<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';
require_once '../config/content_points.php';
require_once '../config/streamer_webhook.php';
$id=(int)$_SESSION['streamer_id'];
$st=$pdo->prepare("SELECT * FROM streamers WHERE id=? AND active=1"); $st->execute([$id]); $s=$st->fetch();
if(!$s){session_destroy();header('Location: ../streamer-login.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $url=trim($_POST['url']??'');
    $proofFiles = $_FILES['proof_images'] ?? null;
    $hasProofImage = false;
    if ($proofFiles && isset($proofFiles['name']) && is_array($proofFiles['name'])) {
        foreach ($proofFiles['name'] as $i => $originalName) {
            if (($proofFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && trim((string)$originalName) !== '') {
                $hasProofImage = true;
                break;
            }
        }
    }
    $date=$_POST['submission_date']??date('Y-m-d');
    $durationHm=trim($_POST['duration_hm']??'');
    $durationMinutes=null;
    if($durationHm!==''){
        if(!preg_match('/^(\d{1,3}):([0-5]\d)$/',$durationHm,$dm)) $error='Informe a duração da live no formato HH:MM.';
        else { $durationMinutes=((int)$dm[1]*60)+(int)$dm[2]; if($durationMinutes<=0) $error='A duração da live deve ser maior que zero.'; }
    }
    $collab=isset($_POST['collab']) && $_POST['collab']==='1' ? 1:0;
    $collabId=$collab?(int)($_POST['collab_streamer_id']??0):null;
    if($url === '' && !$hasProofImage && $error==='') $error='Informe o link da VOD ou envie pelo menos 1 comprovante (foto/print).';
    elseif($error!=='') {}
    elseif($url !== '' && !filter_var($url,FILTER_VALIDATE_URL)) $error='Informe um link válido ou deixe o campo vazio e envie um comprovante.';
    elseif($collab && (!$collabId || $collabId===$id)) $error='Selecione outro streamer para a Collab/Raid.';
    else if($error===''){
        $chk=$pdo->prepare("SELECT COUNT(*) FROM content_submissions WHERE streamer_id=? AND url=? AND status IN ('pending','approved') AND url<>''");
        $chk->execute([$id,$url]);
        if((int)$chk->fetchColumn()) $error='Este link já foi enviado.';
        else{
            $uploadedPaths=[];
            try {
                $files = $proofFiles;
                if ($files && isset($files['name']) && is_array($files['name'])) {
                    $count = 0;
                    foreach ($files['name'] as $i => $originalName) {
                        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                        $count++;
                    }
                    if ($count > 3) throw new RuntimeException('Você pode enviar no máximo 3 imagens.');
                }

                $ins=$pdo->prepare("INSERT INTO content_submissions(streamer_id,content_type,url,submission_date,collab,collab_streamer_id,duration_minutes) VALUES(?,?,?,?,?,?,?)");
                $ins->execute([$id,'vod',$url,$date,$collab,$collabId,$durationMinutes]);
                $sid=(int)$pdo->lastInsertId();

                $uploadDir=__DIR__.'/../assets/uploads/vod-proofs';
                if(!is_dir($uploadDir) && !mkdir($uploadDir,0755,true) && !is_dir($uploadDir)) throw new RuntimeException('Não foi possível preparar a pasta de imagens.');

                if ($files && isset($files['name']) && is_array($files['name'])) {
                    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                    $finfo=function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                    foreach ($files['name'] as $i => $originalName) {
                        $err=(int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                        if($err===UPLOAD_ERR_NO_FILE) continue;
                        if($err!==UPLOAD_ERR_OK) throw new RuntimeException('Uma das imagens não pôde ser enviada.');
                        $size=(int)($files['size'][$i] ?? 0);
                        if($size<=0 || $size>5*1024*1024) throw new RuntimeException('Cada imagem deve ter no máximo 5 MB.');
                        $tmp=$files['tmp_name'][$i] ?? '';
                        $mime=$finfo ? finfo_file($finfo,$tmp) : ($files['type'][$i] ?? '');
                        if(!isset($allowed[$mime]) || @getimagesize($tmp)===false) throw new RuntimeException('Envie somente imagens JPG, PNG ou WEBP.');
                        if(!function_exists('imagewebp')) throw new RuntimeException('O servidor não possui suporte para conversão de imagens para WebP.');

                        // Todos os comprovantes são convertidos para WebP antes de serem armazenados.
                        $filename='vod_'.$sid.'_'.bin2hex(random_bytes(8)).'.webp';
                        $target=$uploadDir.'/'.$filename;
                        $source=null;
                        if($mime==='image/jpeg') $source=@imagecreatefromjpeg($tmp);
                        elseif($mime==='image/png') $source=@imagecreatefrompng($tmp);
                        elseif($mime==='image/webp') $source=@imagecreatefromwebp($tmp);
                        if(!$source) throw new RuntimeException('Não foi possível processar uma das imagens enviadas.');
                        if(function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($source);
                        imagealphablending($source,false);
                        imagesavealpha($source,true);
                        if(!@imagewebp($source,$target,82)){ imagedestroy($source); throw new RuntimeException('Não foi possível converter uma das imagens para WebP.'); }
                        imagedestroy($source);
                        clearstatcache(true,$target);
                        $convertedSize=(int)@filesize($target);
                        if($convertedSize<=0) throw new RuntimeException('O comprovante convertido não pôde ser salvo.');
                        $uploadedPaths[]=$target;
                        $rel='assets/uploads/vod-proofs/'.$filename;
                        $img=$pdo->prepare("INSERT INTO content_submission_images(submission_id,file_path,original_name,mime_type,file_size) VALUES(?,?,?,?,?)");
                        $img->execute([$sid,$rel,basename((string)$originalName),'image/webp',$convertedSize]);
                    }
                    if($finfo) finfo_close($finfo);
                }

                sendStreamerWebhook($pdo,$id,'📨 VOD enviada para análise','Sua VOD foi enviada e está aguardando análise da Staff. Nenhum ponto foi concedido ainda.','info',[
                'Data informada'=>date('d/m/Y',strtotime($date)),
                'Collab/Raid'=>$collab?'Sim':'Não',
                'Status'=>'Aguardando análise',
                'Envio'=>'#'.$sid
            ]);
            header('Location: index.php?submission=success');exit;
            } catch (Throwable $e) {
                foreach($uploadedPaths as $path) { if(is_file($path)) @unlink($path); }
                if(!empty($sid)) { try { $pdo->prepare('DELETE FROM content_submissions WHERE id=?')->execute([$sid]); } catch(Throwable $ignore) {} }
                $error=$e->getMessage();
            }
        }
    }
}
$others=$pdo->prepare("SELECT id,name,category FROM streamers WHERE active=1 AND id<>? ORDER BY name");$others->execute([$id]);$others=$others->fetchAll();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Enviar VOD</title><link rel="stylesheet" href="../assets/css/style.css"></head><body class="streamer-area"><main class="container">
<a href="index.php">← Meu painel</a><div class="page-head"><div><p class="eyebrow">CONTEÚDO</p><h1>Enviar VOD</h1><p class="muted">Você informa apenas o link. A Staff confere a VOD, define a data e a duração oficial.</p></div></div>
<?php if($error): ?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form class="form" method="post" enctype="multipart/form-data" id="vod-form">
<label>Link da VOD <span class="muted">(opcional se enviar comprovante)</span></label><input type="url" name="url" placeholder="https://youtube.com/... ou https://twitch.tv/...">
<label>Data informada da live</label><input type="date" name="submission_date" value="<?=date('Y-m-d')?>" required>
<label>⏱️ Duração informada da live <span class="muted">(HH:MM, pode ser corrigida pela Staff)</span></label><input type="text" name="duration_hm" pattern="[0-9]{1,3}:[0-5][0-9]" placeholder="03:04" value="<?=htmlspecialchars($durationHm??'')?>">
<label>📸 Comprovantes da LIVE <span class="muted">(opcional, até 3 imagens)</span></label>
<input type="file" name="proof_images[]" accept="image/jpeg,image/png,image/webp" multiple id="proof-images">
<div id="proof-help" class="muted" style="font-size:12px;margin-top:2px">Envie até 3 prints ou fotos (JPG, PNG ou WEBP), máximo de 5 MB por imagem.</div>
<label class="check-row"><input type="checkbox" name="collab" value="1" id="collab"> <span>Fiz Collab/Raid com outro streamer do Condado (+3 pontos se validado pela Staff)</span></label>
<div id="collab-box" style="display:none"><label>Streamer da Collab/Raid</label><select name="collab_streamer_id"><option value="">Selecione</option><?php foreach($others as $o): ?><option value="<?=$o['id']?>"><?=htmlspecialchars($o['name'])?> — <?=ucfirst($o['category'])?></option><?php endforeach;?></select></div>
<div class="alert">Informe uma duração aproximada da live para ajudar na análise. A Staff poderá corrigir o horário/duração antes de aprovar. Se o envio for recusado, a duração não será validada.</div>
<button class="btn primary">Enviar para análise</button></form>
<script>document.getElementById('vod-form').addEventListener('submit',function(e){const url=document.querySelector('input[name=\"url\"]').value.trim();const files=document.getElementById('proof-images').files;if(!url && !files.length){e.preventDefault();alert('Informe o link da VOD ou envie pelo menos 1 comprovante (foto/print).');return false;}});document.getElementById('proof-images').addEventListener('change',function(){if(this.files.length>3){alert('Você pode enviar no máximo 3 imagens.');this.value='';}});document.getElementById('collab').addEventListener('change',e=>document.getElementById('collab-box').style.display=e.target.checked?'block':'none');</script>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>