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

use KonradMichalik\Ttt\Attribute\WithEnvVar;
use KonradMichalik\Typo3DumpServer\Utility\EnvironmentHelper;
use PHPUnit\Framework\TestCase;

/**
 * EnvironmentHelperTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class EnvironmentHelperTest extends TestCase
{
    #[WithEnvVar('TYPO3_DUMP_SERVER_HOST')]
    public function testGetHostReturnsDefaultWhenEnvironmentVariableNotSet(): void
    {
        $host = EnvironmentHelper::getHost();

        self::assertSame('tcp://127.0.0.1:9912', $host);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_HOST', 'tcp://192.168.1.100:9999')]
    public function testGetHostReturnsEnvironmentVariableWhenSet(): void
    {
        $host = EnvironmentHelper::getHost();

        self::assertSame('tcp://192.168.1.100:9999', $host);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_HOST', '')]
    public function testGetHostReturnsEmptyStringWhenEnvironmentVariableIsEmpty(): void
    {
        $host = EnvironmentHelper::getHost();

        self::assertSame('', $host);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_IDE')]
    public function testGetIdeReturnsNullWhenEnvironmentVariableNotSet(): void
    {
        $ide = EnvironmentHelper::getIde();

        self::assertNull($ide);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_IDE', 'phpstorm')]
    public function testGetIdeReturnsEnvironmentVariableWhenSet(): void
    {
        $ide = EnvironmentHelper::getIde();

        self::assertSame('phpstorm', $ide);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_IDE', '')]
    public function testGetIdeReturnsNullWhenEnvironmentVariableIsEmpty(): void
    {
        $ide = EnvironmentHelper::getIde();

        self::assertNull($ide);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP')]
    #[WithEnvVar('DDEV_APPROOT')]
    public function testGetPathMappingReturnsNullWhenNotSet(): void
    {
        self::assertNull(EnvironmentHelper::getPathMapping());
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP', '')]
    #[WithEnvVar('DDEV_APPROOT')]
    public function testGetPathMappingReturnsNullWhenEmpty(): void
    {
        self::assertNull(EnvironmentHelper::getPathMapping());
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP', '/var/www/html=/Users/me/Projects')]
    public function testGetPathMappingReturnsMappingWhenSet(): void
    {
        $mapping = EnvironmentHelper::getPathMapping();

        self::assertSame(['/var/www/html', '/Users/me/Projects'], $mapping);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP', '/var/www/html')]
    public function testGetPathMappingReturnsNullWhenInvalidFormat(): void
    {
        self::assertNull(EnvironmentHelper::getPathMapping());
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP', '=/Users/me/Projects')]
    public function testGetPathMappingReturnsNullWhenFromIsEmpty(): void
    {
        self::assertNull(EnvironmentHelper::getPathMapping());
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP', '/var/www/html=/Users/me/Pro=jects')]
    public function testGetPathMappingHandlesPathsWithEquals(): void
    {
        $mapping = EnvironmentHelper::getPathMapping();

        self::assertSame(['/var/www/html', '/Users/me/Pro=jects'], $mapping);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP')]
    #[WithEnvVar('DDEV_APPROOT', '/Users/me/Sites/myproject')]
    public function testGetPathMappingFallsToDdevAppRoot(): void
    {
        $mapping = EnvironmentHelper::getPathMapping();

        self::assertSame(['/var/www/html', '/Users/me/Sites/myproject'], $mapping);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP', '/opt/app=/Users/me/custom')]
    #[WithEnvVar('DDEV_APPROOT', '/Users/me/Sites/myproject')]
    public function testGetPathMappingPrefersExplicitOverDdev(): void
    {
        $mapping = EnvironmentHelper::getPathMapping();

        self::assertSame(['/opt/app', '/Users/me/custom'], $mapping);
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_SINK')]
    public function testGetSinkPathReturnsNullWhenEnvironmentVariableNotSet(): void
    {
        self::assertNull(EnvironmentHelper::getSinkPath());
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_SINK', '')]
    public function testGetSinkPathReturnsNullWhenEnvironmentVariableIsEmpty(): void
    {
        self::assertNull(EnvironmentHelper::getSinkPath());
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_SINK', '/tmp/dumps.ndjson')]
    public function testGetSinkPathReturnsEnvironmentVariableWhenSet(): void
    {
        self::assertSame('/tmp/dumps.ndjson', EnvironmentHelper::getSinkPath());
    }

    #[WithEnvVar('TYPO3_DUMP_SERVER_PATH_MAP')]
    #[WithEnvVar('DDEV_APPROOT', '')]
    public function testGetPathMappingIgnoresEmptyDdevAppRoot(): void
    {
        self::assertNull(EnvironmentHelper::getPathMapping());
    }
}
