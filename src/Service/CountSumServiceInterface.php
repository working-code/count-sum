<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\Service;

interface CountSumServiceInterface
{
    public function calculateCountSumByDirTree(string $dirPath): float;
}
