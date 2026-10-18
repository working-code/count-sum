<?php

declare(strict_types=1);

namespace Unit\FileSystem;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
use RuntimeException;
use WorkingCode\CountSum\Exception\FileNotAccessException;
use WorkingCode\CountSum\FileSystem\FileReader;

/**
 * @internal
 */
class FileReaderTest extends TestCase
{
    private const string DS = DIRECTORY_SEPARATOR;
    private const int DIR_ALLOW_PERMISSIONS = 0777;
    private const int NO_PERMISSIONS = 0000;

    private FileReader $fileReader;
    private string $rootDir;

    /**
     * @throws RandomException
     */
    protected function setUp(): void
    {
        $this->fileReader = new FileReader();
        $this->rootDir = sprintf('%s%scount-sum-reader-%s', sys_get_temp_dir(), self::DS, bin2hex(random_bytes(7)));
        mkdir($this->rootDir, self::DIR_ALLOW_PERMISSIONS, true);

        if (!is_dir($this->rootDir)) {
            throw new RuntimeException(sprintf('Failed to create temp dir: %s', $this->rootDir));
        }
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->rootDir);
    }

    /**
     * @throws FileNotAccessException
     */
    public function testGivenMultiLineFileWhenGetLinesThenYieldsLinesInOrder(): void
    {
        $path = $this->makeFile('data.txt', "10\n20\n30\n");

        self::assertSame(["10\n", "20\n", "30\n"], $this->readAll($path));
    }

    /**
     * @throws FileNotAccessException
     */
    public function testGivenFileWithoutTrailingNewlineWhenGetLinesThenYieldsLastLine(): void
    {
        $path = $this->makeFile('data.txt', "1\n2\n3");

        self::assertSame(["1\n", "2\n", '3'], $this->readAll($path));
    }

    /**
     * @throws FileNotAccessException
     */
    public function testGivenEmptyFileWhenGetLinesThenYieldsNoLines(): void
    {
        $path = $this->makeFile('data.txt', '');

        self::assertSame([], $this->readAll($path));
    }

    /**
     * @throws FileNotAccessException
     */
    public function testGivenFileWithSingleNewlineWhenGetLinesThenYieldsEmptyLine(): void
    {
        $path = $this->makeFile('data.txt', "\n");

        self::assertSame(["\n"], $this->readAll($path));
    }

    /**
     * @throws FileNotAccessException
     */
    public function testGivenOpenGeneratorWhenBrokenAfterFirstLineThenNoError(): void
    {
        $path = $this->makeFile('data.txt', "1\n2\n3\n");

        $lines = [];
        foreach ($this->fileReader->getLines($path) as $line) {
            $lines[] = $line;

            break;
        }

        self::assertSame(["1\n"], $lines);
    }

    public function testGivenNonExistentFileWhenGetLinesThenThrowsFileNotAccessException(): void
    {
        $path = $this->rootDir . self::DS . 'missing.txt';

        try {
            iterator_to_array($this->fileReader->getLines($path), false);
            self::fail('FileNotAccessException was not thrown');
        } catch (FileNotAccessException $e) {
            self::assertStringContainsString('missing.txt', $e->getMessage());
        }
    }

    public function testGivenUnreadableFileWhenGetLinesThenThrowsFileNotAccessException(): void
    {
        $this->skipIfChmodNotHasEffect();
        $path = $this->makeFile('secret.txt', "999\n");
        chmod($path, self::NO_PERMISSIONS);

        $this->expectException(FileNotAccessException::class);
        $this->expectExceptionMessageMatches('#secret\.txt#');

        try {
            iterator_to_array($this->fileReader->getLines($path), false);
        } finally {
            chmod($path, self::DIR_ALLOW_PERMISSIONS);
        }
    }

    private function skipIfChmodNotHasEffect(): void
    {
        $probe = $this->rootDir . self::DS . 'probe.txt';
        file_put_contents($probe, 'x');
        chmod($probe, self::NO_PERMISSIONS);

        try {
            if (is_readable($probe)) {
                self::markTestSkipped('chmod 0000 не блокирует чтение в этом окружении');
            }
        } finally {
            chmod($probe, self::DIR_ALLOW_PERMISSIONS);
            unlink($probe);
        }
    }

    private function makeFile(string $name, string $content): string
    {
        $path = $this->rootDir . self::DS . $name;
        file_put_contents($path, $content);

        return $path;
    }

    /** @return string[]
     * @throws FileNotAccessException
     */
    private function readAll(string $path): array
    {
        return iterator_to_array($this->fileReader->getLines($path), false);
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . self::DS . $entry;
            is_dir($path) && !is_link($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
