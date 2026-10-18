<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\Command;

interface CommandInterface
{
    public const int SUCCESS = 0;
    public const int FAILURE = 1;
    public const int PARTIAL = 2;

    public function execute(): int;
}
