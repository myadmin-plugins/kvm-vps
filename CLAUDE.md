# MyAdmin KVM VPS Plugin

KVM VPS lifecycle management plugin for the MyAdmin hosting platform.

## Commands

```bash
composer install                        # install deps including phpunit/phpunit ^9.6
vendor/bin/phpunit                      # run all tests (phpunit.xml.dist)
vendor/bin/phpunit tests/ -v            # verbose
```

```bash
composer test                           # run tests via composer script
composer coverage                       # generate coverage text report
```

```bash
caliber refresh && git add CLAUDE.md   # sync docs before committing
make php-cs-fixer                       # run code style fixer
```

## Architecture

**Entry:** `src/Plugin.php` · namespace `Detain\MyAdminKvm` · PSR-4 autoload from `composer.json`

**Hook registration:** `Plugin::getHooks()` returns map of event → `[Plugin::class, 'method']`:
- `vps.settings` → `getSettings(GenericEvent $event)`
- `vps.deactivate` → `getDeactivate(GenericEvent $event)`
- `vps.queue` → `getQueue(GenericEvent $event)`
- `vps.activate` → `getActivate(GenericEvent $event)` *(registered externally, not in getHooks)*

**Event guards:** all handlers check `in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS'), get_service_define('KVMV2'), get_service_define('KVMV2_WINDOWS'), get_service_define('KVMV2_STORAGE')])`

**Queue handler** (`getQueue`): resolves `templates/{action}.sh.tpl` via `TFSmarty`, assigns `$serviceInfo`, fetches rendered shell script, appends to `$event['output']`, calls `$event->stopPropagation()`.

**Logging:** `myadmin_log(self::$module, 'info'|'error', $message, __LINE__, __FILE__, self::$module, $serviceId, true, false, $custid)`

**History:** `$GLOBALS['tf']->history->add(self::$module.'queue', $serviceId, 'delete', '', $custid)` in deactivate.

## Templates

All templates are Smarty `.sh.tpl` files rendered by `TFSmarty` in `getQueue()`.

**Primary** (`templates/`): `create.sh.tpl` · `delete.sh.tpl` · `destroy.sh.tpl` · `start.sh.tpl` · `stop.sh.tpl` · `restart.sh.tpl` · `reset.sh.tpl` · `enable.sh.tpl` · `add_ip.sh.tpl` · `remove_ip.sh.tpl` · `change_hostname.sh.tpl` · `change_timezone.sh.tpl` · `reset_password.sh.tpl` · `reinstall_os.sh.tpl` · `setup_vnc.sh.tpl` · `backup.sh.tpl` · `restore.sh.tpl` · `snapshot_save.sh.tpl` · `snapshot_restore.sh.tpl` · `set_slices.sh.tpl` · `update_hdsize.sh.tpl` · `block_smtp.sh.tpl` · `insert_cd.sh.tpl` · `eject_cd.sh.tpl` · `enable_cd.sh.tpl` · `disable_cd.sh.tpl`

**Backup node** (`templates/backup/`): mirrors primary with backup-specific variants — `backup.sh.tpl` · `create.sh.tpl` · `delete.sh.tpl` · `restore.sh.tpl` · `set_slices.sh.tpl` · `setup_vnc.sh.tpl` etc.

## Settings Pattern

`getSettings()` uses `$settings->setTarget('module')` then chains:
- `add_text_setting(module, group, key, label, desc, current_value)` — for slice costs like `vps_slice_kvm_l_cost`
- `add_select_master(module, group, module, key, label, constant, type, dc)` — for server assignment
- `add_dropdown_setting(module, group, key, label, desc, current, ['0','1'], ['No','Yes'])` — for out-of-stock flags
- End with `$settings->setTarget('global')`

## Testing

**Config:** `phpunit.xml.dist` · tests in `tests/` · namespace `Detain\MyAdminKvm\Tests`

**Pattern** (from `tests/PluginTest.php`):
- Static property assertions: `Plugin::$name`, `Plugin::$module`, `Plugin::$type`
- Hook structure: key format `module.action`, value `[Plugin::class, 'methodName']`
- Reflection tests: `ReflectionMethod` for static/public/param type checks
- Use `assertSame()` for exact matches, `assertStringContainsString()` for substrings

## Conventions

- Tabs for indentation (`.scrutinizer.yml` coding_style)
- `camelCase` for parameters and properties
- `UPPERCASE_CONSTANTS` for defines
- One class per file; `src/Plugin.php` is the only source file
- No PDO — database calls handled by parent myadmin framework
- Wrap i18n strings in `_('string')` for gettext
- Commit messages: lowercase descriptive (`kvm updates`, `fix queue handler`)
- Run `caliber refresh && git add CLAUDE.md` before committing

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
