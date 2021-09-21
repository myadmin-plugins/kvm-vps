export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
rm -f /etc/xinetd.d/{$vps_vzid};
service xinetd restart 2>/dev/null || /etc/init.d/xinetd restart 2>/dev/null;
virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;