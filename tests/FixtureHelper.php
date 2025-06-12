<?php

declare(strict_types=1);

namespace JBSNewMedia\AssetComposerBundle\Tests;

use Symfony\Component\Filesystem\Filesystem;

final class FixtureHelper
{
    public static function getFixturesPath(): string
    {
        return __DIR__ . '/Fixtures';
    }

    public static function copyFixturesTo(string $targetDir): void
    {
        $filesystem = new Filesystem();
        $filesystem->mirror(self::getFixturesPath(), $targetDir);
    }

    public static function createAssetFile(string $targetDir, string $path, string $content): void
    {
        $fullPath = $targetDir . '/' . $path;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);
    }
}
