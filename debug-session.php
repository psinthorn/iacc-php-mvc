<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'com_id' => isset($_SESSION['com_id']) ? $_SESSION['com_id'] : 'NOT SET',
    'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET',
    'com_name' => isset($_SESSION['com_name']) ? $_SESSION['com_name'] : 'NOT SET',
    'all_session' => array_keys($_SESSION)
], JSON_PRETTY_PRINT);
