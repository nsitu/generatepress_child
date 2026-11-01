# Deploy GeneratePress Child to CapRover

The workflow updates the existing active child theme through SSH and Docker,
using a checksum-verified WP-CLI PHAR temporarily inside the WordPress container.
It does not rebuild the CapRover app or require a DigitalOcean API token,
CapRover token, or WordPress password.

## 1. Confirm the target from the DigitalOcean console

Run these Bash commands in the droplet web console:

```bash
sudo docker service ls

SERVICE=srv-captain--wordpress-wordpress
sudo docker service inspect "$SERVICE" \
  --format '{{json .Spec.TaskTemplate.ContainerSpec.Mounts}}'

sudo docker service ps "$SERVICE"
CID=$(sudo docker ps \
  --filter "label=com.docker.swarm.service.name=$SERVICE" \
  --filter status=running --format '{{.ID}}')
echo "$CID"

sudo docker exec --user www-data -w /var/www/html "$CID" php -r '
require "wp-load.php";
printf("Active theme folder: %s\nTheme directory: %s\n", get_stylesheet(), get_stylesheet_directory());
'
```

Use the exact service name from `docker service ls` if it differs. Select the
WordPress service, not its database. There must be one running WordPress task
on this droplet, and it must be a Swarm manager. The active theme folder must
be `generatepress_child`, matching the ZIP directory. If it differs, reconcile
the deployment slug with the actual folder before proceeding; the script stops
rather than changing the site's active theme.

The WordPress user and path above are the defaults; change them if your image
differs. That non-root container user must be able to write to the themes and
WordPress upgrade directories.

The service's mounts must explicitly persist `/var/www/html`,
`/var/www/html/wp-content`, or `/var/www/html/wp-content/themes` (or the actual
equivalent path). A plugins-only mount that was sufficient for WPCV does not
cover themes. A mount directly on `generatepress_child` prevents WordPress from
replacing that directory, so mount its parent instead. See
[CapRover persistent apps](https://caprover.com/docs/persistent-apps.html).
When changing persistence, preserve/migrate the existing files before
redeploying the app; a new empty mount can hide the current site's files.

Keep the exported theme ZIP as the initial code backup. Deployment replaces
theme files, so put persistent content/settings in WordPress's database/uploads.

## 2. SSH credential: reuse WPCV or create another key

You can reuse the working WPCV SSH host, login, private key, host key, and
variables if you still have their original values. Repository secrets are
scoped to their repository; adding them to WPCV does not add them here, and
GitHub does not reveal saved secret values.

If you need a new credential, create another key on your PC and authorize it
for the existing WPCV deployment account. Generating it locally means the
private key never needs to be copied through the droplet console.

In **PowerShell on your PC**, run:

```powershell
$sshDirectory = Join-Path $env:USERPROFILE '.ssh'
New-Item -ItemType Directory -Path $sshDirectory -Force | Out-Null
$keyPath = Join-Path $sshDirectory 'generatepress-child-actions'
if ((Test-Path -LiteralPath $keyPath) -or (Test-Path -LiteralPath "$keyPath.pub")) {
    throw 'This key already exists; reuse it or choose a different filename.'
}
ssh-keygen -t ed25519 -C github-actions-generatepress-child -f $keyPath
```

Press **Enter twice** for an empty passphrase. The unattended workflow expects
an unencrypted private key. Copy the **public** key:

```powershell
Get-Content -LiteralPath "$keyPath.pub" -Raw | Set-Clipboard
```

In the **DO console**, replace `YOUR_EXISTING_WPCV_SSH_USER` with the SSH account
already used by the working plugin deployment:

```bash
DEPLOY_USER=YOUR_EXISTING_WPCV_SSH_USER
DEPLOY_HOME=$(getent passwd "$DEPLOY_USER" | cut -d: -f6)
DEPLOY_GROUP=$(id -gn "$DEPLOY_USER")

if [[ "$DEPLOY_HOME" != /* || -z "$DEPLOY_GROUP" ]]; then
  echo 'Check DEPLOY_USER before continuing.'
else
  sudo install -d -m 700 -o "$DEPLOY_USER" -g "$DEPLOY_GROUP" "$DEPLOY_HOME/.ssh"
  printf '\n' | sudo tee -a "$DEPLOY_HOME/.ssh/authorized_keys" >/dev/null
  sudo tee -a "$DEPLOY_HOME/.ssh/authorized_keys" >/dev/null <<'PUBLIC_KEY'
PASTE_THE_SINGLE_LINE_PUBLIC_KEY_HERE
PUBLIC_KEY
  sudo chown "$DEPLOY_USER:$DEPLOY_GROUP" "$DEPLOY_HOME/.ssh/authorized_keys"
  sudo chmod 600 "$DEPLOY_HOME/.ssh/authorized_keys"
fi
```

This appends the public key and preserves existing authorized keys. Confirm the
account can access Docker, directly or through passwordless `sudo -n docker`:

```bash
sudo -iu "$DEPLOY_USER" bash -lc 'docker info >/dev/null 2>&1 || sudo -n docker info >/dev/null'
```

The existing WPCV account should already have this access. Docker permissions
give administrative control of the host; use a trusted deployment account.

## 3. Obtain VM_KNOWN_HOSTS directly from the trusted console

In the **DO console**, set the same IP/hostname and port you will enter in
GitHub. This is the SSH host, not the WordPress website URL:

```bash
SSH_HOST=YOUR_DROPLET_IP
SSH_PORT=22

KEY_HOST=$SSH_HOST
if [[ "$SSH_PORT" != 22 ]]; then
  KEY_HOST="[$SSH_HOST]:$SSH_PORT"
fi

sudo awk -v host="$KEY_HOST" '{print host " " $1 " " $2}' \
  /etc/ssh/ssh_host_ed25519_key.pub
```

Copy the complete output line into `VM_KNOWN_HOSTS`. Its form is:

```text
YOUR_DROPLET_IP ssh-ed25519 AAAA...the-server-public-key...
```

For a nonstandard port it begins `[YOUR_DROPLET_IP]:PORT`. This is the actual
public host key read through your trusted console. No `ssh-keyscan` or local
fingerprint comparison is needed. A fingerprint such as `SHA256:...` alone is
not a valid `known_hosts` entry. Never copy the server's private host key.

## 4. Configure the new GitHub repository

Open [nsitu/generatepress_child settings](https://github.com/nsitu/generatepress_child/settings).
Under **Environments**, create an environment called **production** and add
its environment secrets and variables. Repository-level values under
**Secrets and variables > Actions** work too. Choose one place consistently;
an environment value can override the repository value of the same name.
See [GitHub secrets](https://docs.github.com/en/actions/how-tos/write-workflows/choose-what-workflows-do/use-secrets)
and [variables](https://docs.github.com/en/actions/how-tos/write-workflows/choose-what-workflows-do/use-variables).

### Secrets

| Name | Value |
| --- | --- |
| `VM_HOST` | Droplet's reachable IPv4 address or SSH hostname. No scheme, path, or port. |
| `VM_USER` | Existing deployment SSH login (`DEPLOY_USER` above). |
| `VM_SSH_KEY` | Complete private key, with BEGIN/END lines and all line breaks. |
| `VM_KNOWN_HOSTS` | Full line from step 3. Its hostname/IP must match `VM_HOST`. |

To copy your new **private** key on your PC for the GitHub secret:

```powershell
Get-Content -LiteralPath $keyPath -Raw | Set-Clipboard
```

Paste it only into `VM_SSH_KEY`. Keep the private key out of the repository.

### Variables

| Name | Suggested value | Required? |
| --- | --- | --- |
| `CAPROVER_SERVICE` | `srv-captain--wordpress-wordpress` (confirm step 1) | Yes |
| `VM_PORT` | `22` | Defaults to `22` |
| `WP_PATH` | `/var/www/html` | Defaults to `/var/www/html` |
| `WP_RUN_USER` | `www-data` | Defaults to `www-data` |

Blank optional variables use these defaults. Mapbox and Google Maps keys belong
in **WordPress > Settings > General**, not GitHub Actions secrets.

The droplet's firewall must allow the GitHub runner to reach SSH. Access through
the DO web console alone does not prove that external SSH is reachable.

## 5. Test SSH, then deploy

From **PowerShell on your PC**, test the same credential:

```powershell
$server = 'YOUR_DROPLET_IP'
$sshLogin = 'YOUR_EXISTING_WPCV_SSH_USER'
$sshPort = 22
ssh -i $keyPath -p $sshPort -o IdentitiesOnly=yes "${sshLogin}@${server}" 'docker info >/dev/null 2>&1 || sudo -n docker info >/dev/null'
```

If first asked to trust the host, compare its displayed fingerprint against
this command in the trusted DO console:

```bash
sudo ssh-keygen -lf /etc/ssh/ssh_host_ed25519_key.pub
```

Once configured, commit the changes and push `main`. A push starts validation,
then deployment. You can also choose **Actions > Validate and deploy
GeneratePress Child > Run workflow** on `main`. Pull requests only validate.
Deployments in this repository are serialized; avoid overlapping them with
CapRover app updates or WPCV installs, which use their own separate workflows.

The installer verifies the canonical service name, one local running replica,
the active child theme, the installed parent theme, and persistent storage.
It uses [WP-CLI theme install --force](https://developer.wordpress.org/cli/commands/theme/install/)
without switching themes. It skips plugins and theme PHP during replacement,
and confirms the active slug and installed theme version afterward. Unique
temporary staging directories and runner key files are cleaned up on normal
completion or failure. Abrupt runner termination can leave code archives under
`/tmp/generatepress_child-deploy.*` on the host/container.

After the first deploy, enter the map keys under **Settings > General** and
check the live pages. For the move from CPT UI to theme-owned post types, follow
[the CPT UI handoff and cleanup guide](cptui-migration.md) after deployment.
The installer does not exercise the full production
theme/plugins or perform an HTTP health check. File replacement is not atomic
and there is no automatic rollback. To restore a known-good code version,
revert the relevant change on `main` and push again; database settings remain.
WordPress database template/style overrides may take precedence over theme
files, so manage those separately if using Site Editor customizations.
