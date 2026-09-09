<?php
require_once __DIR__.'/bootstrap.php';
$u=auth_required();
$method=$_SERVER['REQUEST_METHOD'];
$d=json_decode(file_get_contents('php://input'),true) ?: [];
$action=$method==='GET'?'list':($d['action']??'');
if($action==='list'){
  $sql='SELECT n.*,u.name user_name,r.start_at,r.end_at,r.purpose,rm.name room_name,eq.name equipment_name FROM notifications n JOIN users u ON u.id=n.user_id LEFT JOIN reservations r ON r.id=n.reservation_id LEFT JOIN rooms rm ON rm.id=r.room_id LEFT JOIN equipment eq ON eq.id=r.equipment_id';
  $args=[];
  if($u['role']!=='ADMIN'){$sql.=' WHERE n.user_id=?';$args[]=$u['id'];}
  $sql.=' ORDER BY n.created_at DESC';
  $s=$pdo->prepare($sql);$s->execute($args);json_out(['ok'=>true,'items'=>$s->fetchAll()]);
}
if($action==='save'){
  $msg=trim((string)($d['message']??''));$reservationId=(int)($d['reservation_id']??0);
  if(!$msg)json_out(['ok'=>false,'error'=>'Escreva o relato.'],422);
  if(!$reservationId)json_out(['ok'=>false,'error'=>'Vincule o reporte a uma reserva.'],422);
  $s=$pdo->prepare('SELECT id,user_id FROM reservations WHERE id=?');$s->execute([$reservationId]);$reservation=$s->fetch();
  if(!$reservation)json_out(['ok'=>false,'error'=>'Reserva não encontrada.'],404);
  if($u['role']!=='ADMIN' && (int)$reservation['user_id']!==(int)$u['id'])json_out(['ok'=>false,'error'=>'Você só pode reportar sobre suas próprias reservas.'],403);
  $s=$pdo->prepare('INSERT INTO notifications(user_id,reservation_id,message) VALUES(?,?,?)');$s->execute([$u['id'],$reservationId,$msg]);
  $id=(int)$pdo->lastInsertId();audit_log($pdo,'CREATE','notifications',$id,'Reporte criado e vinculado à reserva #'.$reservationId);json_out(['ok'=>true,'id'=>$id]);
}
if($action==='delete'){$id=(int)($d['id']??0);$s=$pdo->prepare('DELETE FROM notifications WHERE id=? AND user_id=?');$s->execute([$id,$u['id']]);audit_log($pdo,'DELETE','notifications',$id,'Reporte excluído');json_out(['ok'=>true]);}
if($action==='close'){admin_required();$id=(int)($d['id']??0);$s=$pdo->prepare("UPDATE notifications SET status='CLOSED' WHERE id=?");$s->execute([$id]);audit_log($pdo,'UPDATE','notifications',$id,'Reporte encerrado');json_out(['ok'=>true]);}
json_out(['ok'=>false,'error'=>'Ação inválida.'],400);
