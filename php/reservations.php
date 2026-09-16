<?php
require __DIR__.'/bootstrap.php';
$u=login_required();$method=$_SERVER['REQUEST_METHOD'];$d=$method==='GET'?$_GET:input();$action=$d['action']??'list';

if($action==='availability'){
 $type=($d['type']??'room')==='equipment'?'equipment':'room';
 $resourceId=(int)($d['resource_id']??0);$date=(string)($d['date']??'');
 if(!$resourceId||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))json_out(['ok'=>false,'error'=>'Recurso ou data inválidos.'],422);
 $startDay=$date.' 07:00:00';$endDay=$date.' 22:00:00';$col=$type==='room'?'room_id':'equipment_id';
 $s=$pdo->prepare("SELECT start_at,end_at FROM reservations WHERE $col=? AND status='ACTIVE' AND start_at < ? AND end_at > ? ORDER BY start_at");$s->execute([$resourceId,$endDay,$startDay]);$busy=$s->fetchAll();
 $now=new DateTime();$dayStart=new DateTime($startDay);$dayEnd=new DateTime($endDay);$slots=[];
 // Períodos fixos de 50 minutos. O usuário pode reservar 1 ou 2 períodos consecutivos.
 for($t=clone $dayStart;$t<$dayEnd;$t->modify('+50 minutes')){
   $slotEnd=(clone $t)->modify('+50 minutes');
   if($slotEnd>$dayEnd)break;
   $free=true;
   if($date===$now->format('Y-m-d') && $t <= $now)$free=false;
   foreach($busy as $b){$bs=new DateTime($b['start_at']);$be=new DateTime($b['end_at']);if($t<$be && $slotEnd>$bs){$free=false;break;}}
   if($free)$slots[]=['start'=>$t->format('H:i'),'end'=>$slotEnd->format('H:i'),'duration'=>50];
 }
 $options=[];
 foreach($slots as $i=>$slot){
   $options[]=$slot;
   if(isset($slots[$i+1]) && $slots[$i+1]['start']===$slot['end']){
      $options[$i]['can_double']=true;
   }
 }
 json_out(['ok'=>true,'date'=>$date,'slots'=>$options,'slot_minutes'=>50,'max_slots'=>2]);
}
if($action==='availability_dates'){
 $type=($d['type']??'room')==='equipment'?'equipment':'room';$resourceId=(int)($d['resource_id']??0);$days=min(max((int)($d['days']??30),1),90);
 if(!$resourceId)json_out(['ok'=>false,'error'=>'Recurso inválido.'],422);
 $col=$type==='room'?'room_id':'equipment_id';$today=new DateTime('today');$now=new DateTime();$from=$today->format('Y-m-d').' 00:00:00';$to=(clone $today)->modify('+'.$days.' days')->format('Y-m-d').' 23:59:59';
 $s=$pdo->prepare("SELECT start_at,end_at FROM reservations WHERE $col=? AND status='ACTIVE' AND start_at < ? AND end_at > ? ORDER BY start_at");$s->execute([$resourceId,$to,$from]);$busy=$s->fetchAll();$available=[];
 for($i=0;$i<$days;$i++){
   $date=(clone $today)->modify('+'.$i.' days');$ds=$date->format('Y-m-d');$dayStart=new DateTime($ds.' 07:00:00');$dayEnd=new DateTime($ds.' 22:00:00');$found=false;
   for($t=clone $dayStart;$t<$dayEnd;$t->modify('+50 minutes')){$slotEnd=(clone $t)->modify('+50 minutes');if($slotEnd>$dayEnd)break;if($ds===$now->format('Y-m-d')&&$t<=$now)continue;$free=true;foreach($busy as $b){$bs=new DateTime($b['start_at']);$be=new DateTime($b['end_at']);if($t<$be&&$slotEnd>$bs){$free=false;break;}}if($free){$found=true;break;}}
   if($found)$available[]=$ds;
 }
 json_out(['ok'=>true,'dates'=>$available]);
}
if($method==='GET'||$action==='list'){
 $sql='SELECT r.*,u.name user_name,rm.number room_number,rm.name room_name,e.code equipment_code,e.name equipment_name FROM reservations r JOIN users u ON u.id=r.user_id LEFT JOIN rooms rm ON rm.id=r.room_id LEFT JOIN equipment e ON e.id=r.equipment_id';
 $where=[];$args=[];if($u['role']!=='ADMIN'){ $where[]='r.user_id=?';$args[]=$u['id']; } if(!empty($d['from'])){$where[]='r.start_at>=?';$args[]=$d['from'].' 00:00:00';} if(!empty($d['to'])){$where[]='r.start_at<=?';$args[]=$d['to'].' 23:59:59';}
 if($where)$sql.=' WHERE '.implode(' AND ',$where);$sql.=' ORDER BY r.created_at DESC, r.start_at DESC';$s=$pdo->prepare($sql);$s->execute($args);json_out(['ok'=>true,'items'=>$s->fetchAll()]);
}
if($action==='create'||$action==='update'){
 $resourceType=(isset($d['room_id'])&&$d['room_id']!=='')?'room':'equipment';
 $resourceId=(int)($d[$resourceType.'_id']??0);
 $start=str_replace('T',' ',trim((string)($d['start_at']??'')));
 $end=str_replace('T',' ',trim((string)($d['end_at']??'')));
 $purpose=trim((string)($d['purpose']??''));
 if(!$resourceId||!$start||!$end||$purpose==='') json_out(['ok'=>false,'error'=>'Preencha recurso, início, fim e finalidade.'],422);

 try{
   $startDt=new DateTime($start); $endDt=new DateTime($end);
 }catch(Throwable $e){ json_out(['ok'=>false,'error'=>'Data ou horário inválido.'],422); }
 $startTs=$startDt->getTimestamp(); $endTs=$endDt->getTimestamp();
 $minutes=(int)(($endTs-$startTs)/60);
 if($endTs<=$startTs) json_out(['ok'=>false,'error'=>'O fim deve ser posterior ao início.'],422);
 $date=$startDt->format('Y-m-d');
 if($date!==$endDt->format('Y-m-d')||!in_array($minutes,[50,100],true))
   json_out(['ok'=>false,'error'=>'A reserva deve ter 50 ou 100 minutos.'],422);

 $dayStart=new DateTime($date.' 07:00:00'); $dayEnd=new DateTime($date.' 22:00:00');
 $stepSeconds=50*60;
 if($startDt<$dayStart || $endDt>$dayEnd || (($startDt->getTimestamp()-$dayStart->getTimestamp())%$stepSeconds)!==0)
   json_out(['ok'=>false,'error'=>'Escolha um horário disponível entre 07:00 e 22:00, em períodos de 50 minutos.'],422);

 $id=(int)($d['id']??0);
 $col=$resourceType==='room'?'room_id':'equipment_id';

 // Confirm that the selected resource exists and is active.
 $table=$resourceType==='room'?'rooms':'equipment';
 $check=$pdo->prepare("SELECT id FROM $table WHERE id=? AND active=1");
 $check->execute([$resourceId]);
 if(!$check->fetchColumn()) json_out(['ok'=>false,'error'=>'O recurso selecionado não está disponível.'],422);

 // Block overlapping active reservations.
 $sql="SELECT id,start_at,end_at FROM reservations WHERE $col=? AND status='ACTIVE' AND start_at < ? AND end_at > ?";
 $args=[$resourceId,$end,$start];
 if($id){$sql.=' AND id<>?';$args[]=$id;}
 $s=$pdo->prepare($sql); $s->execute($args);
 if($s->fetch()){
   $q="SELECT start_at,end_at FROM reservations WHERE $col=? AND status='ACTIVE' AND start_at < ? AND end_at > ? ORDER BY start_at";
   $ss=$pdo->prepare($q); $ss->execute([$resourceId,$end,$start]); $busy=$ss->fetchAll();
   $suggest=[]; $cursor=new DateTime($date.' 07:00:00');
   foreach($busy as $bb){
     $candidate=(clone $cursor);
     if($candidate < new DateTime($bb['start_at'])){
       $suggest[]=$candidate->format('Y-m-d H:i:s');
       if(count($suggest)>=3) break;
     }
     $cursor=new DateTime($bb['end_at']);
   }
   json_out(['ok'=>false,'error'=>'O recurso já está reservado nesse período.','suggestions'=>$suggest],409);
 }

 try{
   if($action==='update'){
     $ownerStmt=$pdo->prepare('SELECT user_id FROM reservations WHERE id=?');
     $ownerStmt->execute([$id]); $owner=$ownerStmt->fetchColumn();
     if($owner===false) json_out(['ok'=>false,'error'=>'Reserva não encontrada.'],404);
     if($u['role']!=='ADMIN'&&(int)$owner!==$u['id']) json_out(['ok'=>false,'error'=>'Sem permissão.'],403);
     $up=$pdo->prepare("UPDATE reservations SET room_id=?,equipment_id=?,start_at=?,end_at=?,purpose=?,status='ACTIVE' WHERE id=?");
     $up->execute([
       $resourceType==='room'?$resourceId:null,
       $resourceType==='equipment'?$resourceId:null,
       $start,$end,$purpose,$id
     ]);
     try{audit_log($pdo,'UPDATE','reservations',$id,'Reserva atualizada');}catch(Throwable $ignore){}
     json_out(['ok'=>true,'id'=>$id]);
   }

   $ins=$pdo->prepare('INSERT INTO reservations(user_id,room_id,equipment_id,start_at,end_at,purpose,status) VALUES(?,?,?,?,?,?,?)');
   $ins->execute([
     (int)$u['id'],
     $resourceType==='room'?$resourceId:null,
     $resourceType==='equipment'?$resourceId:null,
     $start,$end,$purpose,'ACTIVE'
   ]);
   $newId=(int)$pdo->lastInsertId();

   // Audit failure must not turn a successfully stored reservation into an apparent failure.
   try{audit_log($pdo,'CREATE','reservations',$newId,'Reserva criada');}catch(Throwable $ignore){}

   $get=$pdo->prepare('SELECT r.*,u.name user_name,rm.number room_number,rm.name room_name,e.code equipment_code,e.name equipment_name FROM reservations r JOIN users u ON u.id=r.user_id LEFT JOIN rooms rm ON rm.id=r.room_id LEFT JOIN equipment e ON e.id=r.equipment_id WHERE r.id=?');
   $get->execute([$newId]);
   json_out(['ok'=>true,'id'=>$newId,'reservation'=>$get->fetch()]);
 }catch(PDOException $e){
   json_out(['ok'=>false,'error'=>'Não foi possível gravar a reserva no MySQL.','details'=>$e->getMessage()],500);
 }catch(Throwable $e){
   json_out(['ok'=>false,'error'=>'Erro ao gravar a reserva.','details'=>$e->getMessage()],500);
 }
}
if($action==='cancel'){
 $id=(int)$d['id'];$s=$pdo->prepare('SELECT * FROM reservations WHERE id=?');$s->execute([$id]);$r=$s->fetch();if(!$r)json_out(['ok'=>false,'error'=>'Reserva não encontrada.'],404);if($u['role']!=='ADMIN'&&(int)$r['user_id']!==$u['id'])json_out(['ok'=>false,'error'=>'Somente o proprietário pode cancelar.'],403);
 $hours=((strtotime($r['start_at'])-time())/3600);if($u['role']!=='ADMIN'&&$hours<2)json_out(['ok'=>false,'error'=>'Cancelamento permitido somente com pelo menos 2 horas de antecedência.'],422);
 $s=$pdo->prepare("UPDATE reservations SET status='CANCELLED' WHERE id=?");$s->execute([$id]);audit_log($pdo,'UPDATE','reservations',$id,'Reserva cancelada');json_out(['ok'=>true]);
}
json_out(['ok'=>false,'error'=>'Ação inválida.'],400);
