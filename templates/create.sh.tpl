{assign var=base value="/root/cpaneldirect"}
{assign var=ram value=$vps_slices * $settings.slice_ram * 1024}
{assign var=hd value=$settings.slice_hd * $vps_slices}
{assign var=hd value=$hd + $settings.additional_hd}
{assign var=hd value=$hd * 1024}
{assign var=vcpu value=$vps_slices}
export PATH="/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/bin:/usr/X11R6/bin:/root/bin";
function iprogress() { curl --connect-timeout 60 --max-time 240 -k -d action=install_progress -d progress=$1 -d server={$id} 'https://myvps2.interserver.net/vps_queue.php' < /dev/null > /dev/null 2>&1; }
function install_gz_image() {
	source="$1";
	device="$2";
	echo "Copying $source Image"
	tsize=$(stat -c%s "$source")
	gzip -dc "/$source"  | dd of=$device 2>&1 &
	pid=$!
	echo "Got DD PID $pid";
	sleep 2s;
	if [ "$(pidof gzip)" != "" ]; then
		pid="$(pidof gzip)";
		echo "Tried again, got gzip PID $pid";
	fi;
	if [ "$(echo "$pid" | grep " ")" != "" ]; then
		pid=$(pgrep -f 'gzip -dc');
		echo "Didn't like gzip pid (had a space?), going with gzip PID $pid";
	fi;
	tsize="$(stat -L /proc/$pid/fd/3 -c "%s")";
	echo "Got Total Size $tsize";
	if [ -z $tsize ]; then
		tsize="$(stat -c%s "/$source")";
		echo "Falling back to filesize check, got size $tsize";
	fi;
	while [ -d /proc/$pid ]; do
		copied=$(awk '/pos:/ { print $2 }' /proc/$pid/fdinfo/3);
		completed="$(echo "$copied/$tsize*100" |bc -l | cut -d\. -f1)";
		iprogress $completed &
		if [ "$(ls /sys/block/md*/md/sync_action 2>/dev/null)" != "" ] && [ "$(grep -v idle /sys/block/md*/md/sync_action 2>/dev/null)" != "" ]; then
			export softraid="$(grep -l -v idle /sys/block/md*/md/sync_action 2>/dev/null)";
			for softfile in $softraid; do
				echo idle > $softfile;
			done;
		fi;
		echo "$completed%";
		sleep 10s
	done
}
function install_image() {
	source="$1";
	device="$2";
	echo "Copying Image";
	tsize=$(stat -c%s "$source");
	dd "if=$source" "of=$device" >dd.progress 2>&1 &
	pid=$!
	while [ -d /proc/$pid ]; do
		sleep 9s;
		kill -SIGUSR1 $pid;
		sleep 1s;
		if [ -d /proc/$pid ]; then
			copied=$(tail -n 1 dd.progress | cut -d" " -f1);
			completed="$(echo "$copied/$tsize*100" |bc -l | cut -d\. -f1)";
			iprogress $completed &
			if [ "$(ls /sys/block/md*/md/sync_action 2>/dev/null)" != "" ] && [ "$(grep -v idle /sys/block/md*/md/sync_action 2>/dev/null)" != "" ]; then
				export softraid="$(grep -l -v idle /sys/block/md*/md/sync_action 2>/dev/null)";
				for softfile in $softraid; do
					echo idle > $softfile;
				done;
			fi;
			echo "$completed%";
		fi;
	done;
	rm -f dd.progress;
}
IFS="
"
softraid=""
error=0
adjust_partitions=1
export PREPATH="";
ip="{','|implode:$ips}"
iprogress 1 &
if [ "{$module}" = "quickservers" ]; then
	export url="https://myquickserver2.interserver.net/qs_queue.php"
	export size=all
	export memory=$(echo "$(grep "^MemTotal" /proc/meminfo|awk "{ print \$2 }") / 100 * 70"|bc)
	export vcpu="$(lscpu |grep ^CPU\(s\) | awk ' { print $2 }')"
else
	export url="https://myvps2.interserver.net/vps_queue.php"
	export size={$hd}
	export memory={$ram}
	export vcpu={$vcpu}
fi
if [ "$(kpartx 2>&1 |grep sync)" = "" ]; then
	kpartxopts=""
else
	kpartxopts="-s"
fi
if [ -e /etc/dhcp/dhcpd.vps ]; then
	DHCPVPS=/etc/dhcp/dhcpd.vps
else
	DHCPVPS=/etc/dhcpd.vps
fi
if [ "$(echo "$ip" |grep ",")" != "" ]; then
	extraips="$(echo "$ip"|cut -d, -f2-|tr , " ")"
	ip="$(echo "$ip"|cut -d, -f1)"
else
	extraips=""
fi;
if [ $vcpu -gt 8 ]; then
	max_cpu=$vcpu
else
	max_cpu=8
fi
if [ $memory -gt 16384000 ]; then
	max_memory=$memory
else
	max_memory=16384000;
fi
if [ -e /etc/redhat-release ] && [ $(cat /etc/redhat-release |sed s#"^[^0-9]* "#""#g|cut -c1) -le 6 ]; then
	if [ $(echo "$(e2fsck -V 2>&1 |head -n 1 | cut -d" " -f2 | cut -d"." -f1-2) * 100" | bc | cut -d"." -f1) -le 141 ]; then
		if [ ! -e /opt/e2fsprogs/sbin/e2fsck ]; then
			pushd $PWD;
			cd /admin/ports
			./install e2fsprogs
			popd;
		fi;
		export PREPATH="/opt/e2fsprogs/sbin:";
		export PATH="$PREPATH$PATH";
	fi;
fi;
iprogress 3 &
device=/dev/vz/{$vzid}
export pool="$(virsh pool-dumpxml vz 2>/dev/null|grep "<pool"|sed s#"^.*type='\([^']*\)'.*$"#"\1"#g)"
if [ "$pool" = "" ]; then
	{$base}/create_libvirt_storage_pools.sh
	export pool="$(virsh pool-dumpxml vz 2>/dev/null|grep "<pool"|sed s#"^.*type='\([^']*\)'.*$"#"\1"#g)"
fi
#if [ "$(virsh pool-info vz 2>/dev/null)" != "" ]; then
if [ "$pool" = "zfs" ]; then
	mkdir -p /vz/{$vzid}
	zfs create vz/{$vzid}
	device=/vz/{$vzid}/os.qcow2
	cd /vz
	while [ ! -e /vz/{$vzid} ]; do
		sleep 1;
	done
	#virsh vol-create-as --pool vz --name {$vzid}/os.qcow2 --capacity "$size"M --format qcow2 --prealloc-metadata
	#sleep 5s;
	#device="$(virsh vol-list vz --details|grep " {$vzid}[/ ]"|awk '{ print $2 }')"
else
	{$base}/vps_kvm_lvmcreate.sh {$vzid} $size || exit
fi
iprogress 10 &
echo "$pool pool device $device created"
cd /etc/libvirt/qemu
if /usr/bin/virsh dominfo {$vzid} >/dev/null 2>&1; then
	/usr/bin/virsh destroy {$vzid}
	cp {$vzid}.xml {$vzid}.xml.backup
	/usr/bin/virsh undefine {$vzid}
	mv -f {$vzid}.xml.backup {$vzid}.xml
else
	echo "Generating XML Config"
	if [ "$pool" != "zfs" ]; then
		grep -v -e uuid -e filterref -e "<parameter name='IP'" {$base}/windows.xml | sed s#"windows"#"{$vzid}"#g > {$vzid}.xml
	else
		grep -v -e uuid {$base}/windows.xml | sed -e s#"windows"#"{$vzid}"#g -e s#"/dev/vz/{$vzid}"#"$device"#g > {$vzid}.xml
	fi
	echo "Defining Config As VPS"
	if [ ! -e /usr/libexec/qemu-kvm ] && [ -e /usr/bin/kvm ]; then
	  sed s#"/usr/libexec/qemu-kvm"#"/usr/bin/kvm"#g -i {$vzid}.xml
	fi;
fi
if [ "{$module}" = "quickservers" ]; then
	sed -e s#"^.*<parameter name='IP.*$"#""#g -e  s#"^.*filterref.*$"#""#g -i {$vzid}.xml
else
	repl="<parameter name='IP' value='$ip'/>";
	if [ "$extraips" != "" ]; then
		for i in $extraips; do
			repl="$repl\n        <parameter name='IP' value='$i'/>";
		done
	fi
	sed s#"<parameter name='IP' value.*/>"#"$repl"#g -i {$vzid}.xml;
fi

id=$(echo {$vzid}|sed s#"^\(qs\|windows\|linux\|vps\)\([0-9]*\)$"#"\2"#g)
if [ "$id" != "{$vzid}" ]; then
	mac=$({$base}/convert_id_to_mac.sh $id {$module})
	sed s#"<mac address='.*'"#"<mac address='$mac'"#g -i {$vzid}.xml
else
	sed s#"^.*<mac address.*$"#""#g -i {$vzid}.xml
fi
sed s#"<\(vcpu.*\)>.*</vcpu>"#"<vcpu placement='static' current='$vcpu'>$max_cpu</vcpu>"#g -i {$vzid}.xml;
sed s#"<memory.*memory>"#"<memory unit='KiB'>$memory</memory>"#g -i {$vzid}.xml;
sed s#"<currentMemory.*currentMemory>"#"<currentMemory unit='KiB'>$memory</currentMemory>"#g -i {$vzid}.xml;
sed s#"<parameter name='IP' value.*/>"#"$repl"#g -i {$vzid}.xml;
if [ "$(grep -e "flags.*ept" -e "flags.*npt" /proc/cpuinfo)" != "" ]; then
	sed s#"<features>"#"<features>\n    <hap/>"#g -i {$vzid}.xml;
fi
if [ "$(date "+%Z")" = "PDT" ]; then
	sed s#"America/New_York"#"America/Los_Angeles"#g -i {$vzid}.xml;
fi
if [ -e /etc/lsb-release ]; then
	if [ "$(echo "{$vps_os}"|cut -c1-7)" = "windows" ]; then
		sed -e s#"</features>"#"  <hyperv>\n      <relaxed state='on'/>\n      <vapic state='on'/>\n      <spinlocks state='on' retries='8191'/>\n    </hyperv>\n  </features>"#g -i {$vzid}.xml;
		sed -e s#"<clock offset='timezone' timezone='\([^']*\)'/>"#"<clock offset='timezone' timezone='\1'>\n    <timer name='hypervclock' present='yes'/>\n  </clock>"#g -i {$vzid}.xml;
	fi;
	. /etc/lsb-release;
	if [ $(echo $DISTRIB_RELEASE|cut -d\. -f1) -ge 18 ]; then
		sed s#"\(<controller type='scsi' index='0'.*\)>"#"\1 model='virtio-scsi'>\n      <driver queues='$vcpu'/>"#g -i  {$vzid}.xml;
	fi;
fi;
/usr/bin/virsh define {$vzid}.xml
# /usr/bin/virsh setmaxmem {$vzid} $memory;
# /usr/bin/virsh setmem {$vzid} $memory;
# /usr/bin/virsh setvcpus {$vzid} $vcpu;
mac="$(/usr/bin/virsh dumpxml {$vzid} |grep 'mac address' | cut -d\' -f2)";
/bin/cp -f $DHCPVPS $DHCPVPS.backup;
grep -v -e "host {$vzid} " -e "fixed-address $ip;" $DHCPVPS.backup > $DHCPVPS
echo "host {$vzid} { hardware ethernet $mac; fixed-address $ip; }" >> $DHCPVPS
rm -f $DHCPVPS.backup;
if [ -e /etc/apt ]; then
	systemctl restart isc-dhcp-server 2>/dev/null || service isc-dhcp-server restart 2>/dev/null || /etc/init.d/isc-dhcp-server restart 2>/dev/null
else
	systemctl restart dhcpd 2>/dev/null || service dhcpd restart 2>/dev/null || /etc/init.d/dhcpd restart 2>/dev/null;
fi
iprogress 15 &
echo "Custid is {$custid}";
{if $custid == 565600}
if [ ! -e /vz/templates/template.281311.qcow2 ]; then
  wget -O /vz/templates/template.281311.qcow2 http://kvmtemplates.is.cc/cl/template.281311.qcow2
fi
{assign var=vps_os value="template.281311"}
{/if}
if [ "$pool" = "zfs" ]; then
	if [ -e "/vz/templates/{$vps_os}.qcow2" ]; then
		echo "Copy {$vps_os}.qcow2 Image"
		if [ "$size" = "all" ]; then
			size=$(echo "$(zfs list vz -o available -H -p)  / (1024 * 1024)"|bc)
		if [ $size -gt 2000000 ]; then
			size=2000000
		fi;
		fi
		if [ "$(echo "{$vps_os}"|grep -i freebsd)" != "" ]; then
			cp -f /vz/templates/{$vps_os}.qcow2 $device;
			iprogress 60 &
			qemu-img resize $device "$size"M;
			iprogress 90 &
		else
			qemu-img create -f qcow2 -o preallocation=metadata $device 25G
			iprogress 40 &
			qemu-img resize $device "$size"M;
			iprogress 70 &
			part=$(virt-list-partitions /vz/templates/{$vps_os}.qcow2|tail -n 1)
			backuppart=$(virt-list-partitions /vz/templates/{$vps_os}.qcow2|head -n 1)
{if $vps_os != "template.281311"}            
			virt-resize --expand $part /vz/templates/{$vps_os}.qcow2 $device || virt-resize --expand $backuppart /vz/templates/{$vps_os}.qcow2 $device ;
{else}
			cp -fv /vz/templates/{$vps_os}.qcow2 $device;
{/if}
			iprogress 90 &
		fi;
		virsh detach-disk {$vzid} vda --persistent;
		virsh attach-disk {$vzid} /vz/{$vzid}/os.qcow2 vda --targetbus virtio --driver qemu --subdriver qcow2 --type disk --sourcetype file --persistent;
		virsh dumpxml {$vzid} > vps.xml
		sed s#"type='qcow2'/"#"type='qcow2' cache='writeback' discard='unmap'/"#g -i vps.xml
		virsh define vps.xml
		rm -f vps.xml
		virt-customize -d {$vzid} --root-password password:{$rootpass} --hostname "{$vzid}"
		adjust_partitions=0
	fi
elif [ "$(echo {$vps_os} | cut -c1-7)" = "http://" ] || [ "$(echo {$vps_os} | cut -c1-8)" = "https://" ] || [ "$(echo {$vps_os} | cut -c1-6)" = "ftp://" ]; then
	adjust_partitions=0
	echo "Downloading {$vps_os} Image"
	{$base}/vps_get_image.sh "{$vps_os}"
	if [ ! -e "/image_storage/image.raw.img" ]; then
		echo "There must have been a problem, the image does not exist"
		error=$(($error + 1))
	else
		install_image "/image_storage/image.raw.img" "$device"
		echo "Removing Downloaded Image"
		umount /image_storage
		virsh vol-delete --pool vz image_storage
		rmdir /image_storage
	fi
else
	found=0;
	for source in "/vz/templates/{$vps_os}.img.gz" "/templates/{$vps_os}.img.gz" "/{$vps_os}.img.gz"; do
		if [ $found -eq 0 ] && [ -e "$source" ]; then
			found=1;
			install_gz_image "$source" "$device"
		fi;
	done;
	for source in "/vz/templates/{$vps_os}" "/vz/templates/{$vps_os}.img" "/templates/{$vps_os}.img" "/{$vps_os}.img" "/dev/vz/{$vps_os}"; do
		if [ $found -eq 0 ] && [ -e "$source" ]; then
			found=1;
			install_image "$source" "$device"
		fi;
	done;
	if [ $found -eq 0 ]; then
		echo "Template Does Not Exist"
		error=$(($error + 1))
	fi;
fi
if [ "$softraid" != "" ]; then
	for softfile in $softraid; do
		echo check > $softfile
	done
fi
echo "Errors: $error  Adjust Partitions: $adjust_partitions";
if [ $error -eq 0 ]; then
	if [ "$adjust_partitions" = "1" ]; then
		iprogress resizing &
		sects="$(fdisk -l -u $device  | grep sectors$ | sed s#"^.* \([0-9]*\) sectors$"#"\1"#g)"
		t="$(fdisk -l -u $device | sed s#"\*"#""#g | grep "^$device" | tail -n 1)"
		p="$(echo $t | awk '{ print $1 }')"
		fs="$(echo $t | awk '{ print $5 }')"
		if [ "$(echo "$fs" | grep "[A-Z]")" != "" ]; then
			fs="$(echo $t | awk '{ print $6 }')"
		fi;
		pn="$(echo "$p" | sed s#"$device[p]*"#""#g)"
		if [ $pn -gt 4 ]; then
			pt=l
		else
			pt=p
		fi
		start="$(echo $t | awk '{ print $2 }')"
		if [ "$fs" = "83" ]; then
			echo "Resizing Last Partition To Use All Free Space (Sect $sects P $p FS $fs PN $pn PT $pt Start $start"
			echo -e "d\n$pn\nn\n$pt\n$pn\n$start\n\n\nw\nprint\nq\n" | fdisk -u $device
			kpartx $kpartxopts -av $device
			pname="$(ls /dev/mapper/vz-"{$vzid}"p$pn /dev/mapper/vz-{$vzid}$pn /dev/mapper/"{$vzid}"p$pn /dev/mapper/{$vzid}$pn 2>/dev/null | cut -d/ -f4 | sed s#"$pn$"#""#g)"
			e2fsck -p -f /dev/mapper/$pname$pn
			if [ -f "$(which resize4fs 2>/dev/null)" ]; then
				resizefs="resize4fs"
			else
				resizefs="resize2fs"
			fi
			$resizefs -p /dev/mapper/$pname$pn
			mkdir -p /vz/mounts/{$vzid}$pn
			mount /dev/mapper/$pname$pn /vz/mounts/{$vzid}$pn;
			PATH="$PREPATH/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/bin:/usr/X11R6/bin:/root/bin" \
			echo root:{$rootpass} | chroot /vz/mounts/{$vzid}$pn chpasswd || \
			php {$base}/vps_kvm_password_manual.php {$rootpass} "/vz/mounts/{$vzid}$pn"
			if [ -e /vz/mounts/{$vzid}$pn/home/kvm ]; then
				echo kvm:{$rootpass} | chroot /vz/mounts/{$vzid}$pn chpasswd
			fi;
			umount /dev/mapper/$pname$pn
			kpartx $kpartxopts -d $device
		else
			echo "Skipping Resizing Last Partition FS is not 83. Space (Sect $sects P $p FS $fs PN $pn PT $pt Start $start"
		fi
	fi
	touch /tmp/_securexinetd;
	/usr/bin/virsh autostart {$vzid};
	iprogress starting &
	/usr/bin/virsh start {$vzid};
	if [ "$pool" != "zfs" ]; then
		bash {$base}/run_buildebtables.sh;
	fi;
	if [ "{$module}" = "vps" ]; then
		if [ ! -d /cgroup/blkio/libvirt/qemu ]; then
			echo "CGroups Not Detected, Bailing";
		else
			slices="$(echo $memory / 1000 / 512 |bc -l | cut -d\. -f1)";
			cpushares="$(($slices * 512))";
			ioweight="$(echo "400 + (37 * $slices)" | bc -l | cut -d\. -f1)";
			virsh schedinfo {$vzid} --set cpu_shares=$cpushares --current;
			virsh schedinfo {$vzid} --set cpu_shares=$cpushares --config;
			virsh blkiotune {$vzid} --weight $ioweight --current;
			virsh blkiotune {$vzid} --weight $ioweight --config;
		fi;
	fi;
	{$base}/tclimit $ip;
	if [ "{$clientip}" != "" ]; then
		{$base}/vps_kvm_setup_vnc.sh {$vzid} {$clientip|escapeshellarg};
	fi;
	{$base}/vps_refresh_vnc.sh {$vzid}
	vnc="$((5900 + $(virsh vncdisplay {$vzid} | cut -d: -f2 | head -n 1)))";
	if [ "$vnc" == "" ]; then
		sleep 2s;
		vnc="$((5900 + $(virsh vncdisplay {$vzid} | cut -d: -f2 | head -n 1)))";
		if [ "$vnc" == "" ]; then
			sleep 2s;
			vnc="$(virsh dumpxml {$vzid} |grep -i "graphics type='vnc'" | cut -d\' -f4)";
		fi;
	fi;
	{$base}/vps_kvm_screenshot.sh "$(($vnc - 5900))" "$url?action=screenshot&name={$vzid}";
	sleep 1s;
	{$base}/vps_kvm_screenshot.sh "$(($vnc - 5900))" "$url?action=screenshot&name={$vzid}";
	sleep 1s;
	{$base}/vps_kvm_screenshot.sh "$(($vnc - 5900))" "$url?action=screenshot&name={$vzid}";
	/admin/kvmenable blocksmtp {$vzid}
	rm -f /tmp/_securexinetd;
	if [ "{$module}" = "vps" ]; then
		/admin/kvmenable ebflush;
		{$base}/buildebtablesrules | sh
	fi
	service xinetd restart
fi;
iprogress 100 &
