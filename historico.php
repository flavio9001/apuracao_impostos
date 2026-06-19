<?php
include 'config.php';
header('Content-Type: application/json');
$stmt = $pdo->query("SELECT * FROM leituras ORDER BY data_lida DESC LIMIT 100");
echo json_encode($stmt->fetchAll());
?>