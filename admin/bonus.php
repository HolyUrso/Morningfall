<?php
require_once '../config/auth.php'; require_once '../config/database.php';
require_once '../config/audit.php'; require_once '../config/streamer_webhook.php';
$streamers=$pdo->query("SELECT id,name,points FROM streamers WHERE active=1 ORDER BY name")->fetchAll();$error='';$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $sid=(int)($_POST['streamer_id']??0);$points=(int)($_POST['points']??0);$reason=trim($_POST['reason']??'');$url=trim($_POST['evidence_url']??'');
 if($sid<=0||$points<=0)$error='Informe streamer e quantidade de pontos válida.';
 elseif($reason==='')$error='Informe o motivo da bonificação.';
 else{
  try{$pdo->beginTransaction();$st=$pdo->prepare("SELECT * FROM streamers WHERE id=? AND active=1 FOR UPDATE");$st->execute([$sid]);$s=$st->fetch();if(!$s)throw new RuntimeException('Streamer não encontrado.');
   $ins=$pdo->prepare("INSERT INTO activities(streamer_id,type,description,points,activity_date,evidence_url,created_by) VALUES(?,'viral_video',?,?,CURDATE(),?,?)");$ins->execute([$sid,'Bonificação Staff: '.$reason,$points,$url?:null,(int)$_SESSION['staff_id']]);$aid=(int)$pdo->lastInsertId();
   $tx=$pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'adjustment',?,?,?,?,?)");$tx->execute([$sid,'Bonificação extraordinária: '.$reason,$points,'staff_bonus',$aid,(int)$_SESSION['staff_id']]);
   $pdo->prepare("UPDATE streamers SET points=points+? WHERE id=?")->execute([$points,$sid]);
   auditLog($pdo,'Bonificação de pontos','Pontos',$sid,$s['name'],'Bonificação extraordinária concedida: +'.$points.' pontos.',['points'=>$s['points']],['points'=>(int)$s['points']+$points,'reason'=>$reason]);
   $pdo->commit();
   sendStreamerWebhook($pdo,$sid,'🎁 Bonificação extraordinária','A Staff concedeu uma bonificação manual por desempenho excepcional.', 'success',['Motivo'=>$reason,'Pontos'=>'+'.$points,'Staff'=>$_SESSION['staff_name']??'Staff','Link'=>$url?:'Não informado']);
   $ok='Bonificação de +'.$points.' pontos concedida com sucesso.';
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage();}
 }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Bonificação</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><main class="container"><a href="index.php">← Dashboard</a><div class="page-head"><div><p class="eyebrow">STAFF</p><h1>Bonificação Extra</h1><p class="muted">Use para vídeos viralizados ou contribuições excepcionais. Não consome o limite semanal de conteúdo.</p></div></div><?php if($error):?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif;?><?php if($ok):?><div class="alert success"><?=htmlspecialchars($ok)?></div><?php endif;?>
<form method="post" class="form"><label>Streamer</label><select name="streamer_id" required><option value="">Selecione</option><?php foreach($streamers as $s):?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['name'])?> — <?=number_format($s['points'],0,',','.')?> pts</option><?php endforeach;?></select><label>Quantidade de pontos</label><input type="number" name="points" min="1" required placeholder="50"><label>Motivo</label><input name="reason" required placeholder="Vídeo viralizou"><label>Link/evidência (opcional)</label><input type="url" name="evidence_url" placeholder="https://..."><button class="btn primary">Conceder bonificação</button></form></main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>