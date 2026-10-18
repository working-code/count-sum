<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\Service;

use WorkingCode\CountSum\DTO\FindFilesDTO;
use WorkingCode\CountSum\Exception\FileNotAccessException;
use WorkingCode\CountSum\FileSystem\FileReaderInterface;
use WorkingCode\CountSum\FileSystem\FileWalkerInterface;
use WorkingCode\CountSum\Log\LoggerInterface;

class CountSumService implements CountSumServiceInterface
{
    private const string FILE_NAME = 'count';

    public function __construct(
        private readonly FileWalkerInterface $fileWalker,
        private readonly FileReaderInterface $fileReader,
        private readonly LoggerInterface $logger,
    ) {}

    public function calculateCountSumByDirTree(string $dirPath): float
    {
        $sumTotal = 0;

        foreach ($this->fileWalker->findFilesBy(new FindFilesDTO(self::FILE_NAME, $dirPath)) as $file) {
            $sumTotal += $this->calculateCountSumByFile($file);
        }

        return $sumTotal;
    }

    private function calculateCountSumByFile(string $filePath): float
    {
        $sumTotalByFile = 0;

        try {
            foreach ($this->fileReader->getLines($filePath) as $line) {
                if (preg_match_all('/-?\d+(?:[\.,]\d+)?/', $line, $matches) > 0) {
                    foreach ($matches[0] as $number) {
                        $sumTotalByFile += (float) $number;
                    }
                }
            }
        } catch (FileNotAccessException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $sumTotalByFile;
    }
}
