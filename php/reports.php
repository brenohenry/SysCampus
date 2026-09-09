<?php
require __DIR__.'/bootstrap.php';admin_required();
$byRoom=$pdo->query("SELECT COALESCE(rm.name,e.name) resource,COUNT(*) total FROM reservations r LEFT JOIN rooms rm ON rm.id=r.room_id LEFT JOIN equipment e ON e.id=r.equipment_id WHERE r.status='ACTIVE' GROUP BY r.room_id,r.equipment_id ORDER BY total DESC")->fetchAll();
$byStatus=$pdo->query('SELECT status,COUNT(*) total FROM reservations GROUP BY status')->fetchAll();
json_out(['ok'=>true,'by_resource'=>$byRoom,'by_status'=>$byStatus]);
