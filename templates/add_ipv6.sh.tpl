vzid={$vps_vzid|escapeshellarg}; ip6={$ipv6_ip|escapeshellarg}; range6={$ipv6_range|escapeshellarg};
mac=$(virsh domiflist "$vzid" 2>/dev/null | awk 'NR > 2 && $5 ~ /:/ { print $5; exit }'); [ -n "$mac" ] || mac={$mac|escapeshellarg};
f=/etc/dhcp/dhcpd6.vps; [ -f "$f" ] || f=/etc/dhcpd6.vps;
if [ -f "$f" ]; then
  grep -v -e "host $vzid " -e "fixed-address6 $ip6;" "$f" > "$f.new";
  echo "host $vzid { hardware ethernet $mac; fixed-prefix6 $range6; fixed-address6 $ip6; }" >> "$f.new" && cat "$f.new" > "$f";
  rm -f "$f.new";
  svc=isc-dhcp-server6; [ -d /etc/apt ] || svc=dhcpd6;
  systemctl restart "$svc" 2>/dev/null || service "$svc" restart 2>/dev/null || echo "could not restart $svc";
else
  echo "no dhcpd6.vps on this host, skipping the DHCPv6 entry for $vzid";
fi;
