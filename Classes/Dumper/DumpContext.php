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

namespace KonradMichalik\Typo3DumpServer\Dumper;

use function is_float;
use function is_int;

/**
 * DumpContext.
 *
 * Shared helpers for reading values out of a VarDumper dump context array,
 * used by every descriptor and payload builder that renders one.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpContext
{
    /**
     * @param array<string, mixed> $context
     */
    public static function extractTimestamp(array $context): int
    {
        $timestamp = $context['timestamp'] ?? null;

        return is_int($timestamp) || is_float($timestamp) ? (int) $timestamp : 0;
    }
}
