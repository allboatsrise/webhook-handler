<?php

namespace AllBoatsRise\WebhookHandler;

use PDO;

class API {
  /** @var PDO */
  private static $db;

  /**
   * @return PDO
   */
  static function getDatabaseConnection() {
    if (!self::$db) {
      self::$db = new PDO('sqlite:' . __DIR__ . '/../data/database.sqlite');
      self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return self::$db;
  }

  static function queueEvent(array $event) {
    $db = self::getDatabaseConnection();
    $stmt = $db->prepare(<<<'TEXT'
INSERT INTO webhook_event (type, instance, category, data, created_at, modified_at)
VALUES (
  :type,
  :instance,
  :category,
  :data,
  DATETIME('now'),
  DATETIME('now')
)
TEXT
    );

    $stmt->bindValue(':type', $event['type'], PDO::PARAM_STR);
    $stmt->bindValue(':instance', $event['instance'], PDO::PARAM_STR);
    $stmt->bindValue(':category', $event['category'], PDO::PARAM_STR);
    $stmt->bindValue(':data', json_encode($event['data']), PDO::PARAM_STR);

    $stmt->execute();
  }

  /**
   * @return Generator
   */
  static function getUnprocessedEvents() {
    $db = self::getDatabaseConnection();

    $stmt = $db->prepare(<<<'TEXT'
SELECT id
FROM webhook_event
WHERE processed = 0
ORDER BY id ASC
TEXT
    );
    $stmt->execute();

    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();

    $stmt2 = $db->prepare(<<<'TEXT'
SELECT id, type, instance, category, data
FROM webhook_event
WHERE id = :id
TEXT
    );

    foreach($ids as $id) {
      $stmt2->execute([':id' => $id]);

      if ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $stmt2->closeCursor();
        $row['data'] = \json_decode($row['data'], true);
        yield $row;
      }
    }
  }

  static function markEventsAsProcessed(array $eventIds) {
    $db = self::getDatabaseConnection();
    $stmt = $db->prepare(<<<'TEXT'
UPDATE webhook_event
SET processed = 1, modified_at = DATETIME('now'), processed_at = DATETIME('now')
WHERE id = :id
TEXT
    );

    foreach($eventIds as $eventId) {
      $stmt->bindValue(':id', $eventId, PDO::PARAM_INT);
      $stmt->execute();
    }
  }

  /**
   * @return Generator
   */
  static function getEvents($limit = 200) {
    $db = self::getDatabaseConnection();
    $stmt = $db->prepare(<<<'TEXT'
SELECT id, type, instance, category, data, processed, created_at, modified_at, processed_at
FROM webhook_event
ORDER BY id DESC
LIMIT :limit
TEXT
    );

    $stmt->execute([
      ':limit' => $limit
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $row['data'] = \json_decode($row['data'], true);
      yield $row;
    }
  }
}