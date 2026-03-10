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

use function count;

/**
 * EnvironmentHelper.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class EnvironmentHelper
{
    private const DEFAULT_HOST = 'tcp://127.0.0.1:9912';

    private const DDEV_CONTAINER_ROOT = '/var/www/html';

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

    /**
     * @return array{string, string}|null
     */
    public static function getPathMapping(): ?array
    {
        $mapping = getenv('TYPO3_DUMP_SERVER_PATH_MAP');

        if (false !== $mapping && '' !== $mapping) {
            $parts = explode('=', $mapping, 2);

            if (2 === count($parts) && '' !== $parts[0] && '' !== $parts[1]) {
                return [$parts[0], $parts[1]];
            }

            return null;
        }

        $ddevAppRoot = getenv('DDEV_APPROOT');

        if (false !== $ddevAppRoot && '' !== $ddevAppRoot) {
            return [self::DDEV_CONTAINER_ROOT, $ddevAppRoot];
        }

        return null;
    }
}
