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
			self::$module.'.queue_backup' => [__CLASS__, 'getQueueBackup'],
			self::$module.'.queue_restore' => [__CLASS__, 'getQueueRestore'],
			self::$module.'.queue_enable' => [__CLASS__, 'getQueueEnable'],
			self::$module.'.queue_destroy' => [__CLASS__, 'getQueueDestroy'],
			self::$module.'.queue_delete' => [__CLASS__, 'getQueueDelete'],
			self::$module.'.queue_reinstall_os' => [__CLASS__, 'getQueueReinstallOs'],
			self::$module.'.queue_update_hdsize' => [__CLASS__, 'getQueueUpdateHdsize'],
			self::$module.'.queue_enable_cd' => [__CLASS__, 'getQueueEnableCd'],
			self::$module.'.queue_disable_cd' => [__CLASS__, 'getQueueDisableCd'],
			self::$module.'.queue_insert_cd' => [__CLASS__, 'getQueueInsertCd'],
			self::$module.'.queue_eject_cd' => [__CLASS__, 'getQueueEjectCd'],
			self::$module.'.queue_start' => [__CLASS__, 'getQueueStart'],
			self::$module.'.queue_stop' => [__CLASS__, 'getQueueStop'],
			self::$module.'.queue_restart' => [__CLASS__, 'getQueueRestart'],
			self::$module.'.queue_setup_vnc' => [__CLASS__, 'getQueueSetupVnc'],
			self::$module.'.queue_reset_password' => [__CLASS__, 'getQueueResetPassword'],
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

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueBackup(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Backup', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/backup.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueRestore(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Restore', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/restore.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEnable(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Enable', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/enable.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDestroy(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Destroy', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/destroy.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDelete(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Delete', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/delete.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueReinstallOsupdateHdsize(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Reinstall Osupdate Hdsize', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/reinstall_osupdate_hdsize.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEnableCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Enable Cd', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/enable_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDisableCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Disable Cd', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/disable_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueInsertCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Insert Cd', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/insert_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEjectCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Eject Cd', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/eject_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueStart(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Start', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/start.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueStop(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Stop', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/stop.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueRestart(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Restart', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/restart.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueSetupVnc(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_LINUX'), get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_LINUX'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Setup Vnc', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/setup_vnc.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueResetPassword(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Reset Password', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/reset_password.sh.tpl');
			$event->stopPropagation();
		}
	}

}
