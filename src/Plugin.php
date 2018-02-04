<?php

namespace Detain\MyAdminKvm;

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
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.queue' => [__CLASS__, 'getQueue'],
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

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueue(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			$vps = $event->getSubject();
			myadmin_log(self::$module, 'info', self::$name.' Queue '.ucwords(str_replace('_', ' ', $vps['action'])).' for VPS '.$vps['vps_hostname'].'(#'.$vps['vps_id'].'/'.$vps['vps_vzid'].')', __LINE__, __FILE__);
			$server_info = $vps['server_info'];
			if (!file_exists(__DIR__.'/../templates/'.$vps['action'].'.sh.tpl')) {
				myadmin_log(self::$module, 'error', 'Call '.$vps['action'].' for VPS '.$vps['vps_hostname'].'(#'.$vps['vps_id'].'/'.$vps['vps_vzid'].') Does not Exist for '.self::$name, __LINE__, __FILE__);
			} else {
				$smarty = new \TFSmarty();
				$smarty->assign($vps);
				$smarty->assign('vps_vzid', is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid']);
				echo $smarty->fetch(__DIR__.'/../templates/'.$vps['action'].'.sh.tpl');
			}
			$event->stopPropagation();
		}
	}
}
