<?php
require __DIR__.'/bootstrap.php';
admin_required();$method=$_SERVER['REQUEST_METHOD'];$d=$method==='GET'?$_GET:input();$action=$d['action']??'list';
if($method==='GET'){$rows=$pdo->query('SELECT id,name,email,role,active,created_at FROM users ORDER BY name')->fetchAll();json_out(['ok'=>true,'items'=>$rows]);}
if($action==='delete'){$id=(int)$d['id'];$s=$pdo->prepare('UPDATE users SET active=0 WHERE id=?');$s->execute([$id]);audit_log($pdo,'DELETE','users',$id,'Usuário inativado');json_out(['ok'=>true]);}
$id=(int)($d['id']??0);$name=trim((string)($d['name']??''));$email=trim((string)($d['email']??''));$role=($d['role']??'USER')==='ADMIN'?'ADMIN':'USER';$active=!empty($d['active'])?1:0;$password=(string)($d['password']??'');if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL))json_out(['ok'=>false,'error'=>'Nome e e-mail válidos são obrigatórios.'],422);
if($id){if($password){$s=$pdo->prepare('UPDATE users SET name=?,email=?,role=?,active=?,password_hash=? WHERE id=?');$s->execute([$name,$email,$role,$active,password_hash($password,PASSWORD_DEFAULT),$id]);}else{$s=$pdo->prepare('UPDATE users SET name=?,email=?,role=?,active=? WHERE id=?');$s->execute([$name,$email,$role,$active,$id]);}audit_log($pdo,'UPDATE','users',$id,'Usuário atualizado');}
else{$s=$pdo->prepare('INSERT INTO users(name,email,password_hash,role,active) VALUES(?,?,?,?,?)');$s->execute([$name,$email,password_hash($password?:'123456',PASSWORD_DEFAULT),$role,$active]);$id=(int)$pdo->lastInsertId();audit_log($pdo,'CREATE','users',$id,'Usuário criado');}json_out(['ok'=>true,'id'=>$id]);
