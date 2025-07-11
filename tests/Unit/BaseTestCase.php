<?php

namespace MuenchDev\JbManager\Tests\Unit;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected string $tempHome;
    protected string $tempFixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempHome = sys_get_temp_dir() . '/jb-manager-test-' . uniqid('', true);
        mkdir($this->tempHome, 0777, true);
        putenv('HOME=' . $this->tempHome);

        $configDir = $this->tempHome . '/.config/JetBrains/TestApp-2024.1';
        mkdir($configDir, 0777, true);

        $this->tempFixturePath = $configDir . '/recentProjects.xml';
        copy(__DIR__ . '/../_files/recentProjects.xml', $this->tempFixturePath);

        // Ensure the file exists
        if (!file_exists($this->tempFixturePath)) {
            throw new \RuntimeException("Failed to copy fixture file to: " . $this->tempFixturePath);
        }
    }

    protected function tearDown(): void
    {
        $this->rmdirRecursive($this->tempHome);
        putenv('HOME');
        parent::tearDown();
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
