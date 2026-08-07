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
}
