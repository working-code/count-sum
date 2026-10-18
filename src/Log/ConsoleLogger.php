<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\Log;

class ConsoleLogger implements LoggerInterface
{
    /** @var resource */
    private mixed $stream = STDERR;

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->log('WARN', $message);
    }

    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }

    private function log(string $level, string $message): void
    {
        fwrite(
            $this->stream,
            sprintf(
                "[%s] [%s] %s\n",
                date('Y-m-d H:i:s'),
                $level,
                $message,
            )
        );
    }
}
