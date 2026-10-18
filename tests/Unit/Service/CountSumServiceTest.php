<?php

declare(strict_types=1);

namespace Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkingCode\CountSum\DTO\FindFilesDTO;
use WorkingCode\CountSum\Exception\FileNotAccessException;
use WorkingCode\CountSum\FileSystem\FileReaderInterface;
use WorkingCode\CountSum\FileSystem\FileWalkerInterface;
use WorkingCode\CountSum\Log\LoggerInterface;
use WorkingCode\CountSum\Service\CountSumService;

/**
 * @internal
 */
class CountSumServiceTest extends TestCase
{
    private const string GOOD_FILE = '/data/good/count';
    private const string BAD_FILE = '/data/bad/count';

    public function testGivenNoCountFilesWhenCalculateCountSumByDirTreeThenReturnsZero(): void
    {
        $fileWalker = $this->createStub(FileWalkerInterface::class);
        $fileWalker->method('findFilesBy')->willReturn([]);

        $service = $this->makeService($fileWalker);

        $result = $service->calculateCountSumByDirTree('/data');

        self::assertSame(0.0, $result->sum);
        self::assertTrue($result->isComplete());
    }

    public function testGivenFilesWithNumbersWhenCalculateCountSumByDirTreeThenReturnsSumOfAllFiles(): void
    {
        $fileWalker = $this->createStub(FileWalkerInterface::class);
        $fileWalker->method('findFilesBy')->willReturn([self::GOOD_FILE, '/data/other/count']);

        $fileReader = $this->createStub(FileReaderInterface::class);
        $fileReader
            ->method('getLines')
            ->willReturnCallback(static fn (string $path): array => $path === self::GOOD_FILE ? ["10\n", "20\n"] : ["30\n"])
        ;

        $service = $this->makeService($fileWalker, $fileReader);

        $result = $service->calculateCountSumByDirTree('/data');

        self::assertSame(60.0, $result->sum);
        self::assertTrue($result->isComplete());
    }

    #[DataProvider('numberFormats')]
    public function testGivenNumbersInDifferentFormatsWhenCalculateCountSumByDirTreeThenSumsParsedValues(array $lines, float $expected): void
    {
        $fileWalker = $this->createStub(FileWalkerInterface::class);
        $fileWalker->method('findFilesBy')->willReturn([self::GOOD_FILE]);

        $fileReader = $this->createStub(FileReaderInterface::class);
        $fileReader->method('getLines')->willReturn($lines);

        $service = $this->makeService($fileWalker, $fileReader);

        $result = $service->calculateCountSumByDirTree('/data');

        self::assertSame($expected, $result->sum);
        self::assertTrue($result->isComplete());
    }

    /** @return iterable<string, array{array<string>, float}> */
    public static function numberFormats(): iterable
    {
        yield 'integer' => [["10\n"], 10.0];

        yield 'decimal with dot' => [["1.5\n"], 1.5];

        yield 'decimal with comma' => [["3,5\n"], 3.5];

        yield 'negative number' => [["-2\n"], -2.0];

        yield 'positive sign' => [["+7\n"], 7.0];

        yield 'blank lines are ignored' => [["\n", "   \n", "5\n"], 5.0];
    }

    public function testGivenLinesThatAreNotSingleNumbersWhenCalculateCountSumByDirTreeThenSkipsThemAndLogsWarning(): void
    {
        $fileWalker = $this->createStub(FileWalkerInterface::class);
        $fileWalker->method('findFilesBy')->willReturn([self::GOOD_FILE]);

        $fileReader = $this->createStub(FileReaderInterface::class);
        $fileReader->method('getLines')->willReturn(["1 2 3\n", "abc 42\n", "10,def\n", "1.2.3\n", "\n"]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::exactly(4))
            ->method('warning')
            ->with(self::stringContains('is not a number'))
        ;

        $service = $this->makeService($fileWalker, $fileReader, $logger);

        $result = $service->calculateCountSumByDirTree('/data');

        self::assertSame(0.0, $result->sum);
        self::assertTrue($result->isComplete());
    }

    public function testGivenUnreadableFileWhenCalculateCountSumByDirTreeThenLogsErrorAndSkipsIt(): void
    {
        $fileWalker = $this->createStub(FileWalkerInterface::class);
        $fileWalker->method('findFilesBy')->willReturn([self::BAD_FILE, self::GOOD_FILE]);

        $fileReader = $this->createStub(FileReaderInterface::class);
        $fileReader
            ->method('getLines')
            ->willReturnCallback(static function (string $path): array {
                if ($path === self::BAD_FILE) {
                    throw new FileNotAccessException(sprintf("Unable to open file '%s'", $path));
                }

                return ["5\n"];
            })
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(sprintf("Unable to open file '%s'", self::BAD_FILE))
        ;

        $service = $this->makeService($fileWalker, $fileReader, $logger);

        $result = $service->calculateCountSumByDirTree('/data');

        self::assertSame(5.0, $result->sum);
        self::assertFalse($result->isComplete());
        self::assertSame([self::BAD_FILE], $result->skippedFiles);
    }

    public function testGivenDirPathWhenCalculateCountSumByDirTreeThenQueriesWalkerWithCountFileName(): void
    {
        $fileWalker = $this->createMock(FileWalkerInterface::class);
        $fileWalker
            ->expects(self::once())
            ->method('findFilesBy')
            ->with(self::callback(static function (FindFilesDTO $criteria): bool {
                return $criteria->fileName === 'count' && $criteria->dirPath === '/data';
            }))
            ->willReturn([])
        ;

        $service = $this->makeService($fileWalker);

        $service->calculateCountSumByDirTree('/data');
    }

    /**
     * @param FileWalkerInterface|mixed $fileWalker
     * @param FileReaderInterface|mixed $fileReader
     * @param LoggerInterface|mixed     $logger
     */
    private function makeService(mixed $fileWalker = null, mixed $fileReader = null, mixed $logger = null): CountSumService
    {
        return new CountSumService(
            $fileWalker ?? $this->createStub(FileWalkerInterface::class),
            $fileReader ?? $this->createStub(FileReaderInterface::class),
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }
}
