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

return [
    'frontend' => [
        'test/sitepackage' => [
            'target' => Test\Sitepackage\Middleware\DemoMiddleware::class,
            'after' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
    ],
];
