export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
virsh dumpxml --inactive --security-info {$vps_vzid} |grep -v "value='{$param}'" > {$vps_vzid}.xml
virsh define {$vps_vzid}.xml
rm -f {$vps_vzid}.xml
