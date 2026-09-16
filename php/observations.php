<?php
require_once __DIR__.'/bootstrap.php';
$u=login_required();

// Cria a tabela de respostas automaticamente no banco existente.
$pdo->exec("CREATE TABLE IF NOT EXISTS notification_replies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notification_id INT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reply_notification FOREIGN KEY(notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
  CONSTRAINT fk_reply_admin FOREIGN KEY(admin_id) REFERENCES users(id),
  INDEX idx_reply_notification(notification_id,created_at)
) ENGINE=InnoDB");

$method=$_SERVER['REQUEST_METHOD'];
$d=input();
$action=$method==='GET'?'list':($d['action']??'');

if($action==='list'){
  $sql='SELECT n.*,u.name user_name,r.start_at,r.end_at,r.purpose,rm.name room_name,eq.name equipment_name
        FROM notifications n
        JOIN users u ON u.id=n.user_id
        LEFT JOIN reservations r ON r.id=n.reservation_id
        LEFT JOIN rooms rm ON rm.id=r.room_id
        LEFT JOIN equipment eq ON eq.id=r.equipment_id';
  $args=[];
  if($u['role']!=='ADMIN'){ $sql.=' WHERE n.user_id=?'; $args[]=$u['id']; }
  $sql.=' ORDER BY n.created_at DESC';
  $st=$pdo->prepare($sql); $st->execute($args); $items=$st->fetchAll();
  $rs=$pdo->prepare('SELECT rr.id,rr.notification_id,rr.admin_id,au.name admin_name,rr.message,rr.created_at
                     FROM notification_replies rr JOIN users au ON au.id=rr.admin_id
                     WHERE rr.notification_id=? ORDER BY rr.created_at ASC');
  foreach($items as &$item){ $rs->execute([(int)$item['id']]); $item['replies']=$rs->fetchAll(); }
  unset($item);
  json_out(['ok'=>true,'items'=>$items]);
}

if($action==='save'){
  if($u['role']!=='USER') json_out(['ok'=>false,'error'=>'Apenas usuários podem criar reportes por esta tela.'],403);
  $msg=trim((string)($d['message']??'')); $reservationId=(int)($d['reservation_id']??0);
  if(!$msg) json_out(['ok'=>false,'error'=>'Escreva o relato.'],422);
  if(!$reservationId) json_out(['ok'=>false,'error'=>'Vincule o reporte a uma reserva.'],422);
  $st=$pdo->prepare('SELECT id,user_id FROM reservations WHERE id=?'); $st->execute([$reservationId]); $reservation=$st->fetch();
  if(!$reservation) json_out(['ok'=>false,'error'=>'Reserva não encontrada.'],404);
  if((int)$reservation['user_id']!==(int)$u['id']) json_out(['ok'=>false,'error'=>'Você só pode reportar sobre suas próprias reservas.'],403);
  $st=$pdo->prepare('INSERT INTO notifications(user_id,reservation_id,message) VALUES(?,?,?)');
  $st->execute([$u['id'],$reservationId,$msg]); $id=(int)$pdo->lastInsertId();
  audit_log($pdo,'CREATE','notifications',$id,'Reporte criado e vinculado à reserva #'.$reservationId);
  json_out(['ok'=>true,'id'=>$id]);
}

if($action==='reply'){
  $u=admin_required();
  $notificationId=(int)($d['notification_id']??0); $msg=trim((string)($d['message']??''));
  if(!$notificationId) json_out(['ok'=>false,'error'=>'Reporte inválido.'],422);
  if(!$msg) json_out(['ok'=>false,'error'=>'Escreva a resposta.'],422);
  $st=$pdo->prepare('SELECT id FROM notifications WHERE id=?'); $st->execute([$notificationId]);
  if(!$st->fetch()) json_out(['ok'=>false,'error'=>'Reporte não encontrado.'],404);
  $st=$pdo->prepare('INSERT INTO notification_replies(notification_id,admin_id,message) VALUES(?,?,?)');
  $st->execute([$notificationId,$u['id'],$msg]); $id=(int)$pdo->lastInsertId();
  audit_log($pdo,'CREATE','notification_replies',$id,'Resposta do administrador ao reporte #'.$notificationId);
  json_out(['ok'=>true,'id'=>$id]);
}

if($action==='close'){
  admin_required(); $id=(int)($d['id']??0);
  $st=$pdo->prepare("UPDATE notifications SET status='CLOSED' WHERE id=?"); $st->execute([$id]);
  audit_log($pdo,'UPDATE','notifications',$id,'Reporte encerrado'); json_out(['ok'=>true]);
}

if($action==='delete'){
  $id=(int)($d['id']??0); $st=$pdo->prepare('DELETE FROM notifications WHERE id=? AND user_id=?'); $st->execute([$id,$u['id']]);
  audit_log($pdo,'DELETE','notifications',$id,'Reporte excluído'); json_out(['ok'=>true]);
}

json_out(['ok'=>false,'error'=>'Ação inválida.'],400);
