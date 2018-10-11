export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
if [ "$(virsh dumpxml {$vps_vzid}|grep "disk.*cdrom")" = "" ]; then
    echo "Skipping Removal, No CD-ROM Drive exists in VPS configuration";
else
    virsh detach-disk {$vps_vzid} sdb --config
    virsh shutdown {$vps_vzid};
    max=30
    echo "Waiting up to $max Seconds for graceful shutdown";
    start="$(date +%s)";
    while [ $(($(date +%s) - $start)) -le $max ] && [ "$(virsh list |grep {$vps_vzid})" != "" ]; do
        sleep 5s;
    done;
    virsh destroy {$vps_vzid};
    virsh start {$vps_vzid};
    bash /root/cpaneldirect/run_buildebtables.sh;
    /root/cpaneldirect/vps_refresh_vnc.sh {$vps_vzid};
fi;
