<?php
require __DIR__.'/bootstrap.php';
$u=login_required(); $method=$_SERVER['REQUEST_METHOD'];
if($method==='GET'){ $rows=$pdo->query('SELECT e.id,e.code,e.name,e.description,e.location,e.active,e.created_at,e.updated_at,(SELECT COUNT(*) FROM reservations x WHERE x.equipment_id=e.id) AS reservation_count FROM equipment e ORDER BY e.code')->fetchAll();json_out(['ok'=>true,'items'=>$rows]); }
$u=admin_required();$d=input();$action=$d['action']??'save';
if($action==='hard_delete'){
 $id=(int)$d['id'];
 $q=$pdo->prepare('SELECT COUNT(*) FROM reservations WHERE equipment_id=?');
 $q->execute([$id]);
 if((int)$q->fetchColumn()>0) json_out(['ok'=>false,'error'=>'Este equipamento possui histórico de reservas e não pode ser excluído. Inative-o para preservar o histórico.'],409);
 $s=$pdo->prepare('DELETE FROM equipment WHERE id=?');$s->execute([$id]);
 if($s->rowCount()!==1) json_out(['ok'=>false,'error'=>'Equipamento não encontrado.'],404);
 audit_log($pdo,'DELETE','equipment',$id,'Equipamento excluído definitivamente');
 json_out(['ok'=>true]);
}
if($action==='delete'){ $id=(int)$d['id'];$s=$pdo->prepare('UPDATE equipment SET active=0 WHERE id=?');$s->execute([$id]);audit_log($pdo,'DELETE','equipment',$id,'Equipamento inativado');json_out(['ok'=>true]); }
$id=(int)($d['id']??0);$vals=[trim((string)($d['code']??'')),trim((string)($d['name']??'')),trim((string)($d['description']??'')),trim((string)($d['location']??''))];
if(!$vals[0]||!$vals[1]||!$vals[2])json_out(['ok'=>false,'error'=>'Preencha os campos obrigatórios.'],422);
if($id){$s=$pdo->prepare('UPDATE equipment SET code=?,name=?,description=?,location=?,active=1 WHERE id=?');$s->execute([...$vals,$id]);audit_log($pdo,'UPDATE','equipment',$id,'Equipamento atualizado');}
else{$s=$pdo->prepare('INSERT INTO equipment(code,name,description,location) VALUES(?,?,?,?)');$s->execute($vals);$id=(int)$pdo->lastInsertId();audit_log($pdo,'CREATE','equipment',$id,'Equipamento criado');}
json_out(['ok'=>true,'id'=>$id]);
