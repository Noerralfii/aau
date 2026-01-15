<?php
// Disabled debug endpoint
header('Content-Type: application/json; charset=utf-8');
http_response_code(403);
echo json_encode(['ok'=>false,'error'=>'disabled']);

