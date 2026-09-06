<?php
function cfg(): array { static $c=null; return $c ??= require __DIR__.'/config.php'; }
function is_configured(string $v): bool { return $v!=='' && !str_contains($v,'COLOQUE_') && !str_contains($v,'_AQUI'); }
function normalize_creator(string $v): string { $v=mb_strtolower(trim($v),'UTF-8'); return preg_replace('/\s+/',' ',$v); }
function http_get_json(string $url,array $headers=[]): array {
 $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_HTTPHEADER=>$headers,CURLOPT_USERAGENT=>'CarmesimRoleplayMedia/1.0']);
 $body=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
 if($body===false||$status<200||$status>=300) throw new RuntimeException("HTTP $status: $err");
 $d=json_decode($body,true); if(!is_array($d)) throw new RuntimeException('Resposta JSON inválida.'); return $d;
}
function http_post_form(string $url,array $fields,array $headers=[]): array {
 $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($fields),CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_HTTPHEADER=>$headers,CURLOPT_USERAGENT=>'CarmesimRoleplayMedia/1.0']);
 $body=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
 if($body===false||$status<200||$status>=300) throw new RuntimeException("HTTP $status: $err");
 $d=json_decode($body,true); if(!is_array($d)) throw new RuntimeException('Resposta JSON inválida.'); return $d;
}
