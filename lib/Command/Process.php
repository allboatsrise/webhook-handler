<?php
namespace AllBoatsRise\WebhookHandler\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;

use AllBoatsRise\WebhookHandler\Config;
use AllBoatsRise\WebhookHandler\API;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process as CommandlineProcess;

class Process extends Command {
  protected static $defaultName = 'process';

  private $options = [];
  private $lockHandle = null;

  function __construct($options = [], string $name = null) {
    parent::__construct($name);
    $this->options = array_merge([
        'lockFile' => null,
        'githubEventCommandline' => null,
        'contentstackEventCommandline' => null,
    ], $options);
  }

  protected function configure() {
    $this->setDescription('Process outstanding webhook events.');
    $this->addOption('list', null, InputOption::VALUE_NONE, 'List outstanding events.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $events = API::getUnprocessedEvents();

    // output list of events
    if ($input->getOption('list')) {
      $output->write(print_r($events, true));
      return;
    }

    // leave if there are no events to process
    if (empty($events)) return;

    // obtain lock
    if (!$this->getLock()) {
      $output->writeln('Another process is already running this publish command.');
      return;
    }

    $handlerConfigs = Config::getHandlers();

    foreach($handlerConfigs as $handlerConfig) {
      foreach($events as $event) {
        if ($event['type'] !== $handlerConfig['type']) continue;
        if ($event['instance'] !== $handlerConfig['instance']) continue;
        if (empty($handlerConfig['command'])) continue;

        $commandLine = $handlerConfig['command'];

        // run the command line
        $process = CommandlineProcess::fromShellCommandline(
          $commandLine,
          null,
          null,
          null,
          null // disable timeout
        );
        $process->start();

        $process->wait(function($type, $buffer) {
          if (CommandlineProcess::ERR === $type) {
            fwrite(STDERR, $buffer);
          } else {
            fwrite(STDOUT, $buffer);
          }
        });

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
      }
    }

    API::markEventsAsProcessed(array_map(function($event) {
      return $event['id'];
    }, $events));
  }

  private function getLock() {
    // check if a lock is necessary
    if (!$this->options['lockFile']) return true;

    $lockFile = str_replace('\\', '/', $this->options['lockFile']);

    $this->lockHandle = fopen($lockFile, 'w');

    if (false === $this->lockHandle) {
      throw new RuntimeException("Failed to create file ($lockFile).");
    }

    return flock($this->lockHandle, LOCK_EX | LOCK_NB);
  }
}
