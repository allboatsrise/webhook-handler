<?php
require '../../config.php';

use AllBoatsRise\WebhookHandler\API;

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
  header('HTTP/1.0 400 Bad Request');
  die();
}

try {
  API::queueEvent([
    'type' => API::EVENT_TYPE_CONTENTSTACK,
    'category' => $data['event'],
    'data' => $data,
  ]);
} catch(\Exception $ex) {
  header('HTTP/1.0 500 Internal Server Error');
  die();
}
