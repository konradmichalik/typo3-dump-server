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

namespace KonradMichalik\Typo3DumpServer\Utility;

/**
 * EnvironmentHelper.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class EnvironmentHelper
{
    private const DEFAULT_HOST = 'tcp://127.0.0.1:9912';

    public static function getHost(): string
    {
        $host = getenv('TYPO3_DUMP_SERVER_HOST');

        if (false === $host) {
            $host = self::DEFAULT_HOST;
        }

        return $host;
    }

    public static function getIde(): ?string
    {
        $ide = getenv('TYPO3_DUMP_SERVER_IDE');

        if (false === $ide || '' === $ide) {
            return null;
        }

        return $ide;
    }
}
