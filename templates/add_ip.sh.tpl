export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
virsh dumpxml --inactive --security-info {$vps_vzid} > {$vps_vzid}.xml
sed s#"</filterref>"#"  <parameter name='IP' value='{$param}'/>\n    </filterref>"#g -i {$vps_vzid}.xml
virsh define {$vps_vzid}.xml
rm -f {$vps_vzid}.xml
