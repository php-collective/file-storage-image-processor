<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Test\TestCase\Processor\Image\Operation;

use PhpCollective\Infrastructure\Storage\Processor\Image\Exception\UnsupportedOperationException;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Cover;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Operation;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\OperationRegistry;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Resize;
use PhpCollective\Infrastructure\Storage\Processor\Image\Position;
use PhpCollective\Test\TestCase\TestCase;

/**
 * OperationRegistryTest
 */
class OperationRegistryTest extends TestCase
{
    /**
     * @return void
     */
    public function testDefaultRegistersAllBuiltInOperations(): void
    {
        $registry = OperationRegistry::default();
        $expected = [
            'cover', 'crop', 'resize', 'scale', 'heighten', 'widen',
            'rotate', 'sharpen', 'flip', 'flipHorizontal', 'flipVertical',
            'orient', 'brightness', 'contrast', 'grayscale', 'greyscale',
            'colorize', 'blur', 'pixelate', 'trim', 'resizeCanvas',
            'padding', 'place', 'convert', 'callback',
        ];

        foreach ($expected as $name) {
            $this->assertTrue($registry->has($name), "Expected '{$name}' to be registered");
        }
    }

    /**
     * @return void
     */
    public function testResolveReturnsTypedOperation(): void
    {
        $registry = OperationRegistry::default();

        $cover = $registry->resolve('cover', [
            'width' => 100,
            'height' => 100,
            'position' => 'top-left',
        ]);
        $this->assertInstanceOf(Cover::class, $cover);
        $this->assertSame(100, $cover->width);
        $this->assertSame(Position::TopLeft, $cover->position);
    }

    /**
     * @return void
     */
    public function testResolveAcceptsPositionEnumValueOrName(): void
    {
        $registry = OperationRegistry::default();
        $cover = $registry->resolve('cover', [
            'width' => 100,
            'height' => 100,
            'position' => Position::BottomRight,
        ]);
        $this->assertInstanceOf(Cover::class, $cover);
        $this->assertSame(Position::BottomRight, $cover->position);
    }

    /**
     * @return void
     */
    public function testResolveThrowsForUnknownName(): void
    {
        $registry = OperationRegistry::default();

        $this->expectException(UnsupportedOperationException::class);
        $registry->resolve('vips_blur', []);
    }

    /**
     * @return void
     */
    public function testRegisterReplacesPriorRegistration(): void
    {
        $registry = OperationRegistry::default();
        $registry->register('cover', static fn (array $a): Operation => new Resize(99, 99));

        $resolved = $registry->resolve('cover', ['width' => 1, 'height' => 1]);
        $this->assertInstanceOf(Resize::class, $resolved);
        $this->assertSame(99, $resolved->width);
    }

    /**
     * @return void
     */
    public function testRoundTripViaToArray(): void
    {
        $registry = OperationRegistry::default();
        $original = new Cover(150, 80, Position::TopLeft, preventUpscale: true);
        $rebuilt = $registry->resolve('cover', $original->toArray());

        $this->assertEquals($original, $rebuilt);
    }

    /**
     * @return void
     */
    public function testGreyscaleAliasResolvesToGrayscaleOperation(): void
    {
        $registry = OperationRegistry::default();
        $a = $registry->resolve('grayscale', []);
        $b = $registry->resolve('greyscale', []);
        $this->assertSame($a::class, $b::class);
    }

    /**
     * @return void
     */
    public function testNamesReturnsAllRegistered(): void
    {
        $registry = new OperationRegistry();
        $registry
            ->register('foo', static fn (array $a): Operation => new Resize(1, 1))
            ->register('bar', static fn (array $a): Operation => new Resize(2, 2));

        $this->assertSame(['foo', 'bar'], $registry->names());
    }
}
