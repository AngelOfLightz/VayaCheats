<?php
// Simple test to check if PHP is working
header('Content-Type: application/json');
echo json_encode(['test' => 'PHP is working', 'time' => date('Y-m-d H:i:s')]);
?>
