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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Command\Descriptor;

use KonradMichalik\Typo3DumpServer\Command\Descriptor\Typo3HtmlDescriptor;
use KonradMichalik\Typo3DumpServer\Utility\IdeLinkGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Command\Descriptor\DumpDescriptorInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Typo3HtmlDescriptorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class Typo3HtmlDescriptorTest extends TestCase
{
    #[Test]
    public function implementsDumpDescriptorInterface(): void
    {
        $descriptor = new Typo3HtmlDescriptor(new HtmlDumper());

        self::assertInstanceOf(DumpDescriptorInterface::class, $descriptor);
    }

    #[Test]
    public function describeOutputsTypo3ContextAsBadge(): void
    {
        $descriptor = new Typo3HtmlDescriptor(new HtmlDumper());
        $output = new BufferedOutput();
        $cloner = new VarCloner();
        $data = $cloner->cloneVar('test');

        $context = [
            'timestamp' => microtime(true),
            'typo3' => [
                'version' => '13.4.0',
                'context' => 'Development',
            ],
        ];

        $descriptor->describe($output, $data, $context, 1);
        $result = $output->fetch();

        self::assertStringContainsString('TYPO3 13.4.0 (Development)', $result);
        self::assertStringContainsString('badge', $result);
    }

    #[Test]
    public function describeGeneratesIdeLinkWhenConfigured(): void
    {
        $ideLinkGenerator = new IdeLinkGenerator('vscode');
        $descriptor = new Typo3HtmlDescriptor(new HtmlDumper(), $ideLinkGenerator);
        $output = new BufferedOutput();
        $cloner = new VarCloner();
        $data = $cloner->cloneVar('test');

        $context = [
            'timestamp' => microtime(true),
            'source' => [
                'name' => 'TestController.php',
                'file' => '/var/www/html/Classes/Controller/TestController.php',
                'line' => 42,
            ],
        ];

        $descriptor->describe($output, $data, $context, 1);
        $result = $output->fetch();

        self::assertStringContainsString('vscode://file/', $result);
        self::assertStringContainsString('href=', $result);
    }

    #[Test]
    public function describeOutputsSourceInfoWithoutIdeLink(): void
    {
        $descriptor = new Typo3HtmlDescriptor(new HtmlDumper());
        $output = new BufferedOutput();
        $cloner = new VarCloner();
        $data = $cloner->cloneVar('test');

        $context = [
            'timestamp' => microtime(true),
            'source' => [
                'name' => 'TestController.php',
                'file' => '/var/www/html/Classes/Controller/TestController.php',
                'line' => 42,
            ],
        ];

        $descriptor->describe($output, $data, $context, 1);
        $result = $output->fetch();

        self::assertStringContainsString('TestController.php on line 42', $result);
    }
}
