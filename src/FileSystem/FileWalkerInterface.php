<?php

declare(strict_types=1);

namespace WorkingCode\CountSum\FileSystem;

use WorkingCode\CountSum\DTO\FindFilesDTO;

interface FileWalkerInterface
{
    /**
     * @return iterable<string>
     */
    public function findFilesBy(FindFilesDTO $criteria): iterable;
}
