{assign var=ram value=$vps_slices * $settings.slice_ram * 1024}
{assign var=hd value=$settings.slice_hd * $vps_slices}
{assign var=hd value=$hd + $settings.additional_hd}
{assign var=hd value=$hd * 1024}
{if in_array($vps_custid, [2773,8,2304])}
{assign var=vcpu value=ceil($vps_slices / 2)}
{else}
{assign var=vcpu value=ceil($vps_slices / 4)}
{/if}
/root/cpaneldirect/vps_kvm_create.sh {$vzid} {','|implode:$ips} '{$vps_os}' {$hd} {$ram} {$vcpu} {$rootpass} {$clientip|escapeshellarg} 2>&1;
