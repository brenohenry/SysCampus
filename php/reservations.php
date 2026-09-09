<?php
require __DIR__.'/bootstrap.php';
$u=login_required();$method=$_SERVER['REQUEST_METHOD'];$d=$method==='GET'?$_GET:input();$action=$d['action']??'list';
if($method==='GET'||$action==='list'){
 $sql='SELECT r.*,u.name user_name,rm.number room_number,rm.name room_name,e.code equipment_code,e.name equipment_name FROM reservations r JOIN users u ON u.id=r.user_id LEFT JOIN rooms rm ON rm.id=r.room_id LEFT JOIN equipment e ON e.id=r.equipment_id';
 $where=[];$args=[];if($u['role']!=='ADMIN'){ $where[]='r.user_id=?';$args[]=$u['id']; } if(!empty($d['from'])){$where[]='r.start_at>=?';$args[]=$d['from'].' 00:00:00';} if(!empty($d['to'])){$where[]='r.start_at<=?';$args[]=$d['to'].' 23:59:59';}
 if($where)$sql.=' WHERE '.implode(' AND ',$where);$sql.=' ORDER BY r.created_at DESC, r.start_at DESC';$s=$pdo->prepare($sql);$s->execute($args);json_out(['ok'=>true,'items'=>$s->fetchAll()]);
}
if($action==='create'||$action==='update'){
 $resourceType=isset($d['room_id'])&&$d['room_id']!==''?'room':'equipment';$resourceId=(int)($d[$resourceType.'_id']??0);$start=str_replace('T',' ',$d['start_at']??'');$end=str_replace('T',' ',$d['end_at']??'');$purpose=trim((string)($d['purpose']??''));
 if(!$resourceId||!$start||!$end||!$purpose)json_out(['ok'=>false,'error'=>'Preencha recurso, início, fim e finalidade.'],422);if(strtotime($end)<=strtotime($start))json_out(['ok'=>false,'error'=>'O fim deve ser posterior ao início.'],422);
 $id=(int)($d['id']??0);$col=$resourceType==='room'?'room_id':'equipment_id';$other=$resourceType==='room'?'equipment_id':'room_id';
 $sql="SELECT id FROM reservations WHERE $col=? AND status='ACTIVE' AND start_at < ? AND end_at > ?";$args=[$resourceId,$end,$start];if($id){$sql.=' AND id<>?';$args[]=$id;}$s=$pdo->prepare($sql);$s->execute($args);if($s->fetch()){
   $q="SELECT start_at,end_at FROM reservations WHERE $col=? AND status='ACTIVE' AND start_at < ? AND end_at > ? ORDER BY start_at";$ss=$pdo->prepare($q);$ss->execute([$resourceId,$end,$start]);$busy=$ss->fetchAll();$suggest=[];$cursor=new DateTime($end);foreach($busy as $b){$candidate=$cursor->format('Y-m-d H:i:s');if(strtotime($candidate)<strtotime($b['start_at']))$suggest[]=$candidate;$cursor=new DateTime($b['end_at']);if(count($suggest)>=3)break;}json_out(['ok'=>false,'error'=>'O recurso já está reservado nesse período.','suggestions'=>$suggest],409);
 }
 if($action==='update'){
   $owner=(int)$pdo->query('SELECT user_id FROM reservations WHERE id='.(int)$id)->fetchColumn();if($u['role']!=='ADMIN'&&$owner!==$u['id'])json_out(['ok'=>false,'error'=>'Sem permissão.'],403);
   $s=$pdo->prepare("UPDATE reservations SET user_id=?,room_id=?,equipment_id=?,start_at=?,end_at=?,purpose=?,status='ACTIVE' WHERE id=?");$s->execute([$owner?:$u['id'],$resourceType==='room'?$resourceId:null,$resourceType==='equipment'?$resourceId:null,$start,$end,$purpose,$id]);audit_log($pdo,'UPDATE','reservations',$id,'Reserva atualizada');json_out(['ok'=>true,'id'=>$id]);
 }
 $s=$pdo->prepare("INSERT INTO reservations(user_id,room_id,equipment_id,start_at,end_at,purpose) VALUES(?,?,?,?,?,?)");$s->execute([$u['id'],$resourceType==='room'?$resourceId:null,$resourceType==='equipment'?$resourceId:null,$start,$end,$purpose]);$id=(int)$pdo->lastInsertId();audit_log($pdo,'CREATE','reservations',$id,'Reserva criada');$s=$pdo->prepare('SELECT r.*,rm.number room_number,rm.name room_name,e.code equipment_code,e.name equipment_name FROM reservations r LEFT JOIN rooms rm ON rm.id=r.room_id LEFT JOIN equipment e ON e.id=r.equipment_id WHERE r.id=?');$s->execute([$id]);json_out(['ok'=>true,'id'=>$id,'reservation'=>$s->fetch()]);
}
if($action==='cancel'){
 $id=(int)$d['id'];$s=$pdo->prepare('SELECT * FROM reservations WHERE id=?');$s->execute([$id]);$r=$s->fetch();if(!$r)json_out(['ok'=>false,'error'=>'Reserva não encontrada.'],404);if($u['role']!=='ADMIN'&&(int)$r['user_id']!==$u['id'])json_out(['ok'=>false,'error'=>'Somente o proprietário pode cancelar.'],403);
 $hours=((strtotime($r['start_at'])-time())/3600);if($u['role']!=='ADMIN'&&$hours<2)json_out(['ok'=>false,'error'=>'Cancelamento permitido somente com pelo menos 2 horas de antecedência.'],422);
 $s=$pdo->prepare("UPDATE reservations SET status='CANCELLED' WHERE id=?");$s->execute([$id]);audit_log($pdo,'UPDATE','reservations',$id,'Reserva cancelada');json_out(['ok'=>true]);
}
json_out(['ok'=>false,'error'=>'Ação inválida.'],400);
