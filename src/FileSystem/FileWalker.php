<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\FileSystem;

use WorkingCode\CountSum\DTO\FindFilesDTO;
use WorkingCode\CountSum\Exception\DirNotFoundException;
use WorkingCode\CountSum\Log\LoggerInterface;

class FileWalker implements FileWalkerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return iterable<string>
     * @throws DirNotFoundException
     *
     */
    public function findFilesBy(FindFilesDTO $criteria): iterable
    {
        $this->checkExistDir($criteria->dirPath);

        $dirPath = realpath($criteria->dirPath);
        $searchList = [$dirPath];
        $seenList = [];

        if ($dirPath !== false) {
            $seenList[$dirPath] = true;
        }

        while ($searchList !== []) {
            $dir = array_pop($searchList);
            $dirHandle = @opendir($dir);

            if ($dirHandle === false) {
                $this->logger->warning(sprintf('%s is not access directory', $dir));

                continue;
            }

            try {
                while (($file = readdir($dirHandle)) !== false) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    $path = $dir . DIRECTORY_SEPARATOR . $file;

                    if (is_link($path)) {
                        if ($file === $criteria->fileName && is_file($path)) {
                            yield $path;
                        }

                        continue;
                    }

                    if (is_dir($path)) {
                        $realPath = realpath($path);

                        if ($realPath !== false && isset($seenList[$realPath])) {
                            continue;
                        }

                        if ($realPath !== false) {
                            $seenList[$realPath] = true;
                        }

                        $searchList[] = $path;
                    } elseif ($file === $criteria->fileName && is_file($path)) {
                        yield $path;
                    }
                }
            } finally {
                closedir($dirHandle);
            }
        }
    }

    /**
     * @throws DirNotFoundException
     */
    private function checkExistDir(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            throw new DirNotFoundException(sprintf('%s is not a directory', $dirPath));
        }
    }
}
