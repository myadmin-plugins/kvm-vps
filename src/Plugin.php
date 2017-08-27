<?php

namespace Detain\MyAdminKvm;

use Detain\Kvm\Kvm;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminKvm
 */
class Plugin {

	public static $name = 'KVM VPS';
	public static $description = 'Allows selling of KVM VPS Types.  KVM (for Kernel-based Virtual Machine) is a full virtualization solution for Linux on x86 hardware containing virtualization extensions (Intel VT or AMD-V). It consists of a loadable kernel module, kvm.ko, that provides the core virtualization infrastructure and a processor specific module, kvm-intel.ko or kvm-amd.ko.  Using KVM, one can run multiple virtual machines running unmodified Linux or Windows images. Each virtual machine has private virtualized hardware: a network card, disk, graphics adapter, etc.  More info at https://www.linux-kvm.org/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			//self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getChangeIp(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$kvm = new Kvm(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:' .$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $kvm->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Kvm editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_kvm', 'images/icons/database_warning_48.png', 'ReUsable Kvm Licenses');
			$menu->add_link(self::$module, 'choice=none.kvm_list', 'images/icons/database_warning_48.png', 'Kvm Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.kvm_licenses_list', '/images/whm/createacct.gif', 'List all Kvm Licenses');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_page_requirement('crud_kvm_list', '/../vendor/detain/crud/src/crud/crud_kvm_list.php');
		$loader->add_page_requirement('crud_reusable_kvm', '/../vendor/detain/crud/src/crud/crud_reusable_kvm.php');
		$loader->add_requirement('get_kvm_licenses', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_requirement('get_kvm_list', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_page_requirement('kvm_licenses_list', '/../vendor/detain/myadmin-kvm-vps/src/kvm_licenses_list.php');
		$loader->add_page_requirement('kvm_list', '/../vendor/detain/myadmin-kvm-vps/src/kvm_list.php');
		$loader->add_requirement('get_available_kvm', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_requirement('activate_kvm', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_requirement('get_reusable_kvm', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_page_requirement('reusable_kvm', '/../vendor/detain/myadmin-kvm-vps/src/reusable_kvm.php');
		$loader->add_requirement('class.Kvm', '/../vendor/detain/kvm-vps/src/Kvm.php');
		$loader->add_page_requirement('vps_add_kvm', '/vps/addons/vps_add_kvm.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_kvm_l_cost', 'KVM Linux VPS Cost Per Slice:', 'KVM Linux VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_KVM_L_COST'));
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_kvm_w_cost', 'KVM Windows VPS Cost Per Slice:', 'KVM Windows VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_KVM_W_COST'));
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_cloud_kvm_l_cost', 'Cloud KVM Linux VPS Cost Per Slice:', 'Cloud KVM Linux VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_CLOUD_KVM_L_COST'));
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_cloud_kvm_w_cost', 'Cloud KVM Windows VPS Cost Per Slice:', 'Cloud KVM Windows VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_CLOUD_KVM_W_COST'));
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_kvm_win_server', 'KVM Windows NJ Server', NEW_VPS_KVM_WIN_SERVER, 1, 1);
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_kvm_linux_server', 'KVM Linux NJ Server', NEW_VPS_KVM_LINUX_SERVER, 2, 1);
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_la_kvm_win_server', 'KVM LA Windows Server', NEW_VPS_LA_KVM_WIN_SERVER, 1, 2);
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_la_kvm_linux_server', 'KVM LA Linux Server', NEW_VPS_LA_KVM_LINUX_SERVER, 2, 2);
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_ny_kvm_win_server', 'KVM NY4 Windows Server', NEW_VPS_NY_KVM_WIN_SERVER, 1, 3);
		//$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_ny_kvm_linux_server', 'KVM NY4 Linux Server', NEW_VPS_NY_KVM_LINUX_SERVER, 2, 3);
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_cloud_kvm_win_server', 'Cloud KVM Windows Server', (defined('NEW_VPS_CLOUD_KVM_WIN_SERVER') ? NEW_VPS_CLOUD_KVM_WIN_SERVER : ''), 3);
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_cloud_kvm_linux_server', 'Cloud KVM Linux Server', (defined('NEW_VPS_CLOUD_KVM_LINUX_SERVER') ? NEW_VPS_CLOUD_KVM_LINUX_SERVER : ''), 3);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_kvm_linux', 'Out Of Stock KVM Linux Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_LINUX'), ['0', '1'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_kvm_win', 'Out Of Stock KVM Windows Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_WIN'), ['0', '1'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_kvm_linux_la', 'Out Of Stock KVM Linux Los Angeles', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_LINUX_LA'), ['0', '1'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_kvm_win_la', 'Out Of Stock KVM Windows Los Angeles', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_WIN_LA'), ['0', '1'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_kvm_linux_ny', 'Out Of Stock KVM Linux Equinix NY4', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_LINUX_NY'), ['0', '1'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_kvm_win_ny', 'Out Of Stock KVM Windows Equinix NY4', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_WIN_NY'), ['0', '1'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_cloudkvm', 'Out Of Stock Cloud KVM', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_CLOUDKVM'), ['0', '1'], ['No', 'Yes']);
	}

}
