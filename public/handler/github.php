<?php
require '../../config.php';

use GitHubWebhook\Handler;
use AllBoatsRise\WebhookHandler\API;
use AllBoatsRise\WebhookHandler\Config;

$instance = (isset($_GET['instance'])) ? $_GET['instance'] : null;
$handlerConfig = null;

foreach(Config::getHandlers() as $c) {
  if (!empty($c['instance']) && $c['instance'] === $instance) {
    $handlerConfig = $c;
    break;
  }
}

if (!$handlerConfig) {
  header('HTTP/1.0 400 Bad Request');
  die();
}


$handler = new Handler($handlerConfig['secret'], null);

if (!$handler->validate()) {
  header('HTTP/1.0 400 Bad Request');
  die();
}

$event = $handler->getEvent();
$data = $handler->getData();

// $event = 'push';
// $data = [
//   'data' => [
//     'action' => 'push',
//     'otherdata' => 'asdf',
//   ],
// ];

try {
  API::queueEvent([
    'type' => 'github',
    'instance' => $instance,
    'category' => $event,
    'data' => $data,
  ]);
} catch(\Exception $ex) {
  header('HTTP/1.0 500 Internal Server Error');
  die();
}
