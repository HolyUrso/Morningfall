<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';
require_once '../config/vod_points.php';

$id=(int)$_SESSION['streamer_id'];
$prizeId=(int)($_POST['prize_id']??0);
$back='recompensas.php';

function goRedeem(string $msg): void {
    global $back;
    header('Location: '.$back.'?redeem='.urlencode($msg));
    exit;
}

if($_SERVER['REQUEST_METHOD']!=='POST' || $prizeId<=0) goRedeem('Solicitação inválida.');

try{
    $pdo->beginTransaction();

    // Bloqueia o streamer para impedir dois pedidos simultâneos de resgate.
    $st=$pdo->prepare("SELECT id,name,category,points FROM streamers WHERE id=? AND active=1 FOR UPDATE");
    $st->execute([$id]);$streamer=$st->fetch();
    if(!$streamer) throw new RuntimeException('Streamer não encontrado.');

    $cooldownDays=max(0,(int)getSetting($pdo,'redemption_cooldown_days',7));

    // Um único resgate pendente por vez.
    $pending=$pdo->prepare("SELECT COUNT(*) FROM redemptions WHERE streamer_id=? AND status='pending'");
    $pending->execute([$id]);
    if((int)$pending->fetchColumn()>0) throw new RuntimeException('Você já possui um resgate em análise. Aguarde a Staff concluir a análise.');

    // O prazo é contado a partir da aprovação do último resgate.
    if($cooldownDays>0){
        $last=$pdo->prepare("SELECT reviewed_at FROM redemptions WHERE streamer_id=? AND status='approved' AND reviewed_at IS NOT NULL ORDER BY reviewed_at DESC LIMIT 1");
        $last->execute([$id]);
        $lastApproved=$last->fetchColumn();
        if($lastApproved){
            $availableAt=(new DateTime($lastApproved))->modify('+'.$cooldownDays.' days');
            if(new DateTime('now') < $availableAt){
                throw new RuntimeException('Você só poderá solicitar outro resgate a partir de '.$availableAt->format('d/m/Y H:i').'.');
            }
        }
    }

    $st=$pdo->prepare("SELECT * FROM prizes WHERE id=? AND active=1");
    $st->execute([$prizeId]);$prize=$st->fetch();
    if(!$prize) throw new RuntimeException('Recompensa não encontrada.');

    $costColumn='points_'.strtolower((string)$streamer['category']);
    if(!array_key_exists($costColumn,$prize)) throw new RuntimeException('A recompensa não possui pontuação configurada para sua categoria.');

    $cost=(int)$prize[$costColumn];
    if($cost<=0) throw new RuntimeException('Recompensa indisponível para sua categoria.');
    if((int)$streamer['points']<$cost) throw new RuntimeException('Você não possui pontos suficientes.');

    $columns=$pdo->query("SHOW COLUMNS FROM redemptions")->fetchAll();
    $names=array_column($columns,'Field');
    if(!in_array('points_spent',$names,true)) throw new RuntimeException('A tabela de resgates não possui a coluna points_spent. Execute a atualização da V21 e tente novamente.');

    $ins=$pdo->prepare("INSERT INTO redemptions(streamer_id,prize_id,status,points_spent) VALUES(?,?, 'pending', ?)");
    $ins->execute([$id,$prizeId,$cost]);

    $pdo->commit();
    goRedeem('success');
}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    goRedeem('Erro ao solicitar o resgate: '.$e->getMessage());
}
