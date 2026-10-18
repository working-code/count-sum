<?php

declare(strict_types=1);

namespace Unit\FileSystem;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
use RuntimeException;
use WorkingCode\CountSum\DTO\FindFilesDTO;
use WorkingCode\CountSum\Exception\DirNotFoundException;
use WorkingCode\CountSum\FileSystem\FileWalker;
use WorkingCode\CountSum\Log\LoggerInterface;

/**
 * @internal
 */
class FileWalkerTest extends TestCase
{
    private const string DS = DIRECTORY_SEPARATOR;
    private const int DIR_ALLOW_PERMISSIONS = 0777;
    private const int NO_PERMISSIONS = 0000;

    private FileWalker $fileWalker;
    private string $rootDir;

    /**
     * @throws RandomException
     */
    protected function setUp(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $this->fileWalker = new FileWalker($logger);
        $this->rootDir = sprintf('%s%scount-sum-walker-%s', sys_get_temp_dir(), self::DS, bin2hex(random_bytes(7)));
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
     * @throws DirNotFoundException
     */
    public function testGivenTreeWithCountFilesWhenFindFilesByThenReturnsOnlyCountFiles(): void
    {
        $this->makeTree([
            '' => ['count' => "10\n", 'readme.md' => 'noise'],
            'a' => ['count' => "1\n", 'COUNT' => "9\n", 'count.txt' => "9\n"],
            'a/b' => ['count' => "2\n"],
            'x' => [],
        ]);

        self::assertEqualsCanonicalizing([
            $this->rootDir . self::DS . 'a' . self::DS . 'b' . self::DS . 'count',
            $this->rootDir . self::DS . 'a' . self::DS . 'count',
            $this->rootDir . self::DS . 'count',
        ], $this->foundFiles('count'));
    }

    /**
     * @throws DirNotFoundException
     */
    public function testGivenEmptyDirectoryWhenFindFilesByThenReturnsNoFiles(): void
    {
        self::assertEqualsCanonicalizing([], $this->foundFiles('count'));
    }

    public function testGivenNonExistentRootWhenFindFilesByThenThrowsDirNotFoundException(): void
    {
        $this->expectException(DirNotFoundException::class);

        iterator_to_array(
            $this->fileWalker->findFilesBy(new FindFilesDTO('count', $this->rootDir . '/nope')),
            false
        );
    }

    /**
     * @throws DirNotFoundException
     */
    public function testGivenSelfSymlinkLoopWhenFindFilesByThenReturnsEachCountOnce(): void
    {
        $this->makeTree(['' => ['count' => "3\n"], 'sub' => ['count' => "2\n"]]);
        symlink($this->rootDir, $this->rootDir . self::DS . 'loop');
        symlink($this->rootDir . self::DS . 'sub', $this->rootDir . self::DS . 'to-sub');

        self::assertEqualsCanonicalizing([
            $this->rootDir . self::DS . 'count',
            $this->rootDir . self::DS . 'sub' . self::DS . 'count',
        ], $this->foundFiles('count'));
    }

    /**
     * @throws DirNotFoundException
     */
    public function testGivenSymlinkToCountFileWhenFindFilesByThenReturnsLinkPath(): void
    {
        $this->makeTree(['real' => ['count' => "1\n"]]);
        symlink($this->rootDir . self::DS . 'real' . self::DS . 'count', $this->rootDir . self::DS . 'count');

        self::assertEqualsCanonicalizing([
            $this->rootDir . self::DS . 'count',         // ссылка на файл учитывается
            $this->rootDir . self::DS . 'real' . self::DS . 'count',
        ], $this->foundFiles('count'));
    }

    /**
     * @throws DirNotFoundException
     */
    public function testGivenBrokenSymlinkNamedCountWhenFindFilesByThenSkipsIt(): void
    {
        $this->makeTree(['' => ['count' => "1\n"], 'sub' => []]);
        symlink($this->rootDir . self::DS . 'missing', $this->rootDir . self::DS . 'sub' . self::DS . 'count');

        self::assertEqualsCanonicalizing([$this->rootDir . self::DS . 'count'], $this->foundFiles('count'));
    }

    /**
     * @throws DirNotFoundException
     */
    public function testGivenCriteriaWithOtherFileNameWhenFindFilesByThenReturnsOnlyMatchingFiles(): void
    {
        $this->makeTree([
            '' => ['count' => "1\n", 'other' => "9\n"],
            'a' => ['other' => "9\n"],
        ]);

        self::assertEqualsCanonicalizing([
            $this->rootDir . self::DS . 'a' . self::DS . 'other',
            $this->rootDir . self::DS . 'other',
        ], $this->foundFiles('other'));
    }

    /**
     * @throws DirNotFoundException
     */
    public function testGivenUnreadableDirectoryWhenFindFilesByThenSkipsIt(): void
    {
        $this->skipIfChmodNotHasEffect();
        $this->makeTreeWithUnreadableSecret();

        try {
            self::assertEqualsCanonicalizing(
                [$this->rootDir . self::DS . 'count'],
                $this->foundFiles('count')
            );
        } finally {
            $this->restoreSecretPermissions();
        }
    }

    /**
     * @throws DirNotFoundException
     */
    public function testGivenUnreadableDirectoryWhenFindFilesByThenLogsWarning(): void
    {
        $this->skipIfChmodNotHasEffect();
        $this->makeTreeWithUnreadableSecret();

        try {
            $logger = new class implements LoggerInterface {
                public array $warnings = [];

                public function info(string $message): void {}

                public function warning(string $message): void
                {
                    $this->warnings[] = $message;
                }

                public function error(string $message): void {}
            };

            iterator_to_array(
                (new FileWalker($logger))->findFilesBy(new FindFilesDTO('count', $this->rootDir)),
                false
            );

            self::assertCount(1, $logger->warnings);
            self::assertStringContainsString('secret', $logger->warnings[0]);
        } finally {
            $this->restoreSecretPermissions();
        }
    }

    /**
     * @throws DirNotFoundException
     */
    public function testGivenDeeplyNestedTreeWhenFindFilesByThenFindsCountFile(): void
    {
        $cur = $this->rootDir;
        for ($i = 0; $i < 1000; $i++) {
            $cur .= self::DS . 'a';
            mkdir($cur, self::DIR_ALLOW_PERMISSIONS);
        }
        file_put_contents($cur . self::DS . 'count', "7\n");

        self::assertEqualsCanonicalizing([$cur . self::DS . 'count'], $this->foundFiles('count'));
    }

    private function skipIfChmodNotHasEffect(): void
    {
        $probe = $this->rootDir . self::DS . 'probe';
        mkdir($probe, self::DIR_ALLOW_PERMISSIONS);
        chmod($probe, self::NO_PERMISSIONS);

        try {
            $handle = @opendir($probe);
            if ($handle !== false) {
                closedir($handle);
                self::markTestSkipped('chmod 0000 не блокирует чтение в этом окружении');
            }
        } finally {
            chmod($probe, self::DIR_ALLOW_PERMISSIONS);
            rmdir($probe);
        }
    }

    private function makeTreeWithUnreadableSecret(): void
    {
        $this->makeTree(['' => ['count' => "1\n"], 'secret' => ['count' => "999\n"]]);
        chmod($this->rootDir . self::DS . 'secret', self::NO_PERMISSIONS);
    }

    private function restoreSecretPermissions(): void
    {
        chmod($this->rootDir . self::DS . 'secret', self::DIR_ALLOW_PERMISSIONS);
    }

    /** @param array<string, array<string, string>> $filesByDir */
    private function makeTree(array $filesByDir): void
    {
        foreach ($filesByDir as $dir => $files) {
            $dirPath = rtrim($this->rootDir . self::DS . ltrim($dir, self::DS), self::DS);
            if (!is_dir($dirPath)) {
                mkdir($dirPath, self::DIR_ALLOW_PERMISSIONS, true);
            }
            foreach ($files as $name => $content) {
                file_put_contents($dirPath . self::DS . $name, $content);
            }
        }
    }

    /**
     * @return string[]
     *
     * @throws DirNotFoundException
     */
    private function foundFiles(string $fileName): array
    {
        return iterator_to_array(
            $this->fileWalker->findFilesBy(new FindFilesDTO($fileName, $this->rootDir)),
            false
        );
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
