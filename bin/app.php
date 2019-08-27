<?php
require dirname(__DIR__) . '/config.php';

use Symfony\Component\Console\Application;
use AllBoatsRise\WebhookHandler\Command\Process;

$application = new Application();
$application->add(new Process([
  'lockFile' => __DIR__ . '/app.process.lock',
]));
$application->run();