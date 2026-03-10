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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit;

use KonradMichalik\Typo3DumpServer\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * ConfigurationTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ConfigurationTest extends TestCase
{
    public function testExtKeyConstant(): void
    {
        self::assertSame('typo3_dump_server', Configuration::EXT_KEY);
    }

    public function testExtNameConstant(): void
    {
        self::assertSame('Typo3DumpServer', Configuration::EXT_NAME);
    }
}
