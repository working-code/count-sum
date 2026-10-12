<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\FileSystem;

use WorkingCode\CountSum\Exception\FileNotAccessException;

interface FileReaderInterface
{
    /**
     * @return iterable<string>
     * @throws FileNotAccessException
     */
    public function getLines(string $filePath): iterable;
}
