<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\Service;

use WorkingCode\CountSum\DTO\CountSumResultDTO;
use WorkingCode\CountSum\DTO\FindFilesDTO;
use WorkingCode\CountSum\Exception\FileNotAccessException;
use WorkingCode\CountSum\FileSystem\FileReaderInterface;
use WorkingCode\CountSum\FileSystem\FileWalkerInterface;
use WorkingCode\CountSum\Log\LoggerInterface;

class CountSumService implements CountSumServiceInterface
{
    private const string FILE_NAME = 'count';

    private const string NUMBER_PATTERN = '/^[+-]?\d+(?:[.,]\d+)?$/';

    public function __construct(
        private readonly FileWalkerInterface $fileWalker,
        private readonly FileReaderInterface $fileReader,
        private readonly LoggerInterface $logger,
    ) {}

    public function calculateCountSumByDirTree(string $dirPath): CountSumResultDTO
    {
        $sumTotal = 0;
        $skippedFiles = [];

        foreach ($this->fileWalker->findFilesBy(new FindFilesDTO(self::FILE_NAME, $dirPath)) as $file) {
            try {
                $sumTotal += $this->calculateCountSumByFile($file);
            } catch (FileNotAccessException $exception) {
                $this->logger->error($exception->getMessage());

                $skippedFiles[] = $file;
            }
        }

        return new CountSumResultDTO($sumTotal, $skippedFiles);
    }

    /**
     * @throws FileNotAccessException
     */
    private function calculateCountSumByFile(string $filePath): float
    {
        $sumTotalByFile = 0.0;

        foreach ($this->fileReader->getLines($filePath) as $line) {
            $number = trim($line);

            if ($number === '') {
                continue;
            }

            if (preg_match(self::NUMBER_PATTERN, $number) !== 1) {
                $this->logger->warning(sprintf(
                    "%s: line '%s' is not a number, skipped",
                    $filePath,
                    $number,
                ));

                continue;
            }

            $sumTotalByFile += (float) str_replace(',', '.', $number);
        }

        return $sumTotalByFile;
    }
}
