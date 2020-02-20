<?php
require '../config.php';

use AllBoatsRise\WebhookHandler\API;
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Webhook Handler Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css" integrity="sha256-l85OmPOjvil/SOvVt3HnSSjzF1TUMyT9eV0c2BzEGzU=" crossorigin="anonymous" />

    <style type="text/css">
    body {
      font-family: Arial, Helvetica, sans-serif;
    }
    </style>
  </head>
<body>
  <h3>Status</h3>
  <table>
    <tr>
      <th>ID</th>
      <th>Type</th>
      <th>Instance</th>
      <th>Category</th>
      <th>Created</th>
      <th>Processed</th>
    </tr>
    <?php foreach(API::getEvents() as $event):
      $createdAt = new DateTime($event['created_at'], new DateTimeZone('UTC'));
      $createdAt->setTimezone(new DateTimeZone('America/Los_Angeles'));

      $processedAt = null;
      if ($event['processed'] == 1) {
        $processedAt = new DateTime($event['processed_at'], new DateTimeZone('UTC'));
        $processedAt->setTimezone(new DateTimeZone('America/Los_Angeles'));
      }
      ?>
      <tr>
        <td><?php echo htmlspecialchars($event['id']) ?></td>
        <td><?php echo htmlspecialchars($event['type']) ?></td>
        <td><?php echo htmlspecialchars($event['instance']) ?></td>
        <td><?php echo htmlspecialchars($event['category']) ?></td>
        <td><?php echo htmlspecialchars($createdAt->format('Y-m-d H:i:s')) ?></td>
        <td>
          <?php if ($processedAt): ?>
            <?php echo htmlspecialchars($processedAt->format('Y-m-d H:i:s')) ?>
          <?php else: ?>
            pending
          <?php endif ?>
        </td>
      </tr>
    <?php endforeach ?>
  </table>
</body>
</html>