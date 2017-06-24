<?php

namespace Detain\MyAdminKvm;

use Detain\Kvm\Kvm;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Kvm Vps';
	public static $description = 'Allows selling of Kvm Server and VPS License Types.  More info at https://www.netenberg.com/kvm.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a kvm license. Allow 10 minutes for activation.';
	public static $module = 'vps';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'vps.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function Activate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Kvm Activation', __LINE__, __FILE__);
			function_requirements('activate_kvm');
			activate_kvm($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$kvm = new Kvm(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $kvm->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Kvm editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_kvm', 'icons/database_warning_48.png', 'ReUsable Kvm Licenses');
			$menu->add_link($module, 'choice=none.kvm_list', 'icons/database_warning_48.png', 'Kvm Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.kvm_licenses_list', 'whm/createacct.gif', 'List all Kvm Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('crud_kvm_list', '/../vendor/detain/crud/src/crud/crud_kvm_list.php');
		$loader->add_requirement('crud_reusable_kvm', '/../vendor/detain/crud/src/crud/crud_reusable_kvm.php');
		$loader->add_requirement('get_kvm_licenses', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_requirement('get_kvm_list', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_requirement('kvm_licenses_list', '/../vendor/detain/myadmin-kvm-vps/src/kvm_licenses_list.php');
		$loader->add_requirement('kvm_list', '/../vendor/detain/myadmin-kvm-vps/src/kvm_list.php');
		$loader->add_requirement('get_available_kvm', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_requirement('activate_kvm', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_requirement('get_reusable_kvm', '/../vendor/detain/myadmin-kvm-vps/src/kvm.inc.php');
		$loader->add_requirement('reusable_kvm', '/../vendor/detain/myadmin-kvm-vps/src/reusable_kvm.php');
		$loader->add_requirement('class.Kvm', '/../vendor/detain/kvm-vps/src/Kvm.php');
		$loader->add_requirement('vps_add_kvm', '/vps/addons/vps_add_kvm.php');
	}

	public static function getSettings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Slice Costs', 'vps_slice_kvm_l_cost', 'KVM Linux VPS Cost Per Slice:', 'KVM Linux VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_KVM_L_COST'));
		$settings->add_text_setting($module, 'Slice Costs', 'vps_slice_kvm_w_cost', 'KVM Windows VPS Cost Per Slice:', 'KVM Windows VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_KVM_W_COST'));
		$settings->add_text_setting($module, 'Slice Costs', 'vps_slice_cloud_kvm_l_cost', 'Cloud KVM Linux VPS Cost Per Slice:', 'Cloud KVM Linux VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_CLOUD_KVM_L_COST'));
		$settings->add_text_setting($module, 'Slice Costs', 'vps_slice_cloud_kvm_w_cost', 'Cloud KVM Windows VPS Cost Per Slice:', 'Cloud KVM Windows VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_CLOUD_KVM_W_COST'));
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_kvm_win_server', 'KVM Windows NJ Server', NEW_VPS_KVM_WIN_SERVER, 1, 1);
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_kvm_linux_server', 'KVM Linux NJ Server', NEW_VPS_KVM_LINUX_SERVER, 2, 1);
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_la_kvm_win_server', 'KVM LA Windows Server', NEW_VPS_LA_KVM_WIN_SERVER, 1, 2);
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_la_kvm_linux_server', 'KVM LA Linux Server', NEW_VPS_LA_KVM_LINUX_SERVER, 2, 2);
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_ny_kvm_win_server', 'KVM NY4 Windows Server', NEW_VPS_NY_KVM_WIN_SERVER, 1, 3);
		//$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_ny_kvm_linux_server', 'KVM NY4 Linux Server', NEW_VPS_NY_KVM_LINUX_SERVER, 2, 3);
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_cloud_kvm_win_server', 'Cloud KVM Windows Server', (defined('NEW_VPS_CLOUD_KVM_WIN_SERVER') ? NEW_VPS_CLOUD_KVM_WIN_SERVER : ''), 3);
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_cloud_kvm_linux_server', 'Cloud KVM Linux Server', (defined('NEW_VPS_CLOUD_KVM_LINUX_SERVER') ? NEW_VPS_CLOUD_KVM_LINUX_SERVER : ''), 3);
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_kvm_linux', 'Out Of Stock KVM Linux Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_LINUX'), array('0', '1'), array('No', 'Yes',));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_kvm_win', 'Out Of Stock KVM Windows Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_WIN'), array('0', '1'), array('No', 'Yes',));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_kvm_linux_la', 'Out Of Stock KVM Linux Los Angeles', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_LINUX_LA'), array('0', '1'), array('No', 'Yes',));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_kvm_win_la', 'Out Of Stock KVM Windows Los Angeles', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_WIN_LA'), array('0', '1'), array('No', 'Yes',));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_kvm_linux_ny', 'Out Of Stock KVM Linux Equinix NY4', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_LINUX_NY'), array('0', '1'), array('No', 'Yes',));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_kvm_win_ny', 'Out Of Stock KVM Windows Equinix NY4', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_KVM_WIN_NY'), array('0', '1'), array('No', 'Yes',));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_cloudkvm', 'Out Of Stock Cloud KVM', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_CLOUDKVM'), array('0', '1'), array('No', 'Yes',));
	}

}
