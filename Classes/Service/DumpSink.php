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

namespace KonradMichalik\Typo3DumpServer\Service;

use KonradMichalik\Typo3DumpServer\Dumper\DumpPayloadFactory;
use Symfony\Component\VarDumper\Cloner\Data;

use function chmod;
use function fclose;
use function flock;
use function fopen;
use function fseek;
use function fstat;
use function ftruncate;
use function fwrite;
use function json_encode;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const LOCK_EX;
use const LOCK_UN;
use const SEEK_END;

/**
 * DumpSink.
 *
 * Appends dumps as NDJSON lines to a file, so a dump can be produced without
 * a running `server:dump` process, letting an AI coding agent read the file
 * directly instead of managing a background listener.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class DumpSink
{
    public const DEFAULT_MAX_FILE_SIZE = 5 * 1024 * 1024;

    public function __construct(
        private DumpPayloadFactory $payloadFactory,
        private int $maxFileSize,
    ) {}

    public static function withDefaults(): self
    {
        return new self(DumpPayloadFactory::withDefaults(), self::DEFAULT_MAX_FILE_SIZE);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function write(string $path, Data $data, array $context): void
    {
        $json = json_encode(
            $this->payloadFactory->create($data, $context),
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR,
        );
        $line = (false !== $json ? $json : '{}')."\n";

        // 'c' creates the file if missing without truncating an existing one,
        // so the exclusive lock below covers the size check and the write.
        $handle = fopen($path, 'c');
        if (false === $handle) {
            return;
        }

        chmod($path, 0600);

        if (flock($handle, LOCK_EX)) {
            $stat = fstat($handle);
            if (false !== $stat && $stat['size'] >= $this->maxFileSize) {
                ftruncate($handle, 0);
            }

            fseek($handle, 0, SEEK_END);
            fwrite($handle, $line);
            flock($handle, LOCK_UN);
        }

        fclose($handle);
    }
}
