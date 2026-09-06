<?php
require_once '../config/auth.php';
require_once '../config/database.php';

if(($_SESSION['staff_role'] ?? '') !== 'master'){
    http_response_code(403);
    exit('Acesso restrito ao Staff Master.');
}

// Relatorio PDF simples, sem dependencia de bibliotecas externas.
// Inclui todas as empresas cadastradas e os dados administrativos.
$rows=$pdo->query('SELECT photo,category,name,description,responsible,available,position,active,created_at,updated_at FROM site_companies ORDER BY position ASC,name ASC')->fetchAll(PDO::FETCH_ASSOC);

function pdfEscape($s){
    $s=iconv('UTF-8','Windows-1252//TRANSLIT',$s);
    $s=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s);
    return $s;
}
function wrapText($text,$max=92){
    $text=preg_replace('/\\s+/',' ',trim((string)$text));
    if($text==='') return ['—'];
    return str_split($text,$max); // visual ASCII/Windows-1252 safe after transliteration
}

$lines=[];
$lines[]='CARMESIM CREATORS - RELATORIO DE EMPRESAS';
$lines[]='Gerado em '.date('d/m/Y H:i').' | Total: '.count($rows).' empresas';
$lines[]='';

foreach($rows as $i=>$r){
    $status=((int)$r['available']===1)?'EMPRESA DISPONIVEL':'NAO DISPONIVEL';
    $lines[]='Empresa '.($i+1).' - '.($r['name']?:'Sem nome');
    $lines[]='Categoria: '.($r['category']?:'—');
    $lines[]='Status: '.$status;
    if((int)$r['available']===0) $lines[]='Responsavel (interno): '.($r['responsible']?:'Nao informado');
    $lines[]='Posicao: '.(int)$r['position'];
    $lines[]='Ativa no portal: '.((int)$r['active']===1?'Sim':'Nao');
    $lines[]='Foto: '.($r['photo']?:'Nao cadastrada');
    foreach(wrapText('Descricao: '.($r['description']?:'Sem descricao cadastrada.')) as $x) $lines[]=$x;
    $lines[]='';
}

// Build a small valid PDF using standard Helvetica. Text is transliterated to Windows-1252.
$pages=[]; $perPage=46;
for($offset=0;$offset<count($lines);$offset+=$perPage) $pages[]=array_slice($lines,$offset,$perPage);
if(!$pages) $pages=[['Nenhuma empresa cadastrada.']];

$objects=[]; $kids=[];
$objects[]='<< /Type /Catalog /Pages 2 0 R >>';
$objects[]='<< /Type /Pages /Kids [] /Count 0 >>';
$fontObj=3; $objects[]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
$next=4;
foreach($pages as $page){
    $content="BT\n/F1 10 Tf\n40 800 Td\n";
    $first=true;
    foreach($page as $line){
        if(!$first) $content.="0 -16 Td\n"; $first=false;
        $content.='('.pdfEscape($line).') Tj' . "\n";
    }
    $content.="ET\n";
    $streamObj=$next++; $pageObj=$next++;
    $objects[$streamObj-1]='<< /Length '.strlen($content).' >>\nstream\n'.$content.'endstream';
    $objects[$pageObj-1]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$fontObj.' 0 R >> >> /Contents '.$streamObj.' 0 R >>';
    $kids[]=$pageObj.' 0 R';
}
$objects[1]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';

$pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=[0];
foreach($objects as $n=>$obj){
    $num=$n+1; $offsets[$num]=strlen($pdf); $pdf.=$num." 0 obj\n".$obj."\nendobj\n";
}
$xref=strlen($pdf); $count=count($objects)+1;
$pdf.="xref\n0 ".$count."\n0000000000 65535 f \n";
for($i=1;$i<$count;$i++) $pdf.=sprintf("%010d 00000 n \n",$offsets[$i]);
$pdf.="trailer\n<< /Size ".$count." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="relatorio_empresas_carmesim.pdf"');
header('Content-Length: '.strlen($pdf));
echo $pdf;
