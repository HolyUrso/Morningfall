<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/youtube.php';
require_once __DIR__.'/twitch.php';
$c=cfg(); $file=dirname(__DIR__).'/cache/media.json'; $now=time();
function read_cache($f){if(!is_file($f))return null;$d=json_decode(@file_get_contents($f),true);return is_array($d)?$d:null;}
function write_cache($f,$d){@file_put_contents($f,json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),LOCK_EX);}
$old=read_cache($file);
if($old&&isset($old['updated_unix'])&&$now-(int)$old['updated_unix']<(int)$c['cache_ttl']){echo json_encode($old,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
$youtube=[];$twitch=[];$errors=[];
foreach(($c['youtube_queries']??['Carmesim Roleplay','Condado Carmesim']) as $q){try{$youtube=array_merge($youtube,youtube_search($q));}catch(Throwable $e){$errors[]='YouTube: '.$e->getMessage();}}
try{$twitch=twitch_search_carmesim_only();}catch(Throwable $e){$errors[]='Twitch: '.$e->getMessage();}
function unique_creators(array $items,int $limit):array{$seen=[];$out=[];foreach($items as $it){$k=$it['creator_key']??normalize_creator($it['creator']??'');if(!$k||isset($seen[$k]))continue;$seen[$k]=1;$out[]=$it;if(count($out)>=$limit)break;}return $out;}
usort($youtube,function($a,$b){return strcmp((string)($b['published_at']??''),(string)($a['published_at']??''));});
usort($twitch,function($a,$b){$va=(int)($a['views']??0);$vb=(int)($b['views']??0);if($va!==$vb)return $vb<=>$va;return strcmp((string)($b['published_at']??''),(string)($a['published_at']??''));});
$youtube=unique_creators($youtube,5);
$twitch=unique_creators($twitch,5);
// Demo somente se nenhuma fonte estiver configurada/disponível.
if(!$twitch && !$youtube){$d=$c['demo_item'];$d['platform']='twitch';$d['creator_key']=normalize_creator($d['creator']);$d['embedUrl']='https://player.twitch.tv/?channel=luazinha_ofc&parent='.rawurlencode($c['twitch_parent']).'&autoplay=false';$twitch[]=$d;}
$items=array_merge($youtube,$twitch);
$res=['ok'=>true,'updated_at'=>date('c'),'updated_unix'=>$now,'next_refresh_in'=>(int)$c['cache_ttl'],'youtube'=>$youtube,'twitch'=>$twitch,'items'=>$items,'errors'=>$errors];
write_cache($file,$res); echo json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
