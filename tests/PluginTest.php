<?php

declare(strict_types=1);

namespace Detain\MyAdminKvm\Tests;

use Detain\MyAdminKvm\Plugin;
use MyAdmin\Plugins\Testing\ServicePluginTestCase;

/** Contract and service-lifecycle assertions for the KVM VPS plugin. */
class PluginTest extends ServicePluginTestCase
{
    /** @return string */
    protected function pluginClass()
    {
        return Plugin::class;
    }

    /**
     * The service types this plugin owns, declared rather than scanned.
     *
     * Without this the harness derives the gate from each handler's source, so gutting a
     * handler body to `return;` deletes its gate, which makes the lifecycle assertion
     * not-applicable rather than failed -- the suite stays green and only the skip count
     * moves. Declaring the types means the assertion always has something to drive the
     * handler with, so a handler that stops doing its job fails instead of opting out.
     *
     * @return array<int,string>
     */
    protected function handledTypes()
    {
        return [
            'KVM_LINUX', 'KVM_WINDOWS',
            'CLOUD_KVM_LINUX', 'CLOUD_KVM_WINDOWS',
            'KVMV2', 'KVMV2_WINDOWS', 'KVMV2_STORAGE',
        ];
    }

    /**
     * Pins this plugin's identity and its hook registrations.
     *
     * The shared harness deliberately cannot do this. Every catalogue assertion is
     * conditional on a registration existing, so emptying getHooks() leaves the suite
     * byte-identical -- verified: 31 tests / 20 assertions / 11 skips either way. The
     * same holds for $module and $type, which the harness reads but never pins to an
     * expected value, so 'vps' -> 'kvm' silently detaches this plugin from every VPS
     * lifecycle event while staying green.
     *
     * These four lines are the per-repo half of the contract. Keep them.
     *
     * @return void
     */
    public function testRegistersItsIdentityAndHooks()
    {
        $this->assertSame('vps', Plugin::$module, 'changing $module detaches this plugin from the vps events');
        $this->assertSame('service', Plugin::$type, 'changing $type silently drops the service lifecycle assertions');
        foreach (['vps.settings', 'vps.deactivate', 'vps.queue'] as $hook) {
            $this->assertArrayHasKey($hook, Plugin::getHooks(), $hook.' is no longer registered');
            $this->assertIsCallable(Plugin::getHooks()[$hook], $hook.' resolves to nothing callable');
        }
    }
}
