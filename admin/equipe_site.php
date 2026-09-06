<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

if(($_SESSION['staff_role']??'')!=='master'){
    http_response_code(403); exit('Acesso restrito ao Staff Master.');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS site_team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    role_title VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    discord_id VARCHAR(32) NULL,
    discord_avatar VARCHAR(700) NULL,
    member_type ENUM('leadership','collaborator') NOT NULL DEFAULT 'collaborator',
    position INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_site_team_type_position(member_type, position, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$count=(int)$pdo->query("SELECT COUNT(*) FROM site_team_members WHERE member_type='leadership'")->fetchColumn();
if($count===0){
    $seed=$pdo->prepare("INSERT INTO site_team_members(name,role_title,description,member_type,position,active) VALUES(?,?,?,?,?,1)");
    $seed->execute(['Jessy','CEO','Idealizadora do Condado Carmesim','leadership',1]);
    $seed->execute(['Luci','CEO','Idealizador do Condado Carmesim','leadership',2]);
    $seed->execute(['Matheus','COO','Parte da liderança e construção do projeto','leadership',3]);
}

$msg='';$error='';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    try{
        if($action==='save_leadership'){
            $ids=$_POST['id']??[];$names=$_POST['name']??[];$roles=$_POST['role_title']??[];$desc=$_POST['description']??[];$discord=$_POST['discord_id']??[];$avatars=$_POST['discord_avatar']??[];
            foreach($ids as $i=>$id){
                $id=(int)$id; $name=trim($names[$i]??''); $role=trim($roles[$i]??'CEO');
                if($id<=0||$name==='') continue;
                $st=$pdo->prepare("UPDATE site_team_members SET name=?,role_title=?,description=?,discord_id=?,discord_avatar=?,member_type='leadership',position=? WHERE id=?");
                $st->execute([$name,$role,trim($desc[$i]??''),trim($discord[$i]??'')?:null,trim($avatars[$i]??'')?:null,$i+1,$id]);
            }
            auditLog($pdo,'Equipe do site alterada','Equipe','0','Liderança','Dados da liderança principal atualizados.');
            $msg='Liderança atualizada com sucesso.';
        }elseif($action==='create'){
            $name=trim($_POST['name']??'');$role=trim($_POST['role_title']??'');$description=trim($_POST['description']??'');$discord=trim($_POST['discord_id']??'');$avatar=trim($_POST['discord_avatar']??'');$position=(int)($_POST['position']??0);
            if($name===''||$role==='') throw new RuntimeException('Nome e função são obrigatórios.');
            if($position<=0) $position=(int)$pdo->query("SELECT COALESCE(MAX(position),0)+1 FROM site_team_members WHERE member_type='collaborator'")->fetchColumn();
            $st=$pdo->prepare("INSERT INTO site_team_members(name,role_title,description,discord_id,discord_avatar,member_type,position,active) VALUES(?,?,?,?,?,'collaborator',?,1)");
            $st->execute([$name,$role,$description,$discord?:null,$avatar?:null,$position]);
            $new=(int)$pdo->lastInsertId();
            auditLog($pdo,'Colaborador do site criado','Equipe',$new,$name,'Novo colaborador cadastrado.');
            $msg='Colaborador cadastrado com sucesso.';
        }elseif($action==='edit'){
            $id=(int)($_POST['id']??0);$name=trim($_POST['name']??'');$role=trim($_POST['role_title']??'');$description=trim($_POST['description']??'');$discord=trim($_POST['discord_id']??'');$avatar=trim($_POST['discord_avatar']??'');$position=(int)($_POST['position']??1);
            if($id<=0||$name===''||$role==='') throw new RuntimeException('Nome e função são obrigatórios.');
            $st=$pdo->prepare("UPDATE site_team_members SET name=?,role_title=?,description=?,discord_id=?,discord_avatar=?,position=? WHERE id=? AND member_type='collaborator'");
            $st->execute([$name,$role,$description,$discord?:null,$avatar?:null,$position,$id]);
            auditLog($pdo,'Colaborador do site alterado','Equipe',$id,$name,'Dados do colaborador alterados.');
            $msg='Colaborador atualizado com sucesso.';
        }elseif($action==='toggle'){
            $id=(int)($_POST['id']??0);$st=$pdo->prepare("UPDATE site_team_members SET active=IF(active=1,0,1) WHERE id=? AND member_type='collaborator'");$st->execute([$id]);$msg='Status do colaborador atualizado.';
        }elseif($action==='delete'){
            $id=(int)($_POST['id']??0);$st=$pdo->prepare("DELETE FROM site_team_members WHERE id=? AND member_type='collaborator'");$st->execute([$id]);$msg=$st->rowCount()?'Colaborador removido.':'Colaborador não encontrado.';
        }
    }catch(Throwable $ex){$error=$ex->getMessage();}
}

$leaders=$pdo->query("SELECT * FROM site_team_members WHERE member_type='leadership' ORDER BY position ASC,id ASC LIMIT 3")->fetchAll();
$collabs=$pdo->query("SELECT * FROM site_team_members WHERE member_type='collaborator' ORDER BY active DESC,position ASC,name ASC")->fetchAll();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Equipe do Site · Carmesim</title><link rel="stylesheet" href="../assets/css/style.css"><script src="../assets/js/discord-avatar.js" defer></script>
<style>
.team-admin-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.team-admin-card{background:#111722;border:1px solid #35415a;border-radius:14px;padding:18px}.team-admin-card h3{margin:0 0 14px}.avatar-preview{width:76px;height:76px;border-radius:50%;object-fit:cover;border:2px solid #6b5520;margin-top:8px}.form-actions{display:flex;gap:8px;flex-wrap:wrap}.muted-small{font-size:12px;color:#8391aa}.collab-grid{display:grid;gap:10px}.collab-row{display:grid;grid-template-columns:64px 1fr 1fr 1.5fr auto;gap:12px;align-items:center;padding:12px;border:1px solid #273247;background:#111722;border-radius:12px}.collab-row img{width:48px;height:48px;border-radius:50%;object-fit:cover}.status-off{opacity:.55}.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.72);display:flex;align-items:center;justify-content:center;z-index:9999;padding:20px}.modal-card{width:min(680px,100%);max-height:90vh;overflow:auto;background:#111722;border:1px solid #35415a;border-radius:16px;padding:22px}.modal-close{float:right;border:0;background:transparent;color:#fff;font-size:26px;cursor:pointer}@media(max-width:900px){.team-admin-grid{grid-template-columns:1fr}.collab-row{grid-template-columns:56px 1fr}.collab-row>*:not(img):not(.collab-main){grid-column:2}}
</style></head><body><header class="topbar"><div><b>CARMESIM</b> <span>CREATORS</span></div><div><?=e($_SESSION['staff_name']??'')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Dashboard</a><div class="page-head"><div><p class="eyebrow">SITE PÚBLICO</p><h1>👥 Equipe do Site</h1><p class="muted">Gerencie a liderança principal e os colaboradores exibidos na página Sobre.</p></div></div>
<?php if($msg):?><div class="alert success"><?=e($msg)?></div><?php endif;?><?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<section class="panel"><div class="section-title"><div><p class="eyebrow">LIDERANÇA</p><h2>CEO Jessy · CEO Luci · COO Matheus</h2></div><span class="setup-badge">MASTER</span></div>
<p class="muted-small">Informe o ID do Discord de cada líder e use Buscar avatar. O avatar aparecerá automaticamente na página Sobre.</p>
<form method="post"><input type="hidden" name="action" value="save_leadership"><div class="team-admin-grid">
<?php foreach($leaders as $i=>$l): ?><div class="team-admin-card"><h3><?=e($l['name'])?></h3><input type="hidden" name="id[]" value="<?=$l['id']?>"><label>Nome</label><input name="name[]" value="<?=e($l['name'])?>" required><label>Função</label><input name="role_title[]" value="<?=e($l['role_title'])?>" required><label>Descrição</label><input name="description[]" value="<?=e($l['description'])?>"><label>ID do Discord</label><div class="field-with-action"><input id="lead_id_<?=$l['id']?>" name="discord_id[]" value="<?=e($l['discord_id'])?>" inputmode="numeric"><button type="button" class="btn secondary" id="lead_btn_<?=$l['id']?>" onclick="buscarAvatarDiscord({idInput:'lead_id_<?=$l['id']?>',avatarInput:'lead_avatar_<?=$l['id']?>',buttonId:'lead_btn_<?=$l['id']?>',previewId:'lead_preview_<?=$l['id']?>',statusId:'lead_status_<?=$l['id']?>',type:'site_team',entityId:'<?=$l['id']?>'})">🔎 Buscar avatar</button></div><label>Avatar</label><input id="lead_avatar_<?=$l['id']?>" name="discord_avatar[]" value="<?=e($l['discord_avatar'])?>" type="url"><div><img id="lead_preview_<?=$l['id']?>" class="avatar-preview" src="<?=e($l['discord_avatar'])?>" style="<?=empty($l['discord_avatar'])?'display:none':''?>"></div><small id="lead_status_<?=$l['id']?>" class="muted-small"><?=empty($l['discord_avatar'])?'Digite o ID do Discord para buscar o avatar.':'Avatar cadastrado.'?></small></div><?php endforeach; ?></div><div class="form-actions" style="margin-top:16px"><button class="btn primary">Salvar liderança</button></div></form></section>
<section class="panel" style="margin-top:18px"><div class="section-title"><div><p class="eyebrow">COLABORADORES</p><h2>Equipe abaixo da liderança</h2></div><button type="button" class="btn primary" onclick="openCreate()">+ Cadastrar colaborador</button></div><div class="collab-grid">
<?php if(!$collabs): ?><p class="muted">Nenhum colaborador cadastrado ainda.</p><?php endif; ?>
<?php foreach($collabs as $c): ?><div class="collab-row <?=!$c['active']?'status-off':''?>"><div><?php if($c['discord_avatar']):?><img src="<?=e($c['discord_avatar'])?>" alt="Avatar"><?php else:?><div style="width:48px;height:48px;border-radius:50%;background:#273247;display:grid;place-items:center">👤</div><?php endif;?></div><div class="collab-main"><strong><?=e($c['name'])?></strong><div class="muted-small"><?=e($c['role_title'])?></div></div><div><?=e($c['description'])?></div><div><span class="muted-small">Posição: <?=e($c['position'])?> · <?= $c['active']?'Ativo':'Inativo' ?></span></div><div><button type="button" class="btn small-btn" onclick='openEdit(<?=json_encode($c,JSON_HEX_APOS|JSON_HEX_QUOT)?>)'>✏️</button><form method="post" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn small-btn" type="submit"><?=$c['active']?'Desativar':'Ativar'?></button></form><form method="post" style="display:inline" onsubmit="return confirm('Remover este colaborador?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn small-btn delete-btn">Excluir</button></form></div></div><?php endforeach; ?></div></section>
</main><script>
function openCreate(){document.getElementById('teamModal').style.display='flex';document.getElementById('modalTitle').textContent='Cadastrar colaborador';document.getElementById('teamForm').reset();document.getElementById('teamAction').value='create';document.getElementById('teamId').value='';document.getElementById('teamPreview').style.display='none';document.getElementById('teamStatus').textContent='Digite o ID do Discord para buscar o avatar.';}
function openEdit(x){document.getElementById('teamModal').style.display='flex';document.getElementById('modalTitle').textContent='Editar colaborador';document.getElementById('teamAction').value='edit';document.getElementById('teamId').value=x.id;document.getElementById('teamName').value=x.name||'';document.getElementById('teamRole').value=x.role_title||'';document.getElementById('teamDescription').value=x.description||'';document.getElementById('teamDiscord').value=x.discord_id||'';document.getElementById('teamAvatar').value=x.discord_avatar||'';document.getElementById('teamPosition').value=x.position||1;if(x.discord_avatar){document.getElementById('teamPreview').src=x.discord_avatar;document.getElementById('teamPreview').style.display='inline-block';}else document.getElementById('teamPreview').style.display='none';}
function closeTeam(){document.getElementById('teamModal').style.display='none';}
</script><div class="modal-backdrop" id="teamModal" style="display:none"><div class="modal-card"><button class="modal-close" onclick="closeTeam()">×</button><h2 id="modalTitle">Cadastrar colaborador</h2><form method="post" id="teamForm"><input type="hidden" id="teamAction" name="action" value="create"><input type="hidden" id="teamId" name="id"><label>Nome</label><input id="teamName" name="name" required><label>Função / cargo</label><input id="teamRole" name="role_title" required placeholder="Ex.: Designer"><label>Descrição</label><input id="teamDescription" name="description" placeholder="O que essa pessoa faz no projeto"><label>Posição</label><input id="teamPosition" name="position" type="number" min="1" value="1"><label>ID do Discord</label><div class="field-with-action"><input id="teamDiscord" name="discord_id" inputmode="numeric"><button type="button" class="btn secondary" id="teamAvatarBtn" onclick="buscarAvatarDiscord({idInput:'teamDiscord',avatarInput:'teamAvatar',buttonId:'teamAvatarBtn',previewId:'teamPreview',statusId:'teamStatus',type:'site_team'})">🔎 Buscar avatar</button></div><label>Avatar do Discord</label><input id="teamAvatar" name="discord_avatar" type="url"><div><img id="teamPreview" class="avatar-preview" style="display:none" alt="Avatar"></div><small id="teamStatus" class="muted-small">Digite o ID do Discord para buscar o avatar.</small><div class="form-actions" style="margin-top:16px"><button class="btn primary">Salvar</button><button type="button" class="btn" onclick="closeTeam()">Cancelar</button></div></form></div></div></body></html>
