<?php

declare(strict_types=1);

use WorkingCode\CountSum\Application;

require_once './vendor/autoload.php';

set_time_limit(0);

try {
    $application = new Application();
    $result = $application->run($argv[1] ?? '');
    exit($result);
} catch (Throwable $exception) {
    printf(
        "%s %s\n",
        'В работе приложения возникла ошибка:',
        $exception->getMessage()
    );
    exit(1);
}
