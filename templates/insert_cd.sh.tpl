export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
proto="$(echo "{$param}"|cut -d: -f1|tr "[A-Z]" "[a-z]")"
host="$(echo "{$param}"|cut -d/ -f3)"
if [ "$(echo "$host"|grep :)" = "" ]; then
	port="$(grep "^$proto\s" /etc/services |grep "/tcp\s"|cut -d/ -f1|awk "{ print \$2 }")"
else
	host="$(echo "$host"|cut -d: -f1)"
	port="$(echo "$host"|cut -d: -f2)"
fi
path="/$(echo "{$param}"|cut -d/ -f4-)"
echo "<disk type='network' device='cdrom'>
  <driver name='qemu' type='raw'/>
  <target dev='sdb' bus='scsi'/>
  <readonly/>
  <source protocol='$proto' name='$path'>
	<host name='$host' port='$port'/>
  </source>
</disk>" > /root/disk.xml;
virsh update-device {$prefix}{$vps_vzid} /root/disk.xml --live --config
rm -f /root/disk.xml; 
virsh reboot {$prefix}{$vps_vzid};