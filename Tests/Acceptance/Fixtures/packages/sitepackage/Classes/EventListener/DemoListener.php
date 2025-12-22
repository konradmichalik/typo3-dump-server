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

namespace Test\Sitepackage\EventListener;

use KonradMichalik\Typo3DumpServer\Event\DumpEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

#[AsEventListener]
/**
 * DemoListener.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class DemoListener
{
    public function __invoke(DumpEvent $event): void
    {
        $value = $event->getValue();
        $type = $event->getType();

        // DebuggerUtility::var_dump($value, $type);
    }
}
