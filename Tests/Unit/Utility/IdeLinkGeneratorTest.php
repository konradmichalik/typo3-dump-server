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

use KonradMichalik\Typo3DumpServer\Utility\IdeLinkGenerator;
use PHPUnit\Framework\Attributes\{DataProvider, Test};
use PHPUnit\Framework\TestCase;

/**
 * IdeLinkGeneratorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class IdeLinkGeneratorTest extends TestCase
{
    #[Test]
    public function generateReturnsPhpstormLink(): void
    {
        $generator = new IdeLinkGenerator('phpstorm');

        self::assertSame(
            'phpstorm://open?file=/var/www/html/test.php&line=42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    #[Test]
    public function generateReturnsVscodeLink(): void
    {
        $generator = new IdeLinkGenerator('vscode');

        self::assertSame(
            'vscode://file//var/www/html/test.php:42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    #[Test]
    public function generateReturnsSublimeLink(): void
    {
        $generator = new IdeLinkGenerator('sublime');

        self::assertSame(
            'subl://open?url=file:///var/www/html/test.php&line=42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    #[Test]
    public function generateReturnsTextmateLink(): void
    {
        $generator = new IdeLinkGenerator('textmate');

        self::assertSame(
            'txmt://open?url=file:///var/www/html/test.php&line=42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    #[Test]
    public function generateReturnsAtomLink(): void
    {
        $generator = new IdeLinkGenerator('atom');

        self::assertSame(
            'atom://core/open/file?filename=/var/www/html/test.php&line=42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    #[Test]
    public function generateUsesCustomPatternWhenIdeIsUnknown(): void
    {
        $generator = new IdeLinkGenerator('myide://open?file=%file%&line=%line%');

        self::assertSame(
            'myide://open?file=/var/www/html/test.php&line=42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function isSupportedDataProvider(): array
    {
        return [
            'phpstorm' => ['phpstorm', true],
            'vscode' => ['vscode', true],
            'sublime' => ['sublime', true],
            'textmate' => ['textmate', true],
            'atom' => ['atom', true],
            'custom pattern' => ['myide://open?file=%file%&line=%line%', true],
            'unknown without pattern' => ['unknown', false],
            'empty string' => ['', false],
        ];
    }

    #[Test]
    public function generateAppliesPathMapping(): void
    {
        $generator = new IdeLinkGenerator('phpstorm', '/var/www/html', '/Users/me/Projects');

        self::assertSame(
            'phpstorm://open?file=/Users/me/Projects/test.php&line=42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    #[Test]
    public function generateIgnoresPathMappingWhenNotConfigured(): void
    {
        $generator = new IdeLinkGenerator('phpstorm');

        self::assertSame(
            'phpstorm://open?file=/var/www/html/test.php&line=42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    #[Test]
    public function generateKeepsOriginalPathWhenMappingDoesNotMatch(): void
    {
        $generator = new IdeLinkGenerator('phpstorm', '/opt/app', '/Users/me/Projects');

        self::assertSame(
            'phpstorm://open?file=/var/www/html/test.php&line=42',
            $generator->generate('/var/www/html/test.php', 42),
        );
    }

    #[Test]
    #[DataProvider('isSupportedDataProvider')]
    public function isSupportedReturnsExpectedResult(string $ide, bool $expected): void
    {
        self::assertSame($expected, IdeLinkGenerator::isSupported($ide));
    }
}
