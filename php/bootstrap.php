<?php
declare(strict_types=1);
$config=require __DIR__.'/../config.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_name($config['session_name']); session_start(); }
$dsn="mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
try {
  $pdo=new PDO($dsn,$config['db']['user'],$config['db']['pass'],[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false
  ]);
} catch(Throwable $e) { json_out(['ok'=>false,'error'=>'Não foi possível conectar ao MySQL. Verifique config.php.'],500); }
function json_out(array $data,int $status=200):never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); echo json_encode($data,JSON_UNESCAPED_UNICODE); exit; }
function input():array { $raw=file_get_contents('php://input'); $j=json_decode($raw,true); return is_array($j)?$j:$_POST; }
function user():?array { return $_SESSION['user']??null; }
function login_required():array { $u=user(); if(!$u) json_out(['ok'=>false,'error'=>'Sessão expirada.'],401); return $u; }
function admin_required():array { $u=login_required(); if(($u['role']??'')!=='ADMIN') json_out(['ok'=>false,'error'=>'Acesso restrito ao administrador.'],403); return $u; }
function audit_log(PDO $pdo,string $action,string $entity,?int $id,string $details=''):void { $u=user(); $s=$pdo->prepare('INSERT INTO audit_logs(user_id,action,entity,entity_id,details) VALUES(?,?,?,?,?)'); $s->execute([$u['id']??null,$action,$entity,$id,$details]); }
function seed_demo(PDO $pdo):void {
  if((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()>0)return;
  $pdo->beginTransaction();
  try {
    $s=$pdo->prepare('INSERT INTO users(name,email,password_hash,role,active) VALUES(?,?,?,?,1)');
    $s->execute(['João da Silva','aluno@lumenveritas.edu.br',password_hash('123456',PASSWORD_DEFAULT),'USER']);
    $s->execute(['Administrador Lumen Veritas','admin@lumenveritas.edu.br',password_hash('admin123',PASSWORD_DEFAULT),'ADMIN']);
    $s=$pdo->prepare('INSERT INTO rooms(number,name,capacity,location,resources) VALUES(?,?,?,?,?)');
    foreach([['101','Sala 101',40,'Bloco A','Projetor, Ar-condicionado, Computadores'],['202','Laboratório 202',32,'Bloco B','Projetor, Ar-condicionado, 32 computadores'],['305','Sala 305',20,'Bloco C','TV, Ar-condicionado, Videoconferência'],['AUD','Auditório Central',180,'Prédio Central','Projetor, Áudio, Ar-condicionado, Microfones']] as $x)$s->execute($x);
    $s=$pdo->prepare('INSERT INTO equipment(code,name,description,location) VALUES(?,?,?,?)');
    foreach([['EQ-001','Projetor Epson','Projetor multimídia Full HD','Almoxarifado'],['EQ-002','Notebook Dell','Notebook para uso acadêmico','Almoxarifado'],['EQ-003','Kit Laboratorial A','Kit de instrumentos para laboratório','Laboratório 202'],['EQ-004','Microfone sem fio','Microfone UHF para eventos','Almoxarifado']] as $x)$s->execute($x);
    $pdo->commit();
  } catch(Throwable $e){$pdo->rollBack();}
}
seed_demo($pdo);
