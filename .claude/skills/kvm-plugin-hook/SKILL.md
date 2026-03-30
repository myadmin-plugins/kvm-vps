---
name: kvm-plugin-hook
description: Adds a new event hook handler to src/Plugin.php following the GenericEvent pattern. Registers the hook in getHooks(), guards on the KVM service type array, calls myadmin_log(), and optionally stopPropagation(). Use when user says 'add hook', 'new event handler', 'handle vps event', or modifies Plugin.php. Do NOT use for editing Smarty shell templates or PHPUnit tests.
---
# KVM Plugin Hook

## Critical

- **Never** skip the KVM type guard — every handler except `getSettings` must check `in_array($event['type'], [...])` before doing anything.
- **Never** register `vps.activate` in `getHooks()` — it is intentionally commented out and registered externally.
- Hook keys must use dot notation: `self::$module . '.eventname'` (e.g. `vps.suspend`).
- All handler methods must be `public static function` with exactly one parameter: `GenericEvent $event`.
- Call `myadmin_log()` with the full 10-argument signature — do not omit the trailing `true, false, $custid` args.
- Call `$event->stopPropagation()` only when this plugin fully owns the event (queue, activate). Omit it for observe-only hooks (deactivate just logs + history).

## Instructions

### Step 1 — Add the handler method to `src/Plugin.php`

Insert a new `public static function` before the closing `}` of the class. Choose the subject extraction pattern based on hook type:

**Pattern A — service class subject** (activate-style, subject is a service object with `getId()`/`getCustid()`):
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getMyAction(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS'), get_service_define('KVMV2'), get_service_define('KVMV2_WINDOWS'), get_service_define('KVMV2_STORAGE')])) {
        myadmin_log(self::$module, 'info', self::$name.' MyAction', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
        $event->stopPropagation();
    }
}
```

**Pattern B — array subject** (queue-style, subject is `$serviceInfo` array with `vps_id`, `vps_custid` keys):
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getMyAction(GenericEvent $event)
{
    if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS'), get_service_define('KVMV2'), get_service_define('KVMV2_WINDOWS'), get_service_define('KVMV2_STORAGE')])) {
        $serviceInfo = $event->getSubject();
        $settings = get_module_settings(self::$module);
        myadmin_log(self::$module, 'info', self::$name.' MyAction', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id'], true, false, $serviceInfo[$settings['PREFIX'].'_custid']);
        // ... handler logic ...
        $event->stopPropagation();
    }
}
```

**Pattern C — observe-only** (deactivate-style, no stopPropagation, adds history):
```php
public static function getMyAction(GenericEvent $event)
{
    if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS'), get_service_define('KVMV2'), get_service_define('KVMV2_WINDOWS'), get_service_define('KVMV2_STORAGE')])) {
        $serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', self::$name.' MyAction', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
        $GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'myaction', '', $serviceClass->getCustid());
    }
}
```

Verify the method name follows `get` + PascalCase action (e.g. `getSuspend`, `getRestore`) before proceeding.

### Step 2 — Register the hook in `getHooks()`

Add an entry to the returned array in `getHooks()` at `src/Plugin.php`:

```php
public static function getHooks()
{
    return [
        self::$module.'.settings'   => [__CLASS__, 'getSettings'],
        self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
        self::$module.'.queue'      => [__CLASS__, 'getQueue'],
        self::$module.'.myaction'   => [__CLASS__, 'getMyAction'],  // add here
    ];
}
```

Event name must be lowercase, dot-separated: `vps.myaction`.

Verify: `self::$module` is `'vps'`, so the key becomes `'vps.myaction'`.

### Step 3 — Run the tests

```bash
composer test
```

Expected: all existing tests pass. If `testGetHooksReturnsExactlyThreeHooks` fails, update that assertion in `tests/PluginTest.php` to match the new count.

Verify there are no failures before considering the task complete.

## Examples

**User says:** "Add a suspend hook to the KVM plugin"

**Actions taken:**

1. Add `getSuspend` method to `src/Plugin.php` using Pattern A (service class subject, with stopPropagation):
```php
public static function getSuspend(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS'), get_service_define('KVMV2'), get_service_define('KVMV2_WINDOWS'), get_service_define('KVMV2_STORAGE')])) {
        myadmin_log(self::$module, 'info', self::$name.' Suspension', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
        $event->stopPropagation();
    }
}
```

2. Register in `getHooks()`:
```php
self::$module.'.suspend' => [__CLASS__, 'getSuspend'],
```

3. Run `composer test` — update `testGetHooksReturnsExactlyThreeHooks` to `assertCount(4, $hooks)`.

**Result:** `Plugin::getHooks()` now returns `vps.suspend => [Plugin::class, 'getSuspend']` and the method correctly guards on KVM type, logs, and stops propagation.

## Common Issues

**`testGetHooksReturnsExactlyThreeHooks` fails after adding hook:**
The test at `tests/PluginTest.php:212` hard-codes the count. Update:
```php
$this->assertCount(4, $hooks); // was 3
```

**`testExpectedPublicStaticMethods` fails:**
The test at `tests/PluginTest.php:377` lists expected methods. Add your new method name to `$expectedMethods`:
```php
$expectedMethods = ['getHooks', 'getActivate', 'getDeactivate', 'getSettings', 'getQueue', 'getMyAction'];
```

**`testHookKeysUseDotNotation` fails:**
Hook key contains uppercase or underscores. Correct: `self::$module.'.myaction'` not `self::$module.'.myAction'` — event names are always lowercase.

**`myadmin_log` call produces wrong number of arguments:**
The full signature is `myadmin_log($module, $level, $message, __LINE__, __FILE__, $module, $serviceId, true, false, $custid)` — 10 arguments. The 6th arg repeats `self::$module`; `true, false` are always literal.

**Hook fires for non-KVM VPS types:**
You omitted or misplaced the `in_array($event['type'], [...])` guard. It must wrap the entire handler body, not just the log call.
