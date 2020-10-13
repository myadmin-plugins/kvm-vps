export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
rm -f /etc/xinetd.d/{$vps_vzid};
service xinetd restart 2>/dev/null || /etc/init.d/xinetd restart 2>/dev/null;
virsh destroy {$vps_vzid};
virsh dumpxml {$vps_vzid} > {$vps_vzid}.xml
sed s#"<clock.*$"#"<clock offset='timezone' timezone='{$param}'/>"#g -i {$vps_vzid}.xml;
virsh define {$vps_vzid}.xml;
rm -f {$vps_vzid}.xml;
virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;
