---
name: vps-shell-template
description: Creates a new Smarty shell template `.sh.tpl` for a KVM VPS queue action in `templates/` and optionally `templates/backup/`. Use when user says 'add template', 'new vps action', 'add shell script', or needs a new queue action for KVM VPS. Do NOT use for PHP plugin logic changes, new hook registrations, or settings configuration.
---
# VPS Shell Template

Creates a new Smarty shell script template for a KVM VPS queue action.

## Critical

- Templates are rendered by `TFSmarty` in `Plugin::getQueue()`. The action name **must exactly match** the queue action string used in `$serviceInfo['action']` — the filename (without `.sh.tpl`) is the lookup key.
- `getQueue()` only looks in the `templates/` directory for the primary node path — no subdirectory routing.
- The backup node path under `templates/backup/` is a separate file used when the VPS lives on a backup node. If the action must work on both primary and backup nodes, **both files are required**.
- Always escape user-controlled or VPS-controlled vars with `|escapeshellarg` to prevent shell injection.
- Never use `$_GET`/`$_POST` directly in templates — all data comes via `$smarty->assign($serviceInfo)`.

## Instructions

### Step 1 — Identify the action name

The filename (minus `.sh.tpl`) becomes the action key. Use lowercase with underscores matching the existing pattern:
```
create, delete, destroy, start, stop, restart, reset, enable,
add_ip, remove_ip, change_hostname, change_timezone, reset_password,
reinstall_os, setup_vnc, backup, restore, snapshot_save, snapshot_restore,
set_slices, update_hdsize, block_smtp, insert_cd, eject_cd, enable_cd, disable_cd
```
Verify the name does not already exist by checking the `templates/` directory — stop if it does.

### Step 2 — Identify available `$serviceInfo` variables

All keys from `$serviceInfo` are available as Smarty variables. Commonly used ones:

| Smarty var | Meaning |
|---|---|
| `{$vps_vzid}` | VPS container ID (e.g. `linux1234`) |
| `{$vps_id}` | Numeric VPS row ID |
| `{$id}` | Same as `$vps_id` |
| `{$hostname}` | VPS hostname |
| `{$ip}` | Primary IP |
| `{$param}` | Action-specific single parameter (hostname, IP, etc.) |
| `{$vps_slices}` | Number of resource slices |
| `{$vps_os}` | OS template name |
| `{$rootpass}` | Root password |
| `{$settings.slice_hd}` | HD per slice (GB) |
| `{$settings.slice_ram}` | RAM per slice (MB) |
| `{$settings.iolimit}` | IO limit value or `false` |
| `{$settings.iopslimit}` | IOPS limit value or `false` |
| `{$module}` | Module name (`vps` or `quickservers`) |
| `{$clientip}` | Client's public IP |
| `{$sshKey}` | SSH public key or `false` |
| `{$extraips}` | Array of extra IPs |
| `{$ipv6_ip}` | IPv6 address or `false` |

### Step 3 — Write the primary action template

Create `templates/{action}.sh.tpl` (replace `{action}` with the actual action name). Choose the right pattern for the action complexity:

**Simple single-command action** (e.g. `start`, `stop`, `reset_password`):
```smarty
/root/cpaneldirect/provirted.phar {verb} --virt=kvm {$vps_vzid|escapeshellarg};
```

**Action with one extra parameter** (e.g. `change_hostname`, `add_ip`):
```smarty
/root/cpaneldirect/provirted.phar {verb} --virt=kvm {$vps_vzid|escapeshellarg} {$param|escapeshellarg};
```

**Action with conditional flags** (e.g. `create`, `set_slices`):
```smarty
/root/cpaneldirect/provirted.phar {verb} --virt=kvm \
{if $settings.iolimit != false}
  --io-limit={$settings.iolimit} \
{/if}
  {$vps_vzid|escapeshellarg};
```

**Backup-node action needing PATH export and virsh** (e.g. `backup/start`, `backup/stop`):
```smarty
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
virsh {verb} {$vps_vzid};
```

Verify: the file ends with a newline and the final command ends with `;`.

### Step 4 — Write the backup node template (if needed)

If the action must execute on backup nodes (libvirt/virsh-based), create a second file at `templates/backup/{action}.sh.tpl`. Backup templates:
- Start with `export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";`
- Use `virsh` commands directly instead of `provirted.phar`
- Handle ZFS vs LVM pool differences when renaming/moving storage

See `templates/backup/start.sh.tpl` and `templates/backup/change_hostname.sh.tpl` for reference patterns.

Verify both the primary and backup template files exist after creation.

### Step 5 — Run tests

```bash
composer test
```

Tests in `tests/PluginTest.php` validate method signatures and hook structure, not template content — a passing suite confirms the PHP plugin is intact.

## Examples

**User says:** "Add a `suspend` VPS action template"

**Actions taken:**
1. Confirm `templates/suspend.sh.tpl` does not exist.
2. Create `templates/suspend.sh.tpl`:
   ```smarty
   /root/cpaneldirect/provirted.phar suspend --virt=kvm {$vps_vzid|escapeshellarg};
   ```
3. Create `templates/backup/suspend.sh.tpl` (backup node variant using virsh):
   ```smarty
   export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
   virsh suspend {$vps_vzid};
   ```
4. Run `composer test`.

**Result:** Queue action `suspend` now resolves to both templates; `Plugin::getQueue()` will render and append the correct script based on which node the VPS is on.

---

**User says:** "Add a `migrate` action that takes a destination server as a param"

**Actions taken:**
1. Create `templates/migrate.sh.tpl`:
   ```smarty
   /root/cpaneldirect/provirted.phar migrate --virt=kvm {$vps_vzid|escapeshellarg} {$param|escapeshellarg};
   ```
2. No backup counterpart needed if migration is handled exclusively via `provirted.phar`.

## Common Issues

**Template not found / logged as error:**
> `Call {action} for VPS ... Does not Exist for KVM VPS`

The filename does not match the action string exactly. Check `$serviceInfo['action']` value vs. filename. Action names are case-sensitive and use underscores, not hyphens.

**Smarty syntax error on `{if ... != false}`:**
Smarty requires the `{/if}` closing tag on its own token. A missing `}` in a compound `{if ... }{/if}` block causes a fatal render error. Verify all `{if}` blocks are properly closed.

**Shell argument not escaped, causes command failure:**
Any variable that could contain spaces or special chars (hostnames, passwords, IPs) must use `|escapeshellarg`. Without it, a hostname like `my server` breaks the shell command silently. Always add `|escapeshellarg` to `{$vps_vzid}`, `{$param}`, `{$hostname}`, `{$rootpass}`.

**Backup node runs wrong template (provirted.phar not found):**
The backup node uses libvirt, not `provirted.phar`. If the backup variant is missing, `getQueue()` falls back to the primary template which calls `provirted.phar` — resulting in `command not found`. Create the `templates/backup/` variant using `virsh` commands.

**Tests fail after adding template:**
Templates have no direct PHPUnit coverage. If tests fail, the cause is unrelated to the new template — check for syntax errors in any PHP files modified alongside the template work.
