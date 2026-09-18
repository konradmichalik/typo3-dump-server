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

use function date;
use function is_array;

/**
 * DumpPayloadFactory.
 *
 * Builds the JSON-encodable payload shape shared by every machine-readable
 * dump target (the `--format=json` descriptor and the file sink), so both
 * emit the same schema for agents to consume.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpPayloadFactory
{
    public function __construct(
        private readonly DumpNormalizer $normalizer,
    ) {}

    public static function withDefaults(): self
    {
        return new self(DumpNormalizer::withDefaults());
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function create(Data $data, array $context): array
    {
        return [
            'timestamp' => date('c', DumpContext::extractTimestamp($context)),
            'type' => $data->getType(),
            'value' => $this->normalizer->normalize($data),
            'source' => $this->resolveContextSection($context, 'source'),
            'request' => $this->resolveRequest($context),
            'cli' => $this->resolveContextSection($context, 'cli'),
            'typo3' => $this->resolveContextSection($context, 'typo3'),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<array-key, mixed>|null
     */
    private function resolveRequest(array $context): ?array
    {
        $request = $this->resolveContextSection($context, 'request');
        if (null === $request) {
            return null;
        }

        $controller = $request['controller'] ?? null;
        if ($controller instanceof Data) {
            $request['controller'] = $this->normalizer->normalize($controller);
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<array-key, mixed>|null
     */
    private function resolveContextSection(array $context, string $key): ?array
    {
        $section = $context[$key] ?? null;

        return is_array($section) ? $section : null;
    }
}
