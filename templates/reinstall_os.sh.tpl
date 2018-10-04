export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
{if isset($vps_extra['vnc']) && (int)$vps_extra['vnc'] > 1000}
/root/cpaneldirect/vps_kvm_screenshot_swift.sh {$vps_extra['vnc'] - 5900} {$vps_vzid};
{/if}
virsh destroy {$vps_vzid} 2>/dev/null;
rm -f /etc/xinetd.d/{$vps_vzid};
service xinetd restart 2>/dev/null || /etc/init.d/xinetd restart 2>/dev/null;
virsh autostart --disable {$vps_vzid} 2>/dev/null;
virsh managedsave-remove {$vps_vzid} 2>/dev/null;
virsh undefine {$vps_vzid};
export pool="$(virsh pool-dumpxml vz 2>/dev/null|grep "<pool"|sed s#"^.*type='\([^']*\)'.*$"#"\1"#g)"
if [ "$pool" = "zfs" ]; then
  device="$(virsh vol-list vz --details|grep " {$vps_vzid}[/ ]"|awk '{ print $2 }')";
else
  device="/dev/vz/{$vps_vzid}";
  kpartx -dv $device;
fi
if [ "$pool" = "zfs" ]; then
  virsh vol-delete --pool vz {$vps_vzid}/os.qcow2 2>/dev/null;
  virsh vol-delete --pool vz {$vps_vzid} 2>/dev/null;
  zfs list -t snapshot|grep "/{$vps_vzid}@"|cut -d" " -f1|xargs -r -n 1 zfs destroy -v;
  zfs destroy vz/{$vps_vzid};
  if [ -e /vz/{$vps_vzid} ]; then
	rmdir /vz/{$vps_vzid};
  fi;
else
  lvremove -f $device;
fi
