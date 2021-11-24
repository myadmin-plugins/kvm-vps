/root/cpaneldirect/provirted.phar stop --virt=kvm -f {$vps_vzid|escapeshellarg};
/root/cpaneldirect/provirted.phar destroy --virt=kvm {$vps_vzid|escapeshellarg};
