<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_kvm define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Kvm Vps',
	'description' => 'Allows selling of Kvm Server and VPS License Types.  More info at https://www.netenberg.com/kvm.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a kvm license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-kvm-vps',
	'repo' => 'https://github.com/detain/myadmin-kvm-vps',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminKvm\Plugin', 'Requirements'],
		'vps.settings' => ['Detain\MyAdminKvm\Plugin', 'Settings'],
		'vps.activate' => ['Detain\MyAdminKvm\Plugin', 'Activate'],
		'vps.change_ip' => ['Detain\MyAdminKvm\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminKvm\Plugin', 'Menu'] */
	],
];
