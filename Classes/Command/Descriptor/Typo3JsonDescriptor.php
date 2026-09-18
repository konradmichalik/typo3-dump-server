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

namespace KonradMichalik\Typo3DumpServer\Command\Descriptor;

use KonradMichalik\Typo3DumpServer\Dumper\DumpPayloadFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Command\Descriptor\DumpDescriptorInterface;

use function json_encode;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;

/**
 * Typo3JsonDescriptor.
 *
 * Renders each dump as a single NDJSON line so it can be consumed by
 * machine readers (e.g. AI coding agents) instead of only formatted
 * terminal output.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class Typo3JsonDescriptor implements DumpDescriptorInterface
{
    public function __construct(
        private DumpPayloadFactory $payloadFactory,
    ) {}

    public static function withDefaults(): self
    {
        return new self(DumpPayloadFactory::withDefaults());
    }

    public function describe(OutputInterface $output, Data $data, array $context, int $clientId): void
    {
        /** @var array<string, mixed> $context */
        $payload = ['clientId' => $clientId] + $this->payloadFactory->create($data, $context);

        $json = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $output->writeln(false !== $json ? $json : '{}');
    }
}
