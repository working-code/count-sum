<?php

declare(strict_types=1);

namespace Unit\Command;

use PHPUnit\Framework\TestCase;
use WorkingCode\CountSum\Command\CommandInterface;
use WorkingCode\CountSum\Command\CountSumCommand;
use WorkingCode\CountSum\DTO\CountSumResultDTO;
use WorkingCode\CountSum\Exception\DirNotFoundException;
use WorkingCode\CountSum\Log\LoggerInterface;
use WorkingCode\CountSum\Service\CountSumServiceInterface;

/**
 * @internal
 */
class CountSumCommandTest extends TestCase
{
    public function testGivenServiceReturnsSumWhenExecuteThenReturnsSuccess(): void
    {
        $service = $this->createMock(CountSumServiceInterface::class);
        $service
            ->expects(self::once())
            ->method('calculateCountSumByDirTree')
            ->with('/data')
            ->willReturn(new CountSumResultDTO(42.0, []))
        ;

        $command = new CountSumCommand($service, $this->createStub(LoggerInterface::class), '/data');

        self::assertSame(CommandInterface::SUCCESS, $command->execute());
    }

    public function testGivenServiceThrowsWhenExecuteThenReturnsFailureAndLogsError(): void
    {
        $service = $this->createStub(CountSumServiceInterface::class);
        $service
            ->method('calculateCountSumByDirTree')
            ->willThrowException(new DirNotFoundException('boom'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('boom')
        ;

        $command = new CountSumCommand($service, $logger, '/data');

        self::assertSame(CommandInterface::FAILURE, $command->execute());
    }

    public function testGivenSuccessfulExecuteWhenLogsThenWritesStartAndEndInfo(): void
    {
        $service = $this->createStub(CountSumServiceInterface::class);
        $service->method('calculateCountSumByDirTree')->willReturn(new CountSumResultDTO(1.0, []));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::exactly(2))
            ->method('info')
        ;

        $command = new CountSumCommand($service, $logger, '/data');

        self::assertSame(CommandInterface::SUCCESS, $command->execute());
    }

    public function testGivenFailedExecuteWhenLogsThenStillWritesEndInfo(): void
    {
        $service = $this->createStub(CountSumServiceInterface::class);
        $service
            ->method('calculateCountSumByDirTree')
            ->willThrowException(new DirNotFoundException('boom'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::exactly(2))
            ->method('info')
        ;

        $command = new CountSumCommand($service, $logger, '/data');

        self::assertSame(CommandInterface::FAILURE, $command->execute());
    }

    public function testGivenIncompleteResultWhenExecuteThenReturnsPartialAndLogsWarning(): void
    {
        $service = $this->createStub(CountSumServiceInterface::class);
        $service
            ->method('calculateCountSumByDirTree')
            ->willReturn(new CountSumResultDTO(5.0, ['/data/bad/count']))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('incomplete'))
        ;

        $command = new CountSumCommand($service, $logger, '/data');

        self::assertSame(CommandInterface::PARTIAL, $command->execute());
    }
}
