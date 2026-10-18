<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\FileSystem;

use WorkingCode\CountSum\Exception\FileNotAccessException;

class FileReader implements FileReaderInterface
{
    /**
     * @return iterable<string>
     *
     * @throws FileNotAccessException
     */
    public function getLines(string $filePath): iterable
    {
        $fileHandle = @fopen($filePath, 'r');

        if ($fileHandle === false) {
            throw new FileNotAccessException(sprintf("Unable to open file '%s'", $filePath));
        }

        try {
            while (($line = fgets($fileHandle)) !== false) {
                yield $line;
            }
        } finally {
            fclose($fileHandle);
        }
    }
}
