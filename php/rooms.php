<?php
require __DIR__.'/bootstrap.php';
$u=login_required(); $method=$_SERVER['REQUEST_METHOD'];
if($method==='GET') { $rows=$pdo->query('SELECT * FROM rooms ORDER BY number')->fetchAll(); json_out(['ok'=>true,'items'=>$rows]); }
$u=admin_required(); $d=input(); $action=$d['action']??'save';
if($action==='delete') { $id=(int)$d['id']; $s=$pdo->prepare('UPDATE rooms SET active=0 WHERE id=?');$s->execute([$id]);audit_log($pdo,'DELETE','rooms',$id,'Sala inativada');json_out(['ok'=>true]); }
$id=(int)($d['id']??0); $vals=[trim((string)($d['number']??'')),trim((string)($d['name']??'')),(int)($d['capacity']??0),trim((string)($d['location']??'')),trim((string)($d['resources']??''))];
if(!$vals[0]||!$vals[1]||$vals[2]<1||!$vals[3]) json_out(['ok'=>false,'error'=>'Preencha os campos obrigatórios.'],422);
if($id){$s=$pdo->prepare('UPDATE rooms SET number=?,name=?,capacity=?,location=?,resources=?,active=1 WHERE id=?');$s->execute([...$vals,$id]);audit_log($pdo,'UPDATE','rooms',$id,'Sala atualizada');}
else{$s=$pdo->prepare('INSERT INTO rooms(number,name,capacity,location,resources) VALUES(?,?,?,?,?)');$s->execute($vals);$id=(int)$pdo->lastInsertId();audit_log($pdo,'CREATE','rooms',$id,'Sala criada');}
json_out(['ok'=>true,'id'=>$id]);
