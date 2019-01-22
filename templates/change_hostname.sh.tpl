export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
export pool="$(virsh pool-dumpxml vz 2>/dev/null|grep "<pool"|sed s#"^.*type='\([^']*\)'.*$"#"\1"#g)";
virsh destroy {$vps_vzid};
if [ "$pool" = "zfs" ]; then
  zfs rename vz/{$vps_vzid} vz/{$param|escapeshellarg};
else
  lvrename /dev/vz/{$vps_vzid} vz/{$param|escapeshellarg};
fi;
virsh domrename {$vps_vzid} {$param|escapeshellarg};
virsh dumpxml {$param|escapeshellarg} > vps.xml;
sed s#"${vps_vzid}"#{$param|escapeshellarg}#g -i vps.xml;
virsh define vps.xml;
rm -fv vps.xml;
for i in $(find /etc -name dhcpd.vps -type f) $(find $PWD -name "vps.*"); do
  sed s#"${vps_vzid}"#{$param|escapeshellarg}#g -i $i;
done;
if [ -e /etc/apt ]; then
    systemctl restart isc-dhcp-server 2>/dev/null || service isc-dhcp-server restart 2>/dev/null || /etc/init.d/isc-dhcp-server restart 2>/dev/null;
else
    systemctl restart dhcpd 2>/dev/null || service dhcpd restart 2>/dev/null || /etc/init.d/dhcpd restart 2>/dev/null;
fi;
rm -vf /etc/xinetd.d/{$vps_vzid} /etc/xinetd.d/${vps_vzid}-spice;
virsh start {$param|escapeshellarg};
./vps_refresh_vnc.sh {$param|escapeshellarg};
