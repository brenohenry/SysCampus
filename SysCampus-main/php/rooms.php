<?php
require __DIR__.'/bootstrap.php';
$u=login_required(); $method=$_SERVER['REQUEST_METHOD'];
if($method==='GET') { $rows=$pdo->query('SELECT r.*, (SELECT COUNT(*) FROM reservations x WHERE x.room_id=r.id) AS reservation_count FROM rooms r ORDER BY r.number')->fetchAll(); json_out(['ok'=>true,'items'=>$rows]); }
$u=admin_required(); $d=input(); $action=$d['action']??'save';
if($action==='hard_delete') {
 $id=(int)$d['id'];
 $q=$pdo->prepare('SELECT COUNT(*) FROM reservations WHERE room_id=?');
 $q->execute([$id]);
 if((int)$q->fetchColumn()>0) json_out(['ok'=>false,'error'=>'Esta sala possui histórico de reservas e não pode ser excluída. Inative-a para preservar o histórico.'],409);
 $s=$pdo->prepare('DELETE FROM rooms WHERE id=?');$s->execute([$id]);
 if($s->rowCount()!==1) json_out(['ok'=>false,'error'=>'Sala não encontrada.'],404);
 audit_log($pdo,'DELETE','rooms',$id,'Sala excluída definitivamente');
 json_out(['ok'=>true]);
}
if($action==='delete') { $id=(int)$d['id']; $s=$pdo->prepare('UPDATE rooms SET active=0 WHERE id=?');$s->execute([$id]);audit_log($pdo,'DELETE','rooms',$id,'Sala inativada');json_out(['ok'=>true]); }
$id=(int)($d['id']??0); $vals=[trim((string)($d['number']??'')),trim((string)($d['name']??'')),(int)($d['capacity']??0),trim((string)($d['location']??'')),trim((string)($d['resources']??''))];
if(!$vals[0]||!$vals[1]||$vals[2]<1||!$vals[3]) json_out(['ok'=>false,'error'=>'Preencha os campos obrigatórios.'],422);
if($id){$s=$pdo->prepare('UPDATE rooms SET number=?,name=?,capacity=?,location=?,resources=?,active=1 WHERE id=?');$s->execute([...$vals,$id]);audit_log($pdo,'UPDATE','rooms',$id,'Sala atualizada');}
else{$s=$pdo->prepare('INSERT INTO rooms(number,name,capacity,location,resources) VALUES(?,?,?,?,?)');$s->execute($vals);$id=(int)$pdo->lastInsertId();audit_log($pdo,'CREATE','rooms',$id,'Sala criada');}
json_out(['ok'=>true,'id'=>$id]);
