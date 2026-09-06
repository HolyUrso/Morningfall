<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

if(($_SESSION['staff_role']??'')!=='master'){
    try {
        $roleQ=$pdo->prepare("SELECT role FROM staff_users WHERE id=? AND active=1 LIMIT 1");
        $roleQ->execute([(int)$_SESSION['staff_id']]);
        if($roleQ->fetchColumn()==='master') $_SESSION['staff_role']='master';
    } catch(Throwable $e) {}
}
if(($_SESSION['staff_role']??'')!=='master'){
    http_response_code(403); exit('Acesso restrito ao Staff Master.');
}
$msg='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';

    if($action==='create'){
        $name=trim($_POST['name']??'');$username=trim($_POST['username']??'');
        $password=$_POST['password']??'';$role=$_POST['role']??'staff';
        $discord=trim($_POST['discord_id']??'');$avatar=trim($_POST['discord_avatar']??'');
        if($name===''||$username===''||strlen($password)<6){$error='Nome, usuário e senha (mínimo 6 caracteres) são obrigatórios.';}
        elseif(!in_array($role,['master','staff'],true)){$error='Permissão inválida.';}
        else{
            try{
                $st=$pdo->prepare("INSERT INTO staff_users(name,username,password_hash,role,discord_id,discord_avatar) VALUES(?,?,?,?,?,?)");
                $st->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role,$discord!==''?$discord:null,$avatar!==''?$avatar:null]);
                $newStaffId=(int)$pdo->lastInsertId();
                auditLog($pdo,'Staff criada','Staff',$newStaffId,$name,'Novo usuário Staff criado.',null,['username'=>$username,'role'=>$role]);
                $msg='Staff cadastrada com sucesso.';
            }catch(PDOException $e){$error=$e->getCode()==='23000'?'Esse usuário já existe.':'Não foi possível cadastrar a Staff.';}
        }
    }elseif($action==='edit'){
        $id=(int)($_POST['id']??0);
        $name=trim($_POST['name']??'');$username=trim($_POST['username']??'');
        $password=$_POST['password']??'';$role=$_POST['role']??'staff';
        $discord=trim($_POST['discord_id']??'');$avatar=trim($_POST['discord_avatar']??'');
        if($id<=0 || $name==='' || $username==='') $error='Nome e usuário são obrigatórios.';
        elseif(!in_array($role,['master','staff'],true)) $error='Permissão inválida.';
        elseif($id===(int)$_SESSION['staff_id'] && $role!=='master') $error='Você não pode retirar a própria permissão Master.';
        else{
            try{
                if($password!==''){
                    if(strlen($password)<6) throw new RuntimeException('A nova senha precisa ter pelo menos 6 caracteres.');
                    $st=$pdo->prepare("UPDATE staff_users SET name=?,username=?,password_hash=?,role=?,discord_id=?,discord_avatar=? WHERE id=?");
                    $st->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role,$discord!==''?$discord:null,$avatar!==''?$avatar:null,$id]);
                    auditLog($pdo,'Staff alterada','Staff',$id,$name,'Dados/permissão da Staff alterados.',null,['username'=>$username,'role'=>$role,'password_changed'=>true]);
                }else{
                    $st=$pdo->prepare("UPDATE staff_users SET name=?,username=?,role=?,discord_id=?,discord_avatar=? WHERE id=?");
                    $st->execute([$name,$username,$role,$discord!==''?$discord:null,$avatar!==''?$avatar:null,$id]);
                    auditLog($pdo,'Staff alterada','Staff',$id,$name,'Dados/permissão da Staff alterados.',null,['username'=>$username,'role'=>$role,'password_changed'=>false]);
                }
                if($id===(int)$_SESSION['staff_id']){
                    $_SESSION['staff_name']=$name; $_SESSION['staff_role']=$role; $_SESSION['staff_discord_avatar']=$avatar;
                }
                $msg='Staff atualizada com sucesso.';
            }catch(PDOException $e){$error=$e->getCode()==='23000'?'Esse usuário já existe.':'Não foi possível atualizar a Staff.';}
            catch(Throwable $e){$error=$e->getMessage();}
        }
    }elseif($action==='toggle'){
        $id=(int)$_POST['id'];
        if($id===(int)$_SESSION['staff_id']){$error='Você não pode inativar seu próprio usuário.';}
        else{$st=$pdo->prepare("UPDATE staff_users SET active=IF(active=1,0,1) WHERE id=?");$st->execute([$id]);
            $targetQ=$pdo->prepare('SELECT name,active FROM staff_users WHERE id=? LIMIT 1');$targetQ->execute([$id]);$target=$targetQ->fetch();
            auditLog($pdo,'Status da Staff alterado','Staff',$id,$target['name']??'','Status de acesso alterado.');
            $msg='Status atualizado.';}
    }elseif($action==='delete'){
        $id=(int)$_POST['id'];
        if($id===(int)$_SESSION['staff_id']){
            $error='Você não pode excluir seu próprio usuário.';
        }else{
            try{
                $st=$pdo->prepare("DELETE FROM staff_users WHERE id=?");
                $st->execute([$id]);
                auditLog($pdo,'Staff excluída','Staff',$id,'','Usuário Staff excluído permanentemente.');
                $msg=$st->rowCount()?'Staff excluída permanentemente.':'Staff não encontrada.';
            }catch(PDOException $e){
                $error='Não foi possível excluir esta Staff porque existem registros relacionados. Use "Inativar" para preservar o histórico.';
            }
        }
    }
}

$rows=$pdo->query("SELECT id,name,username,role,active,discord_id,discord_avatar,created_at FROM staff_users ORDER BY role='master' DESC,name ASC")->fetchAll();
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Equipe Staff · Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css"><script src="../assets/js/discord-avatar.js" defer></script>
<style>.staff-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px;border:1px solid #39455e}.staff-inline{display:flex;align-items:center}.role-master{color:#cbb4ff}.role-staff{color:#9fb1cc}.avatar-help{font-size:12px;color:#8391aa}</style>
<style>
.staff-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px;border:1px solid #39455e}
.staff-inline{display:flex;align-items:center}.role-master{color:#cbb4ff}.role-staff{color:#9fb1cc}.avatar-help{font-size:12px;color:#8391aa}
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.72);display:flex;align-items:center;justify-content:center;z-index:9999;padding:20px}
.modal-card{width:min(620px,100%);max-height:90vh;overflow:auto;background:#111722;border:1px solid #35415a;border-radius:16px;padding:22px;box-shadow:0 20px 80px rgba(0,0,0,.5)}
.modal-close{border:0;background:transparent;color:#fff;font-size:28px;cursor:pointer}.edit-btn{margin-right:5px}.delete-btn{background:#571b25!important;color:#ffb5bf!important;border-color:#7c2b38!important;margin-right:5px}
.small-btn{font-size:12px;padding:7px 10px;margin-bottom:4px}
</style>
</head>
<body><header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=e($_SESSION['staff_name']??'')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Dashboard</a>
<div class="page-head"><div><p class="eyebrow">ADMINISTRAÇÃO</p><h1>👥 Equipe Staff</h1><p class="muted">Cadastre Staff, defina a permissão e associe o avatar do Discord.</p></div></div>
<?php if($msg):?><div class="alert success"><?=e($msg)?></div><?php endif;?><?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<section class="panel"><div class="section-title"><div><p class="eyebrow">NOVO USUÁRIO</p><h2>Cadastrar Staff</h2></div><span class="setup-badge">MASTER</span></div>
<form class="form" method="post"><input type="hidden" name="action" value="create">
<label>Nome</label><input name="name" required placeholder="Nome da Staff">
<label>Usuário de login</label><input name="username" required placeholder="usuario.staff">
<label>Senha</label><input type="password" name="password" minlength="6" required placeholder="Mínimo 6 caracteres">
<label>Permissão</label><select name="role"><option value="staff">Staff</option><option value="master">Master</option></select>
<div class="discord-avatar-box">
<label>ID do Discord</label>
<div class="field-with-action"><input id="create_discord_id" name="discord_id" inputmode="numeric" placeholder="ID numérico do Discord"><button type="button" class="btn secondary" id="create_avatar_btn" onclick="buscarAvatarDiscord({idInput:'create_discord_id',avatarInput:'create_discord_avatar',buttonId:'create_avatar_btn',previewId:'create_avatar_preview',statusId:'create_avatar_status',type:'staff'})">🔎 Buscar avatar</button></div>
<label>Avatar do Discord</label><input type="url" id="create_discord_avatar" name="discord_avatar" placeholder="Preenchido automaticamente">
<div><img id="create_avatar_preview" class="discord-avatar-preview" style="display:none" alt="Avatar"></div>
<small id="create_avatar_status" class="avatar-status">Digite o ID e clique em Buscar avatar.</small>
</div>
<p class="avatar-help">Se o BOT do Discord estiver configurado em <code>config/discord.php</code>, o avatar poderá ser buscado pelo ID depois do cadastro.</p>
<div class="actions"><button class="btn primary">+ Cadastrar Staff</button></div></form></section>
<section class="panel" style="margin-top:18px"><div class="section-title"><div><p class="eyebrow">EQUIPE</p><h2>Staff cadastrada</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Staff</th><th>Usuário</th><th>Discord</th><th>Permissão</th><th>Status</th><th>Ação</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><div class="staff-inline"><?php if($r['discord_avatar']):?><img class="staff-avatar" src="<?=e($r['discord_avatar'])?>" alt="Avatar"><?php endif;?><strong><?=e($r['name'])?></strong></div></td><td><?=e($r['username'])?></td><td><?=e($r['discord_id']??'—')?></td><td class="<?=e($r['role']==='master'?'role-master':'role-staff')?>"><?=e(ucfirst($r['role']))?></td><td><?=$r['active']?'Ativo':'Inativo'?></td><td>
<?php
$editData=[
'id'=>(int)$r['id'],'name'=>$r['name'],'username'=>$r['username'],'role'=>$r['role'],
'discord_id'=>$r['discord_id']??'','discord_avatar'=>$r['discord_avatar']??''
];
?>
<button type="button" class="btn small-btn edit-btn" onclick='openEdit(<?=json_encode($editData,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>)'>✏️ Editar</button>
<?php if((int)$r['id']!==(int)$_SESSION['staff_id']):?>
<form method="post" style="display:inline" onsubmit="return confirm('Tem certeza que deseja excluir permanentemente esta Staff?')">
<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>">
<button class="btn small-btn delete-btn">Excluir</button>
</form>
<form method="post" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$r['id']?>">
<button class="btn small-btn <?=$r['active']?'danger-btn':'success-btn'?>"><?=$r['active']?'Inativar':'Ativar'?></button></form>
<?php else:?><span class="muted">Você</span><?php endif;?>
</td></tr><?php endforeach;?></tbody></table></div>
</section>

<div id="editModal" class="modal-backdrop" style="display:none">
  <div class="modal-card">
    <div class="section-title"><div><p class="eyebrow">EDITAR</p><h2>Editar Staff</h2></div><button type="button" class="modal-close" onclick="closeEdit()">×</button></div>
    <form method="post" class="form">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <label>Nome</label><input name="name" id="edit_name" required>
      <label>Usuário de login</label><input name="username" id="edit_username" required>
      <label>Nova senha <span class="muted">(deixe em branco para manter)</span></label><input type="password" name="password" minlength="6">
      <label>Permissão</label><select name="role" id="edit_role"><option value="staff">Staff</option><option value="master">Master</option></select>
      <div class="discord-avatar-box">
<label>ID do Discord</label>
<div class="field-with-action"><input id="edit_discord_id" name="discord_id" inputmode="numeric"><button type="button" class="btn secondary" id="edit_avatar_btn" onclick="buscarAvatarDiscord({idInput:'edit_discord_id',avatarInput:'edit_discord_avatar',buttonId:'edit_avatar_btn',previewId:'edit_avatar_preview',statusId:'edit_avatar_status',type:'staff',entityId:document.getElementById('edit_id').value})">🔎 Buscar avatar</button></div>
<label>Avatar do Discord</label><input type="url" id="edit_discord_avatar" name="discord_avatar" placeholder="Preenchido automaticamente">
<div><img id="edit_avatar_preview" class="discord-avatar-preview" style="display:none" alt="Avatar"></div>
<small id="edit_avatar_status" class="avatar-status">Digite o ID e clique em Buscar avatar.</small>
</div>
      <div class="actions"><button class="btn primary">💾 Salvar alterações</button><button type="button" class="btn" onclick="closeEdit()">Cancelar</button></div>
    </form>
  </div>
</div>

<script>
function openEdit(data){
  document.getElementById('edit_id').value=data.id||'';
  document.getElementById('edit_name').value=data.name||'';
  document.getElementById('edit_username').value=data.username||'';
  document.getElementById('edit_role').value=data.role||'staff';
  document.getElementById('edit_discord_id').value=data.discord_id||'';
  document.getElementById('edit_discord_avatar').value=data.discord_avatar||''; const ep=document.getElementById('edit_avatar_preview'); ep.src=data.discord_avatar||''; ep.style.display=data.discord_avatar?'inline-block':'none';
  document.getElementById('editModal').style.display='flex';
}
function closeEdit(){document.getElementById('editModal').style.display='none';}
</script>
</section>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
