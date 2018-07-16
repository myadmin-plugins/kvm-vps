export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
sed s#"<clock[^/>]*\(/*>\)"#"<clock offset='timezone' timezone='{$param}'\1"#g -i /etc/libvirt/qemu/{$vps_vzid}.xml  
virsh define /etc/libvirt/qemu/{$vps_vzid}.xml
