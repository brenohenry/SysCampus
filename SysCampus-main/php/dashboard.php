<?php
require __DIR__.'/bootstrap.php';$u=login_required();
$rooms=(int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE active=1")->fetchColumn();$equipment=(int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE active=1")->fetchColumn();$users=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE active=1")->fetchColumn();
$s=$pdo->prepare("SELECT COUNT(*) FROM reservations WHERE status='ACTIVE' AND user_id=?");$s->execute([$u['id']]);$mine=(int)$s->fetchColumn();
$s=$pdo->prepare("SELECT r.*,rm.number room_number,rm.name room_name,e.code equipment_code,e.name equipment_name FROM reservations r LEFT JOIN rooms rm ON rm.id=r.room_id LEFT JOIN equipment e ON e.id=r.equipment_id WHERE r.status='ACTIVE' AND r.start_at>=NOW() AND r.user_id=? ORDER BY r.start_at LIMIT 8");$s->execute([$u['id']]);json_out(['ok'=>true,'metrics'=>['rooms'=>$rooms,'equipment'=>$equipment,'users'=>$users,'my_reservations'=>$mine],'upcoming'=>$s->fetchAll()]);
