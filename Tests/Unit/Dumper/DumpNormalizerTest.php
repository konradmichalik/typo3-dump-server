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

namespace KonradMichalik\Typo3DumpServer\Tests\Unit\Dumper;

use KonradMichalik\Typo3DumpServer\Dumper\DumpNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\{Data, Stub, VarCloner};

/**
 * DumpNormalizerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpNormalizerTest extends TestCase
{
    #[Test]
    public function normalizesScalarString(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $data = (new VarCloner())->cloneVar('hello');

        self::assertSame('hello', $normalizer->normalize($data));
    }

    #[Test]
    public function normalizesInteger(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $data = (new VarCloner())->cloneVar(42);

        self::assertSame(42, $normalizer->normalize($data));
    }

    #[Test]
    public function normalizesFloat(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $data = (new VarCloner())->cloneVar(4.2);

        self::assertSame(4.2, $normalizer->normalize($data));
    }

    #[Test]
    public function normalizesBoolean(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $data = (new VarCloner())->cloneVar(true);

        self::assertTrue($normalizer->normalize($data));
    }

    #[Test]
    public function normalizesNull(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $data = (new VarCloner())->cloneVar(null);

        self::assertNull($normalizer->normalize($data));
    }

    #[Test]
    public function normalizesFlatIndexedArray(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $data = (new VarCloner())->cloneVar([1, 2, 3]);

        self::assertSame([1, 2, 3], $normalizer->normalize($data));
    }

    #[Test]
    public function normalizesNestedAssociativeArray(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $data = (new VarCloner())->cloneVar(['a' => 1, 'b' => ['c' => 2]]);

        self::assertSame(['a' => 1, 'b' => ['c' => 2]], $normalizer->normalize($data));
    }

    #[Test]
    public function normalizesObjectWithClassNameAndCleanedPropertyKeys(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $object = new DumpNormalizerFixture();
        $data = (new VarCloner())->cloneVar($object);

        self::assertSame(
            [
                '__class' => DumpNormalizerFixture::class,
                'publicProp' => 'pub',
                'protectedProp' => 'prot',
                'privateProp' => 'priv',
            ],
            $normalizer->normalize($data),
        );
    }

    #[Test]
    public function truncatesStringsLongerThanConfiguredLimit(): void
    {
        $normalizer = new DumpNormalizer(DumpNormalizer::DEFAULT_MAX_DEPTH, DumpNormalizer::DEFAULT_MAX_ITEMS, 5);
        $data = (new VarCloner())->cloneVar('abcdefgh');

        self::assertSame('abcde*TRUNCATED*', $normalizer->normalize($data));
    }

    #[Test]
    public function keepsStringsAtOrBelowConfiguredLimitUntouched(): void
    {
        $normalizer = new DumpNormalizer(DumpNormalizer::DEFAULT_MAX_DEPTH, DumpNormalizer::DEFAULT_MAX_ITEMS, 5);
        $data = (new VarCloner())->cloneVar('abcde');

        self::assertSame('abcde', $normalizer->normalize($data));
    }

    #[Test]
    public function marksArraysBeyondMaxDepthInsteadOfRecursing(): void
    {
        $normalizer = new DumpNormalizer(1, DumpNormalizer::DEFAULT_MAX_ITEMS, DumpNormalizer::DEFAULT_MAX_STRING_LENGTH);
        $data = (new VarCloner())->cloneVar(['a' => ['b' => 'deep']]);

        self::assertSame(
            ['a' => '*MAX_DEPTH:array*'],
            $normalizer->normalize($data),
        );
    }

    #[Test]
    public function marksObjectsBeyondMaxDepthInsteadOfRecursing(): void
    {
        $normalizer = new DumpNormalizer(0, DumpNormalizer::DEFAULT_MAX_ITEMS, DumpNormalizer::DEFAULT_MAX_STRING_LENGTH);
        $data = (new VarCloner())->cloneVar(new DumpNormalizerFixture());

        self::assertSame('*MAX_DEPTH:'.DumpNormalizerFixture::class.'*', $normalizer->normalize($data));
    }

    #[Test]
    public function truncatesArraysBeyondMaxItems(): void
    {
        $normalizer = new DumpNormalizer(DumpNormalizer::DEFAULT_MAX_DEPTH, 2, DumpNormalizer::DEFAULT_MAX_STRING_LENGTH);
        $data = (new VarCloner())->cloneVar(['a' => 1, 'b' => 2, 'c' => 3]);

        self::assertSame(
            ['a' => 1, 'b' => 2, '*TRUNCATED*' => true],
            $normalizer->normalize($data),
        );
    }

    #[Test]
    public function normalizesResourceAsMarkerString(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $resource = fopen('php://memory', 'r');
        self::assertNotFalse($resource);
        $data = (new VarCloner())->cloneVar($resource);
        fclose($resource);

        self::assertSame('*RESOURCE:stream*', $normalizer->normalize($data));
    }

    #[Test]
    public function normalizesUntypedStubAsNull(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        // A Stub whose type Data::getType() cannot resolve (neither string,
        // array, object, resource, nor a reference) normalizes to null.
        $stub = new Stub();
        $stub->type = 0;
        $data = new Data([[$stub]]);

        self::assertNull($normalizer->normalize($data));
    }

    #[Test]
    public function normalizesClosureAsObjectMarkerWithClassName(): void
    {
        $normalizer = DumpNormalizer::withDefaults();
        $data = (new VarCloner())->cloneVar(static fn (): int => 1);

        $result = $normalizer->normalize($data);

        self::assertIsArray($result);
        self::assertArrayHasKey('__class', $result);
        // The exact class label for a closure differs across supported
        // symfony/var-dumper versions; only its presence is part of the contract.
        self::assertIsString($result['__class']);
        self::assertNotSame('', $result['__class']);
    }
}

/**
 * DumpNormalizerFixture.
 *
 * @internal
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class DumpNormalizerFixture
{
    public string $publicProp = 'pub';
    private string $protectedProp = 'prot';
    private string $privateProp = 'priv';
}
