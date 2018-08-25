export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
{if isset($vps_extra['vnc']) && (int)$vps_extra['vnc'] > 1000}
/root/cpaneldirect/vps_kvm_screenshot_swift.sh {$vps_extra['vnc'] - 5900} {$vps_vzid};
{/if}
virsh destroy {$vps_vzid};
rm -f /etc/xinetd.d/{$vps_vzid};
/etc/init.d/xinetd restart;
virsh autostart --disable {$vps_vzid};
virsh managedsave-remove {$vps_vzid};
virsh undefine {$vps_vzid};
export pool="$(virsh pool-dumpxml vz 2>/dev/null|grep "<pool"|sed s#"^.*type='\([^']*\)'.*$"#"\1"#g)"
if [ "$pool" = "zfs" ]; then
  virsh vol-delete --pool vz {$vps_vzid};
else
  kpartx -dv /dev/vz/{$vps_vzid};
  lvremove -f /dev/vz/{$vps_vzid};
fi
if [ -e /etc/dhcp/dhcpd.vps ]; then
  sed s#"^host {$vps_vzid} .*$"#""#g -i /etc/dhcp/dhcpd.vps;
else
  sed s#"^host {$vps_vzid} .*$"#""#g -i /etc/dhcpd.vps;
fi;
