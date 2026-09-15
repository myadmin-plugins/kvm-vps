/root/cpaneldirect/provirted.phar stop --virt=kvm -f {$vps_vzid|escapeshellarg};
/root/cpaneldirect/provirted.phar reinstall --virt=kvm {$vps_vzid|escapeshellarg} {$vps_os|escapeshellarg}{if $rootpass != ''} {$rootpass}{/if};
