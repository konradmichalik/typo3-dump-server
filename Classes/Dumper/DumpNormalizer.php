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

use Symfony\Component\VarDumper\Cloner\Data;

use function end;
use function explode;
use function in_array;
use function is_int;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * DumpNormalizer.
 *
 * Turns a Symfony VarDumper Data tree into a plain, JSON-encodable structure
 * so dumps can be consumed by machine readers (e.g. AI coding agents) instead
 * of only by ANSI-formatted terminal output.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpNormalizer
{
    public const DEFAULT_MAX_DEPTH = 10;

    public const DEFAULT_MAX_ITEMS = 100;

    public const DEFAULT_MAX_STRING_LENGTH = 5000;

    public function __construct(
        private readonly int $maxDepth,
        private readonly int $maxItems,
        private readonly int $maxStringLength,
    ) {}

    public static function withDefaults(): self
    {
        return new self(self::DEFAULT_MAX_DEPTH, self::DEFAULT_MAX_ITEMS, self::DEFAULT_MAX_STRING_LENGTH);
    }

    public function normalize(Data $data): mixed
    {
        return $this->normalizeNode($data, 0);
    }

    private function normalizeNode(Data $node, int $depth): mixed
    {
        $type = $node->getType();

        if (null === $type) {
            return null;
        }

        if (in_array($type, ['integer', 'double', 'boolean', 'NULL'], true)) {
            return $node->getValue();
        }

        if ('string' === $type) {
            $value = $node->getValue();

            return $this->truncateString(is_string($value) ? $value : '');
        }

        if (str_ends_with($type, ' resource')) {
            return sprintf('*RESOURCE:%s*', substr($type, 0, -strlen(' resource')));
        }

        if ($depth >= $this->maxDepth) {
            return sprintf('*MAX_DEPTH:%s*', $type);
        }

        if ('array' === $type) {
            return $this->normalizeChildren($node, $depth);
        }

        return [
            '__class' => $type,
            ...$this->normalizeChildren($node, $depth),
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    private function normalizeChildren(Data $node, int $depth): array
    {
        $result = [];
        $count = 0;

        foreach ($node as $key => $child) {
            if (!is_int($key) && !is_string($key)) {
                // Data's iterator only ever yields int|string keys (PHP array key semantics).
                continue;
            }

            if ($count >= $this->maxItems) {
                $result['*TRUNCATED*'] = true;
                break;
            }

            $result[$this->normalizeKey($key)] = $child instanceof Data
                ? $this->normalizeNode($child, $depth + 1)
                : $child;
            ++$count;
        }

        return $result;
    }

    private function normalizeKey(int|string $key): int|string
    {
        if (!is_string($key) || !str_starts_with($key, "\0")) {
            return $key;
        }

        $parts = explode("\0", $key);

        return end($parts);
    }

    private function truncateString(string $value): string
    {
        if (mb_strlen($value) <= $this->maxStringLength) {
            return $value;
        }

        return mb_substr($value, 0, $this->maxStringLength).'*TRUNCATED*';
    }
}
