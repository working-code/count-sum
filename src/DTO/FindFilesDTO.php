<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\DTO;

readonly class FindFilesDTO
{
    public function __construct(
        public string $fileName,
        public string $dirPath,
    ) {}
}
