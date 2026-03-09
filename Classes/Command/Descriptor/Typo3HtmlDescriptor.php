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

use KonradMichalik\Typo3DumpServer\Utility\IdeLinkGenerator;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Command\Descriptor\{DumpDescriptorInterface, HtmlDescriptor as SymfonyHtmlDescriptor};
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

use function bin2hex;
use function date;
use function dirname;
use function file_get_contents;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function random_bytes;
use function sprintf;

/**
 * Typo3HtmlDescriptor.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class Typo3HtmlDescriptor implements DumpDescriptorInterface
{
    private bool $initialized = false;

    public function __construct(
        private readonly HtmlDumper $dumper,
        private readonly ?IdeLinkGenerator $ideLinkGenerator = null,
    ) {}

    /** @phpstan-ignore missingType.iterableValue */
    public function describe(OutputInterface $output, Data $data, array $context, int $clientId): void
    {
        $this->initialize($output);

        /** @var array<string, mixed> $context */
        $title = $this->resolveTitle($context);
        $dedupIdentifier = $this->resolveDedupIdentifier($context);
        $controller = $this->resolveController($context);
        $projectDir = $this->resolveProjectDir($context);
        $sourceDescription = $this->resolveSourceDescription($context);
        $typo3Info = $this->resolveTypo3Info($context);
        $timestamp = $this->extractTimestamp($context);

        $isoDate = date('c', $timestamp);
        $readableDate = date('r', $timestamp);
        $tags = array_filter(
            [
                'controller' => $controller,
                'project dir' => $projectDir,
                'TYPO3' => $typo3Info,
            ],
            static fn (?string $value): bool => null !== $value,
        );

        $output->writeln(<<<HTML
            <article data-dedup-id="{$dedupIdentifier}">
                <header>
                    <div class="row">
                        <h2 class="col">{$title}</h2>
                        <time class="col text-small" title="{$isoDate}" datetime="{$isoDate}">
                            {$readableDate}
                        </time>
                    </div>
                    {$this->renderTags($tags)}
                </header>
                <section class="body">
                    <p class="text-small">
                        {$sourceDescription}
                    </p>
                    {$this->dumper->dump($data, true)}
                </section>
            </article>
            HTML
        );
    }

    private function initialize(OutputInterface $output): void
    {
        if ($this->initialized) {
            return;
        }

        $resourcesDir = dirname((string) (new ReflectionClass(SymfonyHtmlDescriptor::class))->getFileName(), 3)
            .'/Resources';
        $styles = (string) file_get_contents($resourcesDir.'/css/htmlDescriptor.css');
        $scripts = (string) file_get_contents($resourcesDir.'/js/htmlDescriptor.js');
        $output->writeln("<style>{$styles}</style><script>{$scripts}</script>");
        $this->initialized = true;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveTitle(array $context): string
    {
        if (isset($context['request']) && is_array($context['request'])) {
            $method = is_string($context['request']['method'] ?? null) ? $context['request']['method'] : '';
            $uri = is_string($context['request']['uri'] ?? null) ? $context['request']['uri'] : '';

            return sprintf('<code>%s</code> <a href="%s">%s</a>', $method, $uri, $uri);
        }

        if (isset($context['cli']) && is_array($context['cli'])) {
            $commandLine = is_string($context['cli']['command_line'] ?? null)
                ? $context['cli']['command_line']
                : '';

            return '<code>$ </code>'.$commandLine;
        }

        return '-';
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveDedupIdentifier(array $context): string
    {
        if (isset($context['request']) && is_array($context['request'])) {
            $identifier = $context['request']['identifier'] ?? null;
            if (is_string($identifier)) {
                return $identifier;
            }
        }

        if (isset($context['cli']) && is_array($context['cli'])) {
            $identifier = $context['cli']['identifier'] ?? null;
            if (is_string($identifier)) {
                return $identifier;
            }
        }

        return bin2hex(random_bytes(4));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveController(array $context): ?string
    {
        if (!isset($context['request']) || !is_array($context['request'])) {
            return null;
        }

        $controller = $context['request']['controller'] ?? null;
        if (!$controller instanceof Data) {
            return null;
        }

        return sprintf(
            "<span class='dumped-tag'>%s</span>",
            $this->dumper->dump($controller, true, ['maxDepth' => 0]),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveProjectDir(array $context): ?string
    {
        if (!isset($context['source']) || !is_array($context['source'])) {
            return null;
        }

        $projectDir = $context['source']['project_dir'] ?? null;

        return is_string($projectDir) ? $projectDir : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveSourceDescription(array $context): string
    {
        if (!isset($context['source']) || !is_array($context['source'])) {
            return '';
        }

        /** @var array<string, mixed> $source */
        $source = $context['source'];
        $name = is_string($source['name'] ?? null) ? $source['name'] : '-';
        $line = $this->extractLineNumber($source);
        $description = sprintf('%s on line %d', $name, $line);

        $fileLink = $this->resolveFileLink($source);
        if (null !== $fileLink) {
            $description = sprintf('<a href="%s">%s</a>', $fileLink, $description);
        }

        return $description;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveTypo3Info(array $context): ?string
    {
        if (!isset($context['typo3']) || !is_array($context['typo3'])) {
            return null;
        }

        $typo3 = $context['typo3'];
        $version = is_string($typo3['version'] ?? null) ? $typo3['version'] : '-';
        $appContext = is_string($typo3['context'] ?? null) ? $typo3['context'] : '-';

        return sprintf('TYPO3 %s (%s)', $version, $appContext);
    }

    /**
     * @param array<string, mixed> $source
     */
    private function resolveFileLink(array $source): ?string
    {
        if (null !== $this->ideLinkGenerator) {
            $file = is_string($source['file'] ?? null) ? $source['file'] : null;
            $line = $this->extractLineNumber($source);

            if (null !== $file && 0 !== $line) {
                return $this->ideLinkGenerator->generate($file, $line);
            }
        }

        $fileLink = $source['file_link'] ?? null;

        return is_string($fileLink) ? $fileLink : null;
    }

    /**
     * @param array<string, mixed> $source
     */
    private function extractLineNumber(array $source): int
    {
        $line = $source['line'] ?? 0;

        return is_int($line) ? $line : 0;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function extractTimestamp(array $context): int
    {
        return isset($context['timestamp']) && is_float($context['timestamp'])
            ? (int) $context['timestamp']
            : 0;
    }

    /**
     * @param array<string, string> $tags
     */
    private function renderTags(array $tags): string
    {
        if ([] === $tags) {
            return '';
        }

        $renderedTags = '';
        foreach ($tags as $key => $value) {
            $renderedTags .= sprintf('<li><span class="badge">%s</span>%s</li>', $key, $value);
        }

        return <<<HTML
            <div class="row">
                <ul class="tags">
                    {$renderedTags}
                </ul>
            </div>
            HTML;
    }
}
