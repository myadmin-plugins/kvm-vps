<?php

namespace Detain\MyAdminKvm;

use Detain\Kvm\Kvm;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
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

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_kvm', 'icons/database_warning_48.png', 'ReUsable Kvm Licenses');
			$menu->add_link($module, 'choice=none.kvm_list', 'icons/database_warning_48.png', 'Kvm Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.kvm_licenses_list', 'whm/createacct.gif', 'List all Kvm Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
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

	public static function Settings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('licenses', 'Kvm', 'kvm_username', 'Kvm Username:', 'Kvm Username', $settings->get_setting('FANTASTICO_USERNAME'));
		$settings->add_text_setting('licenses', 'Kvm', 'kvm_password', 'Kvm Password:', 'Kvm Password', $settings->get_setting('FANTASTICO_PASSWORD'));
		$settings->add_dropdown_setting('licenses', 'Kvm', 'outofstock_licenses_kvm', 'Out Of Stock Kvm Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes', ));
	}

}
