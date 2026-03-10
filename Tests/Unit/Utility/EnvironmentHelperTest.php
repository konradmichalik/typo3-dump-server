<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_dump_server" TYPO3 CMS extension.
 *
 * (c) 2025-2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Utility;

use KonradMichalik\Typo3DumpServer\Utility\EnvironmentHelper;
use PHPUnit\Framework\TestCase;

use function is_string;

/**
 * EnvironmentHelperTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class EnvironmentHelperTest extends TestCase
{
    private string $originalEnvValue;

    private string $originalIdeValue;

    private string $originalPathMapValue;

    private string $originalDdevAppRootValue;

    protected function setUp(): void
    {
        $dumpServerHost = getenv('TYPO3_DUMP_SERVER_HOST');
        $this->originalEnvValue = is_string($dumpServerHost) ? $dumpServerHost : '';

        $dumpServerIde = getenv('TYPO3_DUMP_SERVER_IDE');
        $this->originalIdeValue = is_string($dumpServerIde) ? $dumpServerIde : '';

        $dumpServerPathMap = getenv('TYPO3_DUMP_SERVER_PATH_MAP');
        $this->originalPathMapValue = is_string($dumpServerPathMap) ? $dumpServerPathMap : '';

        $ddevAppRoot = getenv('DDEV_APPROOT');
        $this->originalDdevAppRootValue = is_string($ddevAppRoot) ? $ddevAppRoot : '';
    }

    protected function tearDown(): void
    {
        if ('' !== $this->originalEnvValue) {
            putenv('TYPO3_DUMP_SERVER_HOST='.$this->originalEnvValue);
        } else {
            putenv('TYPO3_DUMP_SERVER_HOST');
        }

        if ('' !== $this->originalIdeValue) {
            putenv('TYPO3_DUMP_SERVER_IDE='.$this->originalIdeValue);
        } else {
            putenv('TYPO3_DUMP_SERVER_IDE');
        }

        if ('' !== $this->originalPathMapValue) {
            putenv('TYPO3_DUMP_SERVER_PATH_MAP='.$this->originalPathMapValue);
        } else {
            putenv('TYPO3_DUMP_SERVER_PATH_MAP');
        }

        if ('' !== $this->originalDdevAppRootValue) {
            putenv('DDEV_APPROOT='.$this->originalDdevAppRootValue);
        } else {
            putenv('DDEV_APPROOT');
        }
    }

    public function testGetHostReturnsDefaultWhenEnvironmentVariableNotSet(): void
    {
        putenv('TYPO3_DUMP_SERVER_HOST');

        $host = EnvironmentHelper::getHost();

        self::assertSame('tcp://127.0.0.1:9912', $host);
    }

    public function testGetHostReturnsEnvironmentVariableWhenSet(): void
    {
        $customHost = 'tcp://192.168.1.100:9999';
        putenv('TYPO3_DUMP_SERVER_HOST='.$customHost);

        $host = EnvironmentHelper::getHost();

        self::assertSame($customHost, $host);
    }

    public function testGetHostReturnsEmptyStringWhenEnvironmentVariableIsEmpty(): void
    {
        putenv('TYPO3_DUMP_SERVER_HOST=');

        $host = EnvironmentHelper::getHost();

        self::assertSame('', $host);
    }

    public function testGetIdeReturnsNullWhenEnvironmentVariableNotSet(): void
    {
        putenv('TYPO3_DUMP_SERVER_IDE');

        $ide = EnvironmentHelper::getIde();

        self::assertNull($ide);
    }

    public function testGetIdeReturnsEnvironmentVariableWhenSet(): void
    {
        putenv('TYPO3_DUMP_SERVER_IDE=phpstorm');

        $ide = EnvironmentHelper::getIde();

        self::assertSame('phpstorm', $ide);
    }

    public function testGetIdeReturnsNullWhenEnvironmentVariableIsEmpty(): void
    {
        putenv('TYPO3_DUMP_SERVER_IDE=');

        $ide = EnvironmentHelper::getIde();

        self::assertNull($ide);
    }

    public function testGetPathMappingReturnsNullWhenNotSet(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP');
        putenv('DDEV_APPROOT');

        self::assertNull(EnvironmentHelper::getPathMapping());
    }

    public function testGetPathMappingReturnsNullWhenEmpty(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP=');
        putenv('DDEV_APPROOT');

        self::assertNull(EnvironmentHelper::getPathMapping());
    }

    public function testGetPathMappingReturnsMappingWhenSet(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP=/var/www/html=/Users/me/Projects');

        $mapping = EnvironmentHelper::getPathMapping();

        self::assertSame(['/var/www/html', '/Users/me/Projects'], $mapping);
    }

    public function testGetPathMappingReturnsNullWhenInvalidFormat(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP=/var/www/html');

        self::assertNull(EnvironmentHelper::getPathMapping());
    }

    public function testGetPathMappingReturnsNullWhenFromIsEmpty(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP==/Users/me/Projects');

        self::assertNull(EnvironmentHelper::getPathMapping());
    }

    public function testGetPathMappingHandlesPathsWithEquals(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP=/var/www/html=/Users/me/Pro=jects');

        $mapping = EnvironmentHelper::getPathMapping();

        self::assertSame(['/var/www/html', '/Users/me/Pro=jects'], $mapping);
    }

    public function testGetPathMappingFallsToDdevAppRoot(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP');
        putenv('DDEV_APPROOT=/Users/me/Sites/myproject');

        $mapping = EnvironmentHelper::getPathMapping();

        self::assertSame(['/var/www/html', '/Users/me/Sites/myproject'], $mapping);
    }

    public function testGetPathMappingPrefersExplicitOverDdev(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP=/opt/app=/Users/me/custom');
        putenv('DDEV_APPROOT=/Users/me/Sites/myproject');

        $mapping = EnvironmentHelper::getPathMapping();

        self::assertSame(['/opt/app', '/Users/me/custom'], $mapping);
    }

    public function testGetPathMappingIgnoresEmptyDdevAppRoot(): void
    {
        putenv('TYPO3_DUMP_SERVER_PATH_MAP');
        putenv('DDEV_APPROOT=');

        self::assertNull(EnvironmentHelper::getPathMapping());
    }
}
