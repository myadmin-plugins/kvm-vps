/root/cpaneldirect/cli/provirted.phar vnc setup --virt=kvm {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if} {$param|escapeshellarg};
