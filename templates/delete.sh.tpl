export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
{if isset($vps_extra['vnc']) && (int)$vps_extra['vnc'] > 1000}
/root/cpaneldirect/vps_kvm_screenshot_swift.sh {$vps_extra['vnc'] - 5900} {$vps_vzid};
{/if}
virsh destroy {$vps_vzid};
rm -f /etc/xinetd.d/{$vps_vzid};
service xinetd restart 2>/dev/null || /etc/init.d/xinetd restart 2>/dev/null;
virsh autostart --disable {$vps_vzid};