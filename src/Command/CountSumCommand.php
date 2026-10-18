<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\Command;

use Throwable;
use WorkingCode\CountSum\Log\LoggerInterface;
use WorkingCode\CountSum\Service\CountSumServiceInterface;

readonly class CountSumCommand implements CommandInterface
{
    public function __construct(
        private CountSumServiceInterface $countSumService,
        private LoggerInterface $logger,
        private string $dirPath,
    ) {}

    public function execute(): int
    {
        $this->logger->info(sprintf('start command %s', self::class));

        try {
            $result = $this->countSumService->calculateCountSumByDirTree($this->dirPath);

            printf(
                "sum of numbers from 'count' files across the directory tree %s: %.2f\n",
                $this->dirPath,
                $result->sum
            );

            if ($result->isComplete()) {
                return self::SUCCESS;
            }

            $skippedFiles = $result->skippedFiles;
            $this->logger->warning(sprintf(
                'count is incomplete, skipped %d file(s): %s',
                count($skippedFiles),
                implode(', ', $skippedFiles)
            ));

            return self::PARTIAL;
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());

            return self::FAILURE;
        } finally {
            $this->logger->info(sprintf('end command %s', self::class));
        }
    }
}
