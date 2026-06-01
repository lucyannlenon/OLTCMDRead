<?php
require_once __DIR__ . '/zte_connection.php';

$conn = createZteConnection();

echo "=== ONU Types ===\n";
echo $conn->exec('show pon onu-type gpon');
echo "\n\n=== TCONT Profiles ===\n";
echo $conn->exec('show pon tcont-profile');
