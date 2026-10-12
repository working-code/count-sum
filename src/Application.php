<?php

declare(strict_types=1);

namespace WorkingCode\CountSum;

use WorkingCode\CountSum\Command\CountSumCommand;
use WorkingCode\CountSum\FileSystem\FileReader;
use WorkingCode\CountSum\FileSystem\FileWalker;
use WorkingCode\CountSum\Log\ConsoleLogger;
use WorkingCode\CountSum\Service\CountSumService;

class Application
{
    public function run(string $dirPath): int
    {
        $logger = new ConsoleLogger();
        $fileReader = new FileReader();
        $fileWalker = new FileWalker($logger);
        $countSumService = new CountSumService($fileWalker, $fileReader, $logger);
        $countSumCommand = new CountSumCommand($countSumService, $logger, $dirPath);

        return $countSumCommand->execute();
    }
}
