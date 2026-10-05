<?php

declare(strict_types=1);

namespace Detain\MyAdminKvm\Tests;

use Detain\MyAdminKvm\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * MyAdmin plan_2way §5.2, §6: the auto-generated root password UPDATE is sealed
 * only once core's vps_rootpass write flag is on, and the stored *_rootpass
 * columns (an envelope after the flip) never reach Smarty.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RootpassSecretsTest extends TestCase
{
    private static function rootpassValue(...$args)
    {
        $m = new \ReflectionMethod(Plugin::class, 'rootpassValue');
        $m->setAccessible(true);
        return $m->invokeArgs(null, $args);
    }

    public function testTodaysValueWithoutCoreOrWithTheFlagOff(): void
    {
        $this->assertFalse(class_exists('MyAdmin\\Security\\ServiceSecrets'));
        $this->assertSame("New'Pw", self::rootpassValue('vps', 'vps_rootpass', 7, "New'Pw"));
        if (class_exists('MyAdmin\\Plugins\\Testing\\Bootstrap')) {
            \MyAdmin\Plugins\Testing\Bootstrap::installSecrets();
            $this->assertSame("New'Pw", self::rootpassValue('vps', 'vps_rootpass', 7, "New'Pw"));
        }
    }

    public function testNoTemplateRendersAStoredRootpassColumn(): void
    {
        foreach (glob(dirname(__DIR__) . '/templates/*.tpl') as $tpl) {
            $this->assertDoesNotMatchRegularExpression('/\$(vps|qs)_rootpass\b/', (string) file_get_contents($tpl), basename($tpl));
        }
        $src = (string) file_get_contents(dirname(__DIR__) . '/src/Plugin.php');
        $this->assertStringContainsString("\$smarty->assign(array_diff_key(\$serviceInfo, ['vps_rootpass' => true, 'qs_rootpass' => true]));", $src);
        $this->assertStringContainsString('self::redactQueueOutput($output, $serviceInfo)', $src, 'the log redaction still sees every stored value');
        $this->assertStringContainsString("real_escape(self::rootpassValue(\$settings['TABLE'], \$settings['PREFIX'].'_rootpass', (int)\$serviceId, \$newPass))", $src);
    }
}
