<?php

declare(strict_types=1);

namespace Detain\MyAdminKvm\Tests;

use Detain\MyAdminKvm\Plugin;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the KVM VPS Plugin class.
 *
 * @package Detain\MyAdminKvm\Tests
 */
class PluginTest extends TestCase
{
    /**
     * Tests that the Plugin class can be instantiated.
     *
     * @return void
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Tests that the static $name property contains the expected value.
     *
     * @return void
     */
    public function testNamePropertyIsKvmVps(): void
    {
        $this->assertSame('KVM VPS', Plugin::$name);
    }

    /**
     * Tests that the static $description property is a non-empty string.
     *
     * @return void
     */
    public function testDescriptionPropertyIsNonEmptyString(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
    }

    /**
     * Tests that the description mentions KVM technology.
     *
     * @return void
     */
    public function testDescriptionMentionsKvm(): void
    {
        $this->assertStringContainsString('KVM', Plugin::$description);
        $this->assertStringContainsString('Kernel-based Virtual Machine', Plugin::$description);
    }

    /**
     * Tests that the static $help property is an empty string.
     *
     * @return void
     */
    public function testHelpPropertyIsEmptyString(): void
    {
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Tests that the static $module property is 'vps'.
     *
     * @return void
     */
    public function testModulePropertyIsVps(): void
    {
        $this->assertSame('vps', Plugin::$module);
    }

    /**
     * Tests that the static $type property is 'service'.
     *
     * @return void
     */
    public function testTypePropertyIsService(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Tests that getHooks returns an array.
     *
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Tests that getHooks returns non-empty array with expected keys.
     *
     * @return void
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertNotEmpty($hooks);
        $this->assertArrayHasKey('vps.settings', $hooks);
        $this->assertArrayHasKey('vps.deactivate', $hooks);
        $this->assertArrayHasKey('vps.queue', $hooks);
    }

    /**
     * Tests that getHooks does not contain the commented-out activate hook.
     *
     * @return void
     */
    public function testGetHooksDoesNotContainActivateHook(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayNotHasKey('vps.activate', $hooks);
    }

    /**
     * Tests that getHooks keys are prefixed with the module name.
     *
     * @return void
     */
    public function testGetHooksKeysArePrefixedWithModule(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $key) {
            $this->assertStringStartsWith(Plugin::$module . '.', $key);
        }
    }

    /**
     * Tests that each hook value is a callable-style array with class and method.
     *
     * @return void
     */
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

    /**
     * Tests that each hook callback method exists on the Plugin class.
     *
     * @return void
     */
    public function testGetHooksCallbackMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            $this->assertTrue(
                method_exists($value[0], $value[1]),
                "Method {$value[0]}::{$value[1]} referenced by hook '{$key}' does not exist"
            );
        }
    }

    /**
     * Tests that the settings hook references the getSettings method.
     *
     * @return void
     */
    public function testSettingsHookReferencesGetSettings(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['vps.settings']);
    }

    /**
     * Tests that the deactivate hook references the getDeactivate method.
     *
     * @return void
     */
    public function testDeactivateHookReferencesGetDeactivate(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getDeactivate'], $hooks['vps.deactivate']);
    }

    /**
     * Tests that the queue hook references the getQueue method.
     *
     * @return void
     */
    public function testQueueHookReferencesGetQueue(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getQueue'], $hooks['vps.queue']);
    }

    /**
     * Tests that exactly three hooks are registered.
     *
     * @return void
     */
    public function testGetHooksReturnsExactlyThreeHooks(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(3, $hooks);
    }

    /**
     * Tests that the getActivate method exists and accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetActivateMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'getActivate');
        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that the getDeactivate method exists and accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetDeactivateMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'getDeactivate');
        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that the getSettings method exists and accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetSettingsMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'getSettings');
        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that the getQueue method exists and accepts a GenericEvent parameter.
     *
     * @return void
     */
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

    /**
     * Tests that the Plugin class exists in the correct namespace.
     *
     * @return void
     */
    public function testPluginClassExistsInCorrectNamespace(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
        $reflection = new \ReflectionClass(Plugin::class);
        $this->assertSame('Detain\MyAdminKvm', $reflection->getNamespaceName());
    }

    /**
     * Tests that the Plugin class is not abstract or final.
     *
     * @return void
     */
    public function testPluginClassIsConcreteAndNotFinal(): void
    {
        $reflection = new \ReflectionClass(Plugin::class);
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isFinal());
    }

    /**
     * Tests that the constructor takes no required parameters.
     *
     * @return void
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $reflection = new \ReflectionClass(Plugin::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Tests that all static properties are publicly accessible.
     *
     * @return void
     */
    public function testAllStaticPropertiesArePublic(): void
    {
        $reflection = new \ReflectionClass(Plugin::class);
        $staticProperties = $reflection->getStaticProperties();
        $expectedProperties = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expectedProperties as $prop) {
            $this->assertArrayHasKey($prop, $staticProperties, "Static property '{$prop}' should exist");
            $refProp = $reflection->getProperty($prop);
            $this->assertTrue($refProp->isPublic(), "Property '{$prop}' should be public");
            $this->assertTrue($refProp->isStatic(), "Property '{$prop}' should be static");
        }
    }

    /**
     * Tests that all static properties are strings.
     *
     * @return void
     */
    public function testAllStaticPropertiesAreStrings(): void
    {
        $this->assertIsString(Plugin::$name);
        $this->assertIsString(Plugin::$description);
        $this->assertIsString(Plugin::$help);
        $this->assertIsString(Plugin::$module);
        $this->assertIsString(Plugin::$type);
    }

    /**
     * Tests that the description contains a URL to the KVM project site.
     *
     * @return void
     */
    public function testDescriptionContainsKvmUrl(): void
    {
        $this->assertStringContainsString('https://www.linux-kvm.org/', Plugin::$description);
    }

    /**
     * Tests that the Plugin class has exactly the expected public static methods.
     *
     * @return void
     */
    public function testExpectedPublicStaticMethods(): void
    {
        $reflection = new \ReflectionClass(Plugin::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC);
        $methodNames = array_map(fn(\ReflectionMethod $m) => $m->getName(), $methods);
        $expectedMethods = ['getHooks', 'getActivate', 'getDeactivate', 'getSettings', 'getQueue'];
        foreach ($expectedMethods as $method) {
            $this->assertContains($method, $methodNames, "Public static method '{$method}' should exist");
        }
    }

    /**
     * Tests that the hook keys use dot notation consistently.
     *
     * @return void
     */
    public function testHookKeysUseDotNotation(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $key) {
            $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z]+$/', $key, "Hook key '{$key}' should use dot notation");
        }
    }

    /**
     * Tests that the description mentions selling of KVM VPS types.
     *
     * @return void
     */
    public function testDescriptionMentionsSellingVps(): void
    {
        $this->assertStringContainsString('selling of KVM VPS', Plugin::$description);
    }

    /**
     * Tests that the module value matches what hook keys use as prefix.
     *
     * @return void
     */
    public function testModuleValueMatchesHookKeyPrefix(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $key) {
            $prefix = explode('.', $key)[0];
            $this->assertSame(Plugin::$module, $prefix);
        }
    }

    /**
     * Tests that multiple Plugin instances are independent.
     *
     * @return void
     */
    public function testMultipleInstancesAreIndependent(): void
    {
        $plugin1 = new Plugin();
        $plugin2 = new Plugin();
        $this->assertNotSame($plugin1, $plugin2);
        $this->assertInstanceOf(Plugin::class, $plugin1);
        $this->assertInstanceOf(Plugin::class, $plugin2);
    }

    /**
     * Tests that getHooks returns consistent results across multiple calls.
     *
     * @return void
     */
    public function testGetHooksIsIdempotent(): void
    {
        $hooks1 = Plugin::getHooks();
        $hooks2 = Plugin::getHooks();
        $this->assertSame($hooks1, $hooks2);
    }

    /**
     * Tests that the Plugin class does not implement any interfaces.
     *
     * @return void
     */
    public function testPluginDoesNotImplementInterfaces(): void
    {
        $reflection = new \ReflectionClass(Plugin::class);
        $this->assertEmpty($reflection->getInterfaceNames());
    }

    /**
     * Tests that the Plugin class does not extend another class.
     *
     * @return void
     */
    public function testPluginDoesNotExtendAnyClass(): void
    {
        $reflection = new \ReflectionClass(Plugin::class);
        $this->assertFalse($reflection->getParentClass());
    }

    /**
     * Tests that the description mentions both Intel VT and AMD-V.
     *
     * @return void
     */
    public function testDescriptionMentionsVirtualizationExtensions(): void
    {
        $this->assertStringContainsString('Intel VT', Plugin::$description);
        $this->assertStringContainsString('AMD-V', Plugin::$description);
    }
}
