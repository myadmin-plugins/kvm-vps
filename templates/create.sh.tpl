/root/cpaneldirect/provirted.phar create --virt=kvm \
  {if $module == 'quickservers'} --all{/if} \
  {foreach item=$extraIp from=$extraips} --add-ip={$extraIp}{/foreach} \
  {if $ipv6_ip != false} --ipv6-ip={$ipv6_ip}{/if} \
  {if $ipv6_range != false} --ipv6-range={$ipv6_range}{/if} \
  --order-id={$id} \
  --client-ip={$clientip} \
  --password={$rootpass} \
  {if $module == 'vps'}--io-limit={$vps_slices * 100000000}{/if} \
  {if $module == 'vps'}--iops-limit={(100 * $vps_slices) + 100}{/if} \
  {$vzid} \
  {$hostname} \
  {if $ip == ''}none{else}{$ip}{/if} \
  {$vps_os} \
  {($settings.slice_hd * $vps_slices) + $settings.additional_hd} \
  {$vps_slices * $settings.slice_ram} \
  {$vps_slices};
