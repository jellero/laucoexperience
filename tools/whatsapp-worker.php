<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/db.php';
require_once dirname(__DIR__) . '/inc/volontariato.php';

$result = volontariato_dispatch_outbox($pdo, null, 100);
printf("WhatsApp outbox: %d inviati, %d errori.%s", $result['sent'], $result['failed'], PHP_EOL);
exit($result['failed'] > 0 ? 1 : 0);
