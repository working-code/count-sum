<?php

declare(strict_types=1);

namespace Unit;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
use RuntimeException;
use WorkingCode\CountSum\Application;

/**
 * @internal
 */
class ApplicationTest extends TestCase
{
    private const string DS = DIRECTORY_SEPARATOR;
    private const int DIR_ALLOW_PERMISSIONS = 0777;
    private const int NO_PERMISSIONS = 0000;

    private string $rootDir;

    /**
     * @throws RandomException
     */
    protected function setUp(): void
    {
        $this->rootDir = sprintf('%s%scount-sum-app-%s', sys_get_temp_dir(), self::DS, bin2hex(random_bytes(7)));
        mkdir($this->rootDir, self::DIR_ALLOW_PERMISSIONS, true);

        if (!is_dir($this->rootDir)) {
            throw new RuntimeException(sprintf('Failed to create temp dir: %s', $this->rootDir));
        }
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->rootDir);
    }

    public function testGivenTreeWithCountFilesWhenRunThenReturnsSuccess(): void
    {
        file_put_contents($this->rootDir . self::DS . 'count', "10\n");
        mkdir($this->rootDir . self::DS . 'sub', self::DIR_ALLOW_PERMISSIONS);
        file_put_contents($this->rootDir . self::DS . 'sub' . self::DS . 'count', "5\n");

        self::assertSame(0, (new Application())->run($this->rootDir));
    }

    public function testGivenNonExistentDirWhenRunThenReturnsFailure(): void
    {
        self::assertSame(1, (new Application())->run($this->rootDir . self::DS . 'nope'));
    }

    public function testGivenUnreadableCountFileWhenRunThenReturnsPartial(): void
    {
        $this->skipIfChmodNotHasEffect();

        file_put_contents($this->rootDir . self::DS . 'count', "10\n");
        mkdir($this->rootDir . self::DS . 'sub', self::DIR_ALLOW_PERMISSIONS);
        $unreachable = $this->rootDir . self::DS . 'sub' . self::DS . 'count';
        file_put_contents($unreachable, "5\n");
        chmod($unreachable, self::NO_PERMISSIONS);

        try {
            self::assertSame(2, (new Application())->run($this->rootDir));
        } finally {
            chmod($unreachable, self::DIR_ALLOW_PERMISSIONS);
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
