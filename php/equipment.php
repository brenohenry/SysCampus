<?php
require __DIR__.'/bootstrap.php';
$u=login_required(); $method=$_SERVER['REQUEST_METHOD'];
if($method==='GET'){ $rows=$pdo->query('SELECT id,code,name,description,location,active,created_at,updated_at FROM equipment ORDER BY code')->fetchAll();json_out(['ok'=>true,'items'=>$rows]); }
$u=admin_required();$d=input();$action=$d['action']??'save';
if($action==='delete'){ $id=(int)$d['id'];$s=$pdo->prepare('UPDATE equipment SET active=0 WHERE id=?');$s->execute([$id]);audit_log($pdo,'DELETE','equipment',$id,'Equipamento inativado');json_out(['ok'=>true]); }
$id=(int)($d['id']??0);$vals=[trim((string)($d['code']??'')),trim((string)($d['name']??'')),trim((string)($d['description']??'')),trim((string)($d['location']??''))];
if(!$vals[0]||!$vals[1]||!$vals[2])json_out(['ok'=>false,'error'=>'Preencha os campos obrigatórios.'],422);
if($id){$s=$pdo->prepare('UPDATE equipment SET code=?,name=?,description=?,location=?,active=1 WHERE id=?');$s->execute([...$vals,$id]);audit_log($pdo,'UPDATE','equipment',$id,'Equipamento atualizado');}
else{$s=$pdo->prepare('INSERT INTO equipment(code,name,description,location) VALUES(?,?,?,?)');$s->execute($vals);$id=(int)$pdo->lastInsertId();audit_log($pdo,'CREATE','equipment',$id,'Equipamento criado');}
json_out(['ok'=>true,'id'=>$id]);
