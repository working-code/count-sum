<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\Log;

interface LoggerInterface
{
    public function info(string $message): void;

    public function warning(string $message): void;

    public function error(string $message): void;
}
