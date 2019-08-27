<?php
require '../../config.php';

use AllBoatsRise\WebhookHandler\API;

$instance = (isset($_GET['instance'])) ? $_GET['instance'] : null;
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
  header('HTTP/1.0 400 Bad Request');
  die();
}

try {
  API::queueEvent([
    'type' => 'contentstack',
    'instance' => $instance,
    'category' => "{$data['module']}-{$data['event']}",
    'data' => $data,
  ]);
} catch(\Exception $ex) {
  header('HTTP/1.0 500 Internal Server Error');
  die();
}
