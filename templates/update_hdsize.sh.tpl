virsh destroy {$vps_vzid};
export pool="$(virsh pool-dumpxml vz 2>/dev/null|grep "<pool"|sed s#"^.*type='\([^']*\)'.*$"#"\1"#g)"
if [ "$pool" = "zfs" ]; then
	virsh vol-resize {$vps_vzid} {$mb}M
else
	sh /root/cpaneldirect/vps_kvm_lvmresize.sh {$vps_vzid} $mb;
fi
virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;
