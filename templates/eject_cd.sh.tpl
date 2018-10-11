export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
virsh change-media {$vps_vzid} sdb --eject --live;
virsh change-media {$vps_vzid} sdb --eject --config;
