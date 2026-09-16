<?php
require __DIR__.'/bootstrap.php';
$action=$_GET['action']??($_POST['action']??'me');
if($action==='forgot-password'){
  $d=input();
  $email=strtolower(trim((string)($d['email']??'')));
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)) json_out(['ok'=>false,'error'=>'Informe um e-mail válido.'],422);

  // Resposta uniforme: não revela se o e-mail existe no sistema.
  $generic='Se o e-mail estiver cadastrado, você receberá uma mensagem com o link para redefinir a senha.';
  $s=$pdo->prepare('SELECT id,name,email,active FROM users WHERE email=? LIMIT 1');
  $s->execute([$email]); $target=$s->fetch();

  if($target && (int)$target['active']===1){
    $token=bin2hex(random_bytes(32));
    $expires=(new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
    $u=$pdo->prepare('UPDATE users SET reset_token=?, reset_token_expires_at=? WHERE id=?');
    $u->execute([$token,$expires,(int)$target['id']]);
    require_once __DIR__.'/../lib/mailer.php';
    $resetUrl=app_base_url().'/redefinir-senha.html?token='.rawurlencode($token);
    try {
      send_password_reset_email($target['email'],$target['name'],$resetUrl,$expires);
      json_out(['ok'=>true,'message'=>$generic]);
    } catch(Throwable $e) {
      error_log('SysCampus password reset mail: '.$e->getMessage());
      // Não expõe credenciais/configuração SMTP ao usuário.
      json_out(['ok'=>false,'error'=>'Não foi possível enviar o e-mail de recuperação. Verifique a configuração SMTP do sistema.'],500);
    }
  }
  json_out(['ok'=>true,'message'=>$generic]);
}
if($action==='validate-reset-token'){
  $token=trim((string)($_GET['token']??''));
  if(!preg_match('/^[a-f0-9]{64}$/',$token)) json_out(['ok'=>false,'error'=>'Link de recuperação inválido ou expirado.'],400);
  $s=$pdo->prepare('SELECT id,reset_token_expires_at FROM users WHERE reset_token=? AND reset_token_expires_at>NOW() AND active=1 LIMIT 1');
  $s->execute([$token]); $target=$s->fetch();
  if(!$target) json_out(['ok'=>false,'error'=>'Link de recuperação inválido ou expirado.'],400);
  $dt=new DateTimeImmutable($target['reset_token_expires_at']);
  json_out(['ok'=>true,'expires_at'=>$dt->format('d/m/Y H:i')]);
}
if($action==='reset-password'){
  $d=input();
  $token=trim((string)($d['token']??''));
  $password=(string)($d['password']??'');
  $confirm=(string)($d['password_confirm']??'');
  if(!preg_match('/^[a-f0-9]{64}$/',$token)) json_out(['ok'=>false,'error'=>'Link de recuperação inválido ou expirado.'],400);
  if(strlen($password)<8) json_out(['ok'=>false,'error'=>'A nova senha deve possuir pelo menos 8 caracteres.'],422);
  if($password!==$confirm) json_out(['ok'=>false,'error'=>'As senhas não coincidem.'],422);
  $s=$pdo->prepare('SELECT id FROM users WHERE reset_token=? AND reset_token_expires_at>NOW() AND active=1 LIMIT 1');
  $s->execute([$token]); $target=$s->fetch();
  if(!$target) json_out(['ok'=>false,'error'=>'Link de recuperação inválido ou expirado.'],400);
  $hash=password_hash($password,PASSWORD_DEFAULT);
  $u=$pdo->prepare('UPDATE users SET password_hash=?,reset_token=NULL,reset_token_expires_at=NULL WHERE id=?');
  $u->execute([$hash,(int)$target['id']]);
  audit_log($pdo,'UPDATE','users',(int)$target['id'],'Senha redefinida por recuperação de acesso');
  json_out(['ok'=>true,'message'=>'Senha redefinida com sucesso. Você já pode entrar no SysCampus.']);
}
if($action==='login'){
  $d=input(); $email=trim((string)($d['email']??$d['id']??'')); $pass=(string)($d['password']??$d['senha']??''); $requestedRole=strtoupper((string)($d['role']??'USER')); if(!in_array($requestedRole,['USER','ADMIN'],true)) $requestedRole='USER';
  $s=$pdo->prepare('SELECT id,name,email,password_hash,role,active FROM users WHERE email=? LIMIT 1'); $s->execute([$email]); $u=$s->fetch();
  if(!$u || !$u['active'] || !password_verify($pass,$u['password_hash'])) json_out(['ok'=>false,'error'=>'ID ou senha inválidos.'],401); if($u['role']!==$requestedRole) json_out(['ok'=>false,'error'=>$requestedRole==='ADMIN'?'Esta conta não possui acesso à Área do Administrador.':'Esta conta pertence à Área do Administrador. Use a opção Área do Administrador.'],403);
  unset($u['password_hash']); $_SESSION['user']=$u; audit_log($pdo,'LOGIN','users',(int)$u['id'],'Login efetuado'); json_out(['ok'=>true,'user'=>$u]);
}
if($action==='logout'){ $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),'',['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>$p['samesite']??'Lax']); } session_destroy(); json_out(['ok'=>true]); }
json_out(['ok'=>true,'user'=>user()]);
