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

namespace KonradMichalik\Typo3DumpServer\Dumper\ContextProvider;

use Symfony\Component\VarDumper\Dumper\ContextProvider\ContextProviderInterface;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Typo3ContextProvider.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class Typo3ContextProvider implements ContextProviderInterface
{
    /**
     * @return array{version: string, context: string}|null
     */
    public function getContext(): ?array
    {
        try {
            return [
                'version' => $this->getTypo3Version(),
                'context' => (string) Environment::getContext(),
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function getTypo3Version(): string
    {
        return (new Typo3Version())->getVersion();
    }
}
