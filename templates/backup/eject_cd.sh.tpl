export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
virsh change-media {$vps_vzid} hda --eject --live;
virsh change-media {$vps_vzid} hda --eject --config;
