<?php
require_once __DIR__.'/helpers.php';
function twitch_token(): ?string { static $t=null;$c=cfg();if($t)return $t;if(!is_configured($c['twitch_client_id'])||!is_configured($c['twitch_client_secret']))return null;$d=http_post_form('https://id.twitch.tv/oauth2/token',['client_id'=>$c['twitch_client_id'],'client_secret'=>$c['twitch_client_secret'],'grant_type'=>'client_credentials']);return $t=$d['access_token']??null; }
function twitch_api(string $ep,array $p=[]): array { $c=cfg();$t=twitch_token();if(!$t)return []; $u='https://api.twitch.tv/helix/'.ltrim($ep,'/');if($p)$u.='?'.http_build_query($p);return http_get_json($u,['Client-ID: '.$c['twitch_client_id'],'Authorization: Bearer '.$t]); }
function twitch_search_live(string $q): array {
 $c=cfg();if(!is_configured($c['twitch_client_id'])||!is_configured($c['twitch_client_secret']))return [];
 $d=twitch_api('search/channels',['query'=>$q,'live_only'=>'true','first'=>20]);$out=[];
 foreach(($d['data']??[]) as $ch){$login=$ch['broadcaster_login']??'';if(!$login)continue;$sd=twitch_api('streams',['user_login'=>$login,'first'=>1]);$st=$sd['data'][0]??null;if(!$st)continue;$cr=$st['user_name']??($ch['display_name']??$login);$out[]=['platform'=>'twitch','creator'=>$cr,'creator_key'=>normalize_creator($cr),'title'=>$st['title']??'Carmesim Roleplay','category'=>$st['game_name']??'Red Dead Redemption II','views'=>(int)($st['viewer_count']??0),'url'=>'https://www.twitch.tv/'.rawurlencode($login),'embedUrl'=>'https://player.twitch.tv/?channel='.rawurlencode($login).'&parent='.rawurlencode($c['twitch_parent']).'&autoplay=false','live'=>true,'published_at'=>$st['started_at']??null];}
 return $out;
}
function twitch_search_carmesim_only(): array {
    $c=cfg(); $queries=$c['twitch_queries']??['Carmesim Roleplay','Condado Carmesim']; $all=[];
    foreach($queries as $query){ try{$all=array_merge($all,twitch_search_live($query));}catch(Throwable $e){} }
    return $all;
}
