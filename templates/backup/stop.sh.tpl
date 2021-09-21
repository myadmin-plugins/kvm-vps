export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
virsh destroy {$vps_vzid};
rm -f /etc/xinetd.d/{$vps_vzid};
service xinetd restart 2>/dev/null || /etc/init.d/xinetd restart 2>/dev/null;