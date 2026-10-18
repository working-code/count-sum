<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\Service;

use WorkingCode\CountSum\DTO\CountSumResultDTO;

interface CountSumServiceInterface
{
    public function calculateCountSumByDirTree(string $dirPath): CountSumResultDTO;
}
