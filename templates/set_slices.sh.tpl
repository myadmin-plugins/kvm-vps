{assign var=memory value=1000 * $settings['slice_ram']}
{assign var=memory value=$memory * $vps_slices}
{assign var=diskspace value=$settings['slice_hd'] * $vps_slices}
{assign var=diskspace value=$diskspace + $settings['additional_hd']}
{assign var=diskspace value=$diskspace * 1010}
{if in_array($vps_custid, [2773,8,2304])}
{assign var=vcpu value=ceil($vps_slices / 2)}
{else}
{assign var=vcpu value=ceil($vps_slices / 4)}
{/if}
{assign var=cpushares value=$vps_slices * 512}
{assign var=ioweight value=37 * $vps_slices}
{assign var=ioweight value=$ioweight + 400}
set -x;
/usr/bin/virsh destroy {$vps_vzid};
cd /etc/libvirt/qemu/;
/bin/cp -f /etc/libvirt/qemu/{$vps_vzid}.xml /etc/libvirt/qemu/{$vps_vzid}.xml.backup;
/usr/bin/virsh managedsave-remove {$vps_vzid};
/usr/bin/virsh undefine {$vps_vzid};
cat /etc/libvirt/qemu/{$vps_vzid}.xml.backup|sed -e s#"<currentMemory.*"#"<currentMemory>{$memory}</currentMemory>"#g -e s#"<memory.*"#"<memory>{$memory}</memory>"#g -e s#"<vcpu.*"#"<vcpu>{$vcpu}</vcpu>"#g > /etc/libvirt/qemu/{$vps_vzid}.xml;
/usr/bin/virsh define {$vps_vzid}.xml;
export pool="$(virsh pool-dumpxml vz 2>/dev/null|grep "<pool"|sed s#"^.*type='\([^']*\)'.*$"#"\1"#g)"
if [ "$pool" = "zfs" ]; then
	virsh vol-resize {$vps_vzid} {$diskspace}M --pool vz --shrink
else
	/root/cpaneldirect/vps_kvm_lvmresize.sh {$vps_vzid} {$diskspace};
fi
}
/usr/bin/virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;
if [ ! -d /cgroup/blkio/libvirt/qemu ]; then
  echo No CGroups Enabled;
else 
  virsh schedinfo {$vps_vzid} --set cpu_shares={$cpushares} --current;
  virsh blkiotune {$vps_vzid} --weight {$ioweight} --current;
  virsh blkiotune {$vps_vzid} --weight {$ioweight} --config;
  virsh schedinfo {$vps_vzid} --set cpu_shares={$cpushares} --config;
fi;
