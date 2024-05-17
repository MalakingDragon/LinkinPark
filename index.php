<?php
include 'config.php';
include 'api.php';



header('Content-Type: application/json');
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', $request_uri);
$endpoint = implode('/', array_slice($uri_parts, 2));
$endpoint = rtrim($endpoint, '/'); 

if ($endpoint === 'api/register') {
    register($pdo);
}
else if ($endpoint === 'api/login') {
    login($pdo);
}
// elseif (strpos($endpoint, 'dashboard') !== false) {
//     $plate = end($uri_parts);
//     get_plate_parking($pdo, $plate);
// }
else if ($endpoint === 'api/entrance'){
    entrance($pdo);
}
else if ($endpoint === 'api/exit'){
    exit_parking($pdo);
}
else if ($endpoint === 'api/latest-park'){
    latest_in($pdo);
}
else{
    echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
}
?>