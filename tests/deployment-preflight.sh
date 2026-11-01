#!/usr/bin/env bash
# Exercise Docker discovery without contacting Docker or modifying WordPress.
set -euo pipefail

repo_root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
test_dir=$(mktemp -d)
trap 'rm -rf -- "$test_dir"' EXIT
touch "$test_dir/generatepress_child.zip" "$test_dir/wp-cli.phar"
export MOCK_LOG="$test_dir/docker.log"
export MOCK_CONTAINERS=''
export MOCK_REPLICAS=1
export MOCK_ALLOW_EXEC=false
export MOCK_ACTIVE_THEME=generatepress_child
export MOCK_MOUNTS=/var/www/html
export MOCK_PARENT_INSTALLED=true

docker() {
  printf '%s\n' "$*" >> "$MOCK_LOG"
  case "$1 ${2:-}" in
    'info ') return 0 ;;
    'service inspect')
      [[ "$3" == srv-captain--wordpress-wordpress ]] || return 1
      if [[ "${!#}" == '{{.Spec.Name}}' ]]; then
        echo srv-captain--wordpress-wordpress
      elif [[ "${!#}" == *ContainerSpec.Mounts* ]]; then
        printf '%s\n' "$MOCK_MOUNTS"
      else
        echo "$MOCK_REPLICAS"
      fi
      ;;
    'service ps') echo 'MOCK TASK NODE STATE' ;;
    'service ls') echo 'MOCK AVAILABLE SERVICES' ;;
    'ps -a') echo 'MOCK LOCAL CONTAINERS' ;;
    'ps --filter') printf '%s' "$MOCK_CONTAINERS" ;;
    'cp '*) [[ "$MOCK_ALLOW_EXEC" == true ]] ;;
    'exec '*)
      [[ "$MOCK_ALLOW_EXEC" == true ]] || return 99
      case "$*" in
        *' id -u') echo 33 ;;
        *' mktemp -d '*) echo /tmp/generatepress_child-deploy.ABC123 ;;
        *' chmod 644 '*|*' rm -rf '*) return 0 ;;
        *' core is-installed') return 0 ;;
        *' option get stylesheet') echo "$MOCK_ACTIVE_THEME" ;;
        *' theme is-installed generatepress') [[ "$MOCK_PARENT_INSTALLED" == true ]] ;;
        *' eval '*) echo /var/www/html/wp-content/themes/generatepress_child ;;
        *' theme install '*' --force') return 0 ;;
        *' theme get generatepress_child --field=version') echo 0.1 ;;
        *) echo "Unexpected container command: $*" >&2; return 99 ;;
      esac
      ;;
    *) echo "Unexpected Docker mutation: $*" >&2; return 99 ;;
  esac
}
export -f docker

run_failure() {
  local service=$1 expected=$2 output status=0
  : > "$MOCK_LOG"
  output=$(bash "$repo_root/scripts/remote-install.sh" "$test_dir" "$service" /var/www/html www-data 2>&1) || status=$?
  if [[ "$status" != 1 || "$output" != *"$expected"* ]]; then
    printf 'Unexpected result (exit %s):\n%s\n' "$status" "$output" >&2
    exit 1
  fi
  if grep -Eq '^(cp|exec) ' "$MOCK_LOG"; then
    echo 'Container discovery failure must not modify the container.' >&2
    exit 1
  fi
}

# Whitespace is removed before service inspection, and zero matches are explicit.
run_failure $' \tsrv-captain--wordpress-wordpress\r\n' 'No running WordPress container'
grep -q '^service ps srv-captain--wordpress-wordpress --no-trunc$' "$MOCK_LOG"
grep -q '^ps -a --filter label=com.docker.swarm.service.name=srv-captain--wordpress-wordpress ' "$MOCK_LOG"

export MOCK_CONTAINERS=$'abcdef123456\n123456abcdef'
run_failure srv-captain--wordpress-wordpress 'Multiple running WordPress containers'

export MOCK_CONTAINERS='not-a-container-id'
run_failure srv-captain--wordpress-wordpress 'Unexpected Docker container ID output'

export MOCK_REPLICAS=2
run_failure srv-captain--wordpress-wordpress 'configured replicas: 2'

run_failure unknown-service 'Cannot resolve CAPROVER_SERVICE'
grep -q '^service ls ' "$MOCK_LOG"

run_failure '   ' 'CAPROVER_SERVICE must contain a Docker service name'
[[ ! -s "$MOCK_LOG" ]]

echo 'PASS: service whitespace, zero/multiple/invalid container IDs, replicas, missing service, diagnostic output, and no container changes on failure.'

# Exercise the complete installer without changing any files or a database.
export MOCK_ALLOW_EXEC=true
export MOCK_REPLICAS=1
export MOCK_CONTAINERS=abcdef123456
run_installer() {
  bash "$repo_root/scripts/remote-install.sh" "$test_dir" srv-captain--wordpress-wordpress /var/www/html www-data
}

expect_no_install() {
  local expected=$1 output status=0
  : > "$MOCK_LOG"
  output=$(run_installer 2>&1) || status=$?
  [[ "$status" != 0 && "$output" == *"$expected"* ]] || {
    printf 'Unexpected pre-install failure: %s\n' "$output" >&2
    exit 1
  }
  if grep -q ' theme install ' "$MOCK_LOG"; then
    echo 'A failed preflight must not install the theme.' >&2
    exit 1
  fi
  grep -q ' rm -rf -- /tmp/generatepress_child-deploy.ABC123$' "$MOCK_LOG"
}

export MOCK_ACTIVE_THEME=other-theme
expect_no_install 'Expected active theme generatepress_child'
export MOCK_ACTIVE_THEME=generatepress_child
export MOCK_PARENT_INSTALLED=false
expect_no_install 'Parent theme generatepress must already be installed'
export MOCK_PARENT_INSTALLED=true
export MOCK_MOUNTS=/var/www/html/wp-content/plugins
expect_no_install 'Themes directory is not covered'
export MOCK_MOUNTS=/var/www/html/wp-content/themes/generatepress_child
expect_no_install 'Themes directory is not covered'
export MOCK_MOUNTS=/var/www/html/wp-content/themes
: > "$MOCK_LOG"
run_installer
grep -q ' theme install /tmp/generatepress_child-deploy.ABC123/generatepress_child.zip --force$' "$MOCK_LOG"
grep -q ' theme get generatepress_child --field=version$' "$MOCK_LOG"
if grep -q ' theme activate ' "$MOCK_LOG"; then
  echo 'Deployment must preserve the existing theme selection.' >&2
  exit 1
fi
echo 'PASS: active theme and parent guards, persistent themes mount, child-only mount rejection, successful install, and cleanup.'
