case {$vps_os|lower|escapeshellarg} in
	5|windows*)
		echo "change_root skipped: windows guest";
		;;
	*)
		/root/cpaneldirect/provirted.phar update --virt=kvm --password={$param|escapeshellarg} {$vps_vzid|escapeshellarg};
		;;
esac
