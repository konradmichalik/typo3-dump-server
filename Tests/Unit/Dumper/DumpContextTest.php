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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Dumper;

use KonradMichalik\Typo3DumpServer\Dumper\DumpContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * DumpContextTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpContextTest extends TestCase
{
    #[Test]
    public function extractTimestampReturnsIntegerTimestamp(): void
    {
        self::assertSame(1758182400, DumpContext::extractTimestamp(['timestamp' => 1758182400]));
    }

    #[Test]
    public function extractTimestampCastsFloatTimestampToInt(): void
    {
        self::assertSame(1758182400, DumpContext::extractTimestamp(['timestamp' => 1758182400.789]));
    }

    #[Test]
    public function extractTimestampDefaultsToZeroWhenMissing(): void
    {
        self::assertSame(0, DumpContext::extractTimestamp([]));
    }

    #[Test]
    public function extractTimestampDefaultsToZeroForInvalidType(): void
    {
        self::assertSame(0, DumpContext::extractTimestamp(['timestamp' => 'not-a-number']));
    }
}
