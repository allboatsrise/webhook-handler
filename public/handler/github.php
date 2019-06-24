<?php
require '../../config.php';

use GitHubWebhook\Handler;
use AllBoatsRise\WebhookHandler\API;

$handler = new Handler(getenv('GITHUB_WEBHOOK_SECRET'), null);

if (!$handler->validate()) {
  header('HTTP/1.0 400 Bad Request');
  die();
}

$data = $handler->getData();

// $data = [
//   'action' => 'push',
//   'data' => [
//     'action' => 'push',
//     'otherdata' => 'asdf',
//   ],
// ];

try {
  API::queueEvent([
    'type' => API::EVENT_TYPE_GITHUB,
    'category' => $data['action'],
    'data' => $data,
  ]);
} catch(\Exception $ex) {
  header('HTTP/1.0 500 Internal Server Error');
  die();
}
