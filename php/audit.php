<?php
require __DIR__.'/bootstrap.php';admin_required();$s=$pdo->query('SELECT a.*,u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 300');json_out(['ok'=>true,'items'=>$s->fetchAll()]);
