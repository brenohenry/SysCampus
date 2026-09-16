<?php
require __DIR__.'/bootstrap.php';
$action=$_GET['action']??($_POST['action']??'me');
if($action==='login'){
  $d=input(); $email=trim((string)($d['email']??$d['id']??'')); $pass=(string)($d['password']??$d['senha']??''); $requestedRole=strtoupper((string)($d['role']??'USER')); if(!in_array($requestedRole,['USER','ADMIN'],true)) $requestedRole='USER';
  $s=$pdo->prepare('SELECT id,name,email,password_hash,role,active FROM users WHERE email=? LIMIT 1'); $s->execute([$email]); $u=$s->fetch();
  if(!$u || !$u['active'] || !password_verify($pass,$u['password_hash'])) json_out(['ok'=>false,'error'=>'ID ou senha inválidos.'],401); if($u['role']!==$requestedRole) json_out(['ok'=>false,'error'=>$requestedRole==='ADMIN'?'Esta conta não possui acesso à Área do Administrador.':'Esta conta pertence à Área do Administrador. Use a opção Área do Administrador.'],403);
  unset($u['password_hash']); $_SESSION['user']=$u; audit_log($pdo,'LOGIN','users',(int)$u['id'],'Login efetuado'); json_out(['ok'=>true,'user'=>$u]);
}
if($action==='logout'){ $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),'',['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>$p['samesite']??'Lax']); } session_destroy(); json_out(['ok'=>true]); }
json_out(['ok'=>true,'user'=>user()]);
