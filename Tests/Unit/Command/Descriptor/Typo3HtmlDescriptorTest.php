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

use function strlen;

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

    #[Test]
    public function describeRendersHttpRequestUriAsLink(): void
    {
        $result = $this->describeWithContext([
            'timestamp' => microtime(true),
            'request' => [
                'method' => 'GET',
                'uri' => 'https://example.com/page',
            ],
        ]);

        self::assertStringContainsString('<a href="https://example.com/page">', $result);
    }

    #[Test]
    public function describeDoesNotLinkRequestUriWithUnsafeScheme(): void
    {
        $result = $this->describeWithContext([
            'timestamp' => microtime(true),
            'request' => [
                'method' => 'GET',
                'uri' => 'javascript:alert(1)',
            ],
        ]);

        self::assertStringNotContainsString('href=', $result);
        self::assertStringContainsString('javascript:alert(1)', $result);
    }

    #[Test]
    public function describeUsesFileLinkFromContext(): void
    {
        $result = $this->describeWithContext([
            'timestamp' => microtime(true),
            'source' => [
                'name' => 'TestController.php',
                'file' => '/var/www/html/Classes/Controller/TestController.php',
                'line' => 42,
                'file_link' => 'phpstorm://open?file=/var/www/html/Classes/Controller/TestController.php&line=42',
            ],
        ]);

        self::assertStringContainsString('<a href="phpstorm://open?file=', $result);
    }

    #[Test]
    public function describeIgnoresFileLinkWithUnsafeScheme(): void
    {
        $result = $this->describeWithContext([
            'timestamp' => microtime(true),
            'source' => [
                'name' => 'TestController.php',
                'file' => '/var/www/html/Classes/Controller/TestController.php',
                'line' => 42,
                'file_link' => 'javascript:alert(1)',
            ],
        ]);

        self::assertStringNotContainsString('href=', $result);
        self::assertStringContainsString('TestController.php on line 42', $result);
    }

    #[Test]
    public function describeIgnoresFileLinkWithoutScheme(): void
    {
        $result = $this->describeWithContext([
            'timestamp' => microtime(true),
            'source' => [
                'name' => 'TestController.php',
                'file' => '/var/www/html/Classes/Controller/TestController.php',
                'line' => 42,
                'file_link' => "java\tscript:alert(1)",
            ],
        ]);

        self::assertStringNotContainsString('href=', $result);
    }

    #[Test]
    public function describeOnlyRendersStylesOnFirstCall(): void
    {
        $descriptor = new Typo3HtmlDescriptor(new HtmlDumper());
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar('test');
        $context = ['timestamp' => microtime(true)];

        $descriptor->describe($output, $data, $context, 1);
        $firstCall = $output->fetch();

        $descriptor->describe($output, $data, $context, 2);
        $secondCall = $output->fetch();

        // Only the first call injects the shared htmlDescriptor.css/js assets,
        // so its output is necessarily larger than a repeat call's.
        self::assertGreaterThan(strlen($secondCall), strlen($firstCall));
    }

    #[Test]
    public function describeOutputsCliTitleAndDedupIdentifierWithoutRequest(): void
    {
        $result = $this->describeWithContext([
            'timestamp' => microtime(true),
            'cli' => [
                'identifier' => 'cli-1',
                'command_line' => 'bin/console some:command',
            ],
        ]);

        self::assertStringContainsString('bin/console some:command', $result);
        self::assertStringContainsString('data-dedup-id="cli-1"', $result);
    }

    #[Test]
    public function describeDefaultsCliTitleWhenCommandLineIsMissing(): void
    {
        $result = $this->describeWithContext([
            'timestamp' => microtime(true),
            'cli' => [
                'identifier' => 'cli-2',
            ],
        ]);

        self::assertStringContainsString('<code>$ </code>', $result);
    }

    #[Test]
    public function describeOutputsControllerAndDedupIdentifierFromRequest(): void
    {
        $cloner = new VarCloner();
        $result = $this->describeWithContext([
            'timestamp' => microtime(true),
            'request' => [
                'identifier' => 'req-1',
                'method' => 'GET',
                'uri' => '/some/path',
                'controller' => $cloner->cloneVar('SomeController::action'),
            ],
        ]);

        self::assertStringContainsString('data-dedup-id="req-1"', $result);
        self::assertStringContainsString('dumped-tag', $result);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function describeWithContext(array $context): string
    {
        $descriptor = new Typo3HtmlDescriptor(new HtmlDumper());
        $output = new BufferedOutput();
        $data = (new VarCloner())->cloneVar('test');

        $descriptor->describe($output, $data, $context, 1);

        return $output->fetch();
    }
}
