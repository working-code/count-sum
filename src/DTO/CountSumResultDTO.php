<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\DTO;

readonly class CountSumResultDTO
{
    /**
     * @param string[] $skippedFiles
     */
    public function __construct(
        public float $sum,
        public array $skippedFiles,
    ) {}

    public function isComplete(): bool
    {
        return $this->skippedFiles === [];
    }
}
