#!/usr/bin/env bash
# Executed on the CapRover Docker host by deploy.sh.
set -euo pipefail

staging_dir=${1:?Deployment staging directory is required}
service=${2:?CapRover Docker service name is required}
wp_path=${3:?WordPress container path is required}
wp_run_user=${4:?WordPress container user is required}

# GitHub variables can accidentally contain whitespace from copy/paste.
service=${service#"${service%%[![:space:]]*}"}
service=${service%"${service##*[![:space:]]}"}
[[ -n "$service" ]] || { echo 'CAPROVER_SERVICE must contain a Docker service name.' >&2; exit 1; }

[[ "$wp_path" == /* && "$wp_path" != / ]] || { echo 'WP_PATH must be an absolute container directory.' >&2; exit 1; }
[[ -f "$staging_dir/generatepress_child.zip" && -f "$staging_dir/wp-cli.phar" ]] || { echo 'Deployment files are missing.' >&2; exit 1; }
command -v docker >/dev/null
if docker info >/dev/null 2>&1; then
  docker_cmd=(docker)
else
  docker_cmd=(sudo -n docker)
fi

# Use the actual service name: upgraded CapRover installations may retain prefixes.
if ! service=$("${docker_cmd[@]}" service inspect "$service" --format '{{.Spec.Name}}'); then
  echo 'Cannot resolve CAPROVER_SERVICE on this Docker host. Available services:' >&2
  "${docker_cmd[@]}" service ls --format 'table {{.Name}}\t{{.Replicas}}' >&2 || true
  exit 1
fi
printf 'Target Docker service: %s\n' "$service"

show_service_diagnostics() {
  echo 'Swarm tasks (check the NODE and CURRENT STATE columns):' >&2
  "${docker_cmd[@]}" service ps "$service" --no-trunc >&2 || true
  echo 'Matching containers on this SSH host:' >&2
  "${docker_cmd[@]}" ps -a --filter "label=com.docker.swarm.service.name=$service" \
    --format 'table {{.ID}}\t{{.Names}}\t{{.Status}}' >&2 || true
}

replicas=$("${docker_cmd[@]}" service inspect "$service" --format '{{if .Spec.Mode.Replicated}}{{.Spec.Mode.Replicated.Replicas}}{{else}}0{{end}}')
if [[ "$replicas" != 1 ]]; then
  echo "Deployment requires a single-replica WordPress service; configured replicas: $replicas" >&2
  show_service_diagnostics
  exit 1
fi
containers=$("${docker_cmd[@]}" ps --filter "label=com.docker.swarm.service.name=$service" --filter status=running --format '{{.ID}}')
if [[ -z "$containers" ]]; then
  echo 'No running WordPress container was found on this SSH host. VM_HOST must be the Swarm manager running the WordPress task.' >&2
  show_service_diagnostics
  exit 1
elif [[ "$containers" == *$'\n'* ]]; then
  echo 'Multiple running WordPress containers match this service. Wait for any rolling update or restart to finish, then retry.' >&2
  show_service_diagnostics
  exit 1
elif [[ ! "$containers" =~ ^[a-f0-9]+$ ]]; then
  printf 'Unexpected Docker container ID output: %q\n' "$containers" >&2
  show_service_diagnostics
  exit 1
fi
container=$containers

# Use a temporary WP-CLI PHAR inside the existing container, with its PHP,
# database environment, network, and filesystem. The image need not include wp.
actual_user=$("${docker_cmd[@]}" exec --user "$wp_run_user" "$container" id -u)
[[ "$actual_user" != 0 ]] || { echo 'WP_RUN_USER must be a non-root container user.' >&2; exit 1; }
container_dir=$("${docker_cmd[@]}" exec --user "$wp_run_user" "$container" mktemp -d /tmp/generatepress_child-deploy.XXXXXXXXXX)
[[ "$container_dir" =~ ^/tmp/generatepress_child-deploy\.[a-zA-Z0-9]+$ ]] || { echo 'Unexpected container temporary directory.' >&2; exit 1; }
cleanup() {
  local status=$?
  "${docker_cmd[@]}" exec --user "$wp_run_user" "$container" rm -rf -- "$container_dir" || true
  return "$status"
}
trap cleanup EXIT
"${docker_cmd[@]}" cp "$staging_dir/generatepress_child.zip" "$container:$container_dir/generatepress_child.zip"
"${docker_cmd[@]}" cp "$staging_dir/wp-cli.phar" "$container:$container_dir/wp-cli.phar"
"${docker_cmd[@]}" exec --user 0 "$container" chmod 644 "$container_dir/generatepress_child.zip" "$container_dir/wp-cli.phar"

run_wp() {
  "${docker_cmd[@]}" exec --user "$wp_run_user" --workdir "$wp_path" \
    --env "WP_CLI_CACHE_DIR=$container_dir/cache" "$container" \
    php "$container_dir/wp-cli.phar" --path="$wp_path" "$@"
}

run_wp --skip-plugins --skip-themes core is-installed
active_theme=$(run_wp --skip-plugins --skip-themes option get stylesheet)
[[ "$active_theme" == generatepress_child ]] || {
  printf 'Expected active theme generatepress_child; found %s. Confirm the existing theme folder name before deploying.\n' "$active_theme" >&2
  exit 1
}
if ! run_wp --skip-plugins --skip-themes theme is-installed generatepress; then
  echo 'Parent theme generatepress must already be installed.' >&2
  exit 1
fi
theme_dir=$(run_wp --skip-plugins --skip-themes eval 'echo realpath(get_theme_root("generatepress_child") . "/generatepress_child");')
[[ "$theme_dir" == /* ]] || { echo 'Cannot resolve the existing child theme directory.' >&2; exit 1; }
theme_root=$(dirname -- "$theme_dir")

# Only explicitly configured service volumes/bind mounts count. An anonymous
# image VOLUME can disappear on container replacement and is not sufficient.
mounts=$("${docker_cmd[@]}" service inspect "$service" --format '{{range .Spec.TaskTemplate.ContainerSpec.Mounts}}{{if and (not .ReadOnly) (or (eq .Type "volume") (eq .Type "bind"))}}{{println .Target}}{{end}}{{end}}')
persistent=false
while IFS= read -r mount; do
  mount=${mount%/}
  if [[ -n "$mount" && ( "$theme_root" == "$mount" || "$theme_root" == "$mount/"* ) ]]; then
    persistent=true
    break
  fi
done <<< "$mounts"
if [[ "$persistent" != true ]]; then
  echo 'Themes directory is not covered by a writable persistent service mount. Mount the themes directory or a parent directory in CapRover before deploying.' >&2
  exit 1
fi

# Keep the current active theme and its database settings while replacing code.
# Skip site-specific plugins and theme functions during deployment; these do not
# need to run to install files, and broken old code must not prevent repair.
run_wp --skip-plugins --skip-themes theme install "$container_dir/generatepress_child.zip" --force
active_theme=$(run_wp --skip-plugins --skip-themes option get stylesheet)
[[ "$active_theme" == generatepress_child ]] || { echo 'Active theme changed unexpectedly.' >&2; exit 1; }
run_wp --skip-plugins --skip-themes theme get generatepress_child --field=version
echo 'GeneratePress Child deployed to the persistent WordPress volume and remains active.'
