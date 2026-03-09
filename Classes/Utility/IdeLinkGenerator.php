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

use function str_contains;
use function str_replace;

/**
 * IdeLinkGenerator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class IdeLinkGenerator
{
    /**
     * @var array<string, string>
     */
    private const IDE_PATTERNS = [
        'phpstorm' => 'phpstorm://open?file=%file%&line=%line%',
        'vscode' => 'vscode://file/%file%:%line%',
        'sublime' => 'subl://open?url=file://%file%&line=%line%',
        'textmate' => 'txmt://open?url=file://%file%&line=%line%',
        'atom' => 'atom://core/open/file?filename=%file%&line=%line%',
    ];

    private readonly string $pattern;

    public function __construct(string $ide)
    {
        $this->pattern = self::IDE_PATTERNS[$ide] ?? $ide;
    }

    public function generate(string $file, int $line): string
    {
        return str_replace(
            ['%file%', '%line%'],
            [$file, (string) $line],
            $this->pattern,
        );
    }

    public static function isSupported(string $ide): bool
    {
        if (isset(self::IDE_PATTERNS[$ide])) {
            return true;
        }

        return str_contains($ide, '%file%');
    }
}
