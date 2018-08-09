export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
virsh change-media {$prefix}{$vps_vzid} sdb --eject --live --config;
