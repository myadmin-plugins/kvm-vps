---
name: plugin-phpunit-test
description: Writes PHPUnit 9 tests in `tests/PluginTest.php` following the reflection-based pattern: static property checks, hook key/value structure, `ReflectionMethod` for signature validation. Use when user says 'add test', 'write test for', 'test coverage', or after adding new methods to `Plugin.php`. Do NOT use for integration tests requiring myadmin framework bootstrap or tests that call `getSettings`, `getQueue`, `getActivate`, or `getDeactivate` at runtime (those require framework globals like `get_service_define`, `myadmin_log`, `TFSmarty`).
---
# plugin-phpunit-test

## Critical

- **Never** call `getSettings()`, `getQueue()`, `getActivate()`, or `getDeactivate()` at runtime in tests — they depend on undefined framework functions (`get_service_define`, `myadmin_log`, `TFSmarty`, `$GLOBALS['tf']`). Use `ReflectionMethod` for signature validation only.
- All tests must be in `tests/PluginTest.php`, namespace `Detain\MyAdminKvm\Tests`, class `PluginTest extends TestCase`.
- Run tests with `composer test` (config `phpunit.xml.dist`). All tests must pass before finishing.
- Test method names must follow the pattern `test<WhatIsBeingTested>()` in camelCase.
- Every test method must declare return type `: void` and have a `/** ... */` docblock.

## Instructions

1. **Read the current `tests/PluginTest.php`** to understand existing coverage. Read `src/Plugin.php` to see all public static properties and methods.
   - Verify the file header uses `declare(strict_types=1)` and correct namespace before proceeding.

2. **Identify gaps**: For each public static property in `src/Plugin.php` not yet tested, plan a property-value test. For each public static method not yet covered by a signature test, plan a `ReflectionMethod` test.

3. **Write static property tests** using direct static access:
   ```php
   public function testNamePropertyIsKvmVps(): void
   {
       $this->assertSame('KVM VPS', Plugin::$name);
   }

   public function testModulePropertyIsVps(): void
   {
       $this->assertSame('vps', Plugin::$module);
   }
   ```
   Pattern: `assertSame($expectedLiteral, Plugin::$propertyName)`.

4. **Write hook structure tests** by calling `Plugin::getHooks()` and asserting on the returned array:
   ```php
   public function testGetHooksContainsExpectedKeys(): void
   {
       $hooks = Plugin::getHooks();
       $this->assertNotEmpty($hooks);
       $this->assertArrayHasKey('vps.settings', $hooks);
       $this->assertArrayHasKey('vps.deactivate', $hooks);
       $this->assertArrayHasKey('vps.queue', $hooks);
   }

   public function testGetHooksValuesAreCallableArrays(): void
   {
       $hooks = Plugin::getHooks();
       foreach ($hooks as $key => $value) {
           $this->assertIsArray($value, "Hook value for '{$key}' should be an array");
           $this->assertCount(2, $value, "Hook value for '{$key}' should have exactly 2 elements");
           $this->assertSame(Plugin::class, $value[0], "Hook '{$key}' should reference the Plugin class");
           $this->assertIsString($value[1], "Hook '{$key}' method name should be a string");
       }
   }
   ```

5. **Write `ReflectionMethod` signature tests** for each public static method that accepts a `GenericEvent`:
   ```php
   public function testGetQueueMethodSignature(): void
   {
       $reflection = new \ReflectionMethod(Plugin::class, 'getQueue');
       $this->assertTrue($reflection->isStatic());
       $this->assertTrue($reflection->isPublic());
       $params = $reflection->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $type = $params[0]->getType();
       $this->assertNotNull($type);
       $this->assertSame(GenericEvent::class, $type->getName());
   }
   ```
   - This step uses the method name from Step 2.
   - Repeat this pattern for every handler: `getActivate`, `getDeactivate`, `getSettings`, `getQueue`.

6. **Write `ReflectionClass` structural tests** for class-level invariants:
   ```php
   public function testPluginClassIsConcreteAndNotFinal(): void
   {
       $reflection = new \ReflectionClass(Plugin::class);
       $this->assertFalse($reflection->isAbstract());
       $this->assertFalse($reflection->isFinal());
   }

   public function testAllStaticPropertiesArePublic(): void
   {
       $reflection = new \ReflectionClass(Plugin::class);
       $staticProperties = $reflection->getStaticProperties();
       $expectedProperties = ['name', 'description', 'help', 'module', 'type'];
       foreach ($expectedProperties as $prop) {
           $this->assertArrayHasKey($prop, $staticProperties);
           $refProp = $reflection->getProperty($prop);
           $this->assertTrue($refProp->isPublic());
           $this->assertTrue($refProp->isStatic());
       }
   }
   ```

7. **Run tests** to confirm all pass:
   ```bash
   composer test
   ```
   Fix any failures before marking complete. If a test fails with `Fatal error: Call to undefined function get_service_define`, move that test to use `ReflectionMethod` instead of calling the method directly.

## Examples

**User says:** "Add a test that verifies the new `getReboot` method exists and has the right signature after I added it to `src/Plugin.php`."

**Actions taken:**
1. Read `src/Plugin.php` — confirm `public static function getReboot(GenericEvent $event)` exists.
2. Read `tests/PluginTest.php` — confirm no existing `testGetRebootMethodSignature` test.
3. Add to `tests/PluginTest.php`:
   ```php
   /**
    * Tests that the getReboot method exists and accepts a GenericEvent parameter.
    *
    * @return void
    */
   public function testGetRebootMethodSignature(): void
   {
       $reflection = new \ReflectionMethod(Plugin::class, 'getReboot');
       $this->assertTrue($reflection->isStatic());
       $this->assertTrue($reflection->isPublic());
       $params = $reflection->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $type = $params[0]->getType();
       $this->assertNotNull($type);
       $this->assertSame(GenericEvent::class, $type->getName());
   }
   ```
4. Also add to `testExpectedPublicStaticMethods()`'s `$expectedMethods` array: `'getReboot'`.
5. Run `composer test` — all pass.

**Result:** New test verifies `getReboot` is public, static, and accepts exactly one `GenericEvent $event` parameter — without invoking the method body.

## Common Issues

- **`Fatal error: Call to undefined function get_service_define()`** — You called a handler method directly (e.g., `Plugin::getQueue($event)`). Replace with `ReflectionMethod` signature test only; never call handler bodies in tests.

- **`PHPUnit\Framework\ExpectationFailedException: Failed asserting that 4 is identical to 3`** in `testGetHooksReturnsExactlyThreeHooks` — A new hook was added to `getHooks()` in `src/Plugin.php`. Update the `assertCount` value to match the new total.

- **`ReflectionException: Method Plugin::getReboot does not exist`** — The method was not yet added to `src/Plugin.php`, or there is a typo in the method name. Verify with `grep -n 'function get' src/Plugin.php`.

- **Test class not found / autoload error** — Confirm `composer.json` has `"Detain\\MyAdminKvm\\Tests\\":"tests/"` in `autoload-dev.psr-4`. Run `composer dump-autoload` if you just added it.

- **`assertSame` fails on string property** — Check the exact value in `src/Plugin.php` (e.g., `Plugin::$name` is `'KVM VPS'` not `'KVM'`). Copy the literal verbatim.
