<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_dump_server" TYPO3 CMS extension.
 *
 * (c) 2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KonradMichalik\Typo3DumpServer\Event;

use function gettype;

/**
 * DumpEvent.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class DumpEvent
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private mixed $value,
        private array $context = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the dumped variable value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get the type of the dumped variable.
     */
    public function getType(): string
    {
        return gettype($this->value);
    }
}
