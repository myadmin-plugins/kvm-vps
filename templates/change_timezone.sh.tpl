case {$vps_os|lower|escapeshellarg} in
	5|windows*)
		/root/cpaneldirect/provirted.phar update --virt=kvm --timezone={$param|escapeshellarg} {$vps_vzid|escapeshellarg};
		;;
	*)
		/root/cpaneldirect/provirted.phar stop --virt=kvm {$vps_vzid|escapeshellarg};
		virt-customize --no-network -d {$vps_vzid|escapeshellarg} --timezone {$param|escapeshellarg};
		/root/cpaneldirect/provirted.phar start --virt=kvm {$vps_vzid|escapeshellarg};
		;;
esac
