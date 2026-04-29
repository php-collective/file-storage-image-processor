<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Test\TestCase\Processor\Image\Operation;

use Intervention\Image\Direction;
use Intervention\Image\Interfaces\ImageInterface;
use PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Blur;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Brightness;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Callback;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Colorize;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Contrast;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Convert;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Cover;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Crop;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Flip;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\FlipHorizontal;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\FlipVertical;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Grayscale;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Heighten;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\OperationContext;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Orient;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Padding;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Pixelate;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Place;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Resize;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\ResizeCanvas;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Rotate;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Scale;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Sharpen;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Trim;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Widen;
use PhpCollective\Infrastructure\Storage\Processor\Image\Position;
use PhpCollective\Test\TestCase\TestCase;

/**
 * OperationTest
 *
 * Verifies each Operation class forwards its constructor parameters
 * to the right intervention/image method when applied to a context.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class OperationTest extends TestCase
{
    /**
     * @return void
     */
    public function testResize(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('resize')->with(100, 200);
        (new Resize(100, 200))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testResizePreventUpscale(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('resizeDown')->with(100, 200);
        (new Resize(100, 200, preventUpscale: true))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testScale(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('scale')->with(100, 200);
        (new Scale(100, 200))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testHeighten(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('scale')->with(null, 200);
        (new Heighten(200))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testWiden(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('scale')->with(100, null);
        (new Widen(100))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testCover(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('cover')->with(100, 100, 'center');
        (new Cover(100, 100))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testCoverPreventUpscale(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('coverDown')->with(100, 100, 'top-left');
        (new Cover(100, 100, Position::TopLeft, preventUpscale: true))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testCropWithoutCoordinates(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())
            ->method('crop')
            ->with(100, 200, 0, 0, null, 'center');
        (new Crop(100, 200))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testCropWithCoordinates(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())
            ->method('crop')
            ->with(100, 200, 5, 10, null, 'top-left');
        (new Crop(100, 200, 5, 10, Position::TopLeft))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testRotate(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('rotate')->with(90);
        (new Rotate(90))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testSharpen(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('sharpen')->with(50);
        (new Sharpen(50))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testFlipHorizontal(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('flip')->with(Direction::HORIZONTAL);
        (new FlipHorizontal())->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testFlipVertical(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('flip')->with(Direction::VERTICAL);
        (new FlipVertical())->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testFlip(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('flip')->with(Direction::HORIZONTAL);
        (new Flip(FlipDirection::Horizontal))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testOrient(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('orient');
        (new Orient())->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testBrightness(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('brightness')->with(20);
        (new Brightness(20))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testContrast(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('contrast')->with(-15);
        (new Contrast(-15))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testGrayscale(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('grayscale');
        (new Grayscale())->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testColorize(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('colorize')->with(10, -5, 20);
        (new Colorize(10, -5, 20))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testBlur(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('blur')->with(7);
        (new Blur(7))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testPixelate(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('pixelate')->with(8);
        (new Pixelate(8))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testTrim(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('trim')->with(15);
        (new Trim(15))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testResizeCanvas(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('resizeCanvas')->with(200, 200, '#ffffff', 'center');
        (new ResizeCanvas(200, 200, '#ffffff'))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testPaddingFromAmount(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('resizeCanvasRelative')->with(20, 20, null, 'center');
        (new Padding(amount: 10))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testPaddingExplicitDimensions(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('resizeCanvasRelative')->with(40, 10, '#000', 'center');
        (new Padding(width: 40, height: 10, background: '#000'))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testPlace(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $image->expects($this->once())->method('insert')->with('/tmp/wm.png', 5, 10, 'bottom-right', 0.5);
        (new Place('/tmp/wm.png', Position::BottomRight, 5, 10, 0.5))->apply(new OperationContext($image));
    }

    /**
     * @return void
     */
    public function testConvertSetsContextOutputFormat(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $context = new OperationContext($image);

        $this->assertNull($context->outputFormat);
        (new Convert('WEBP'))->apply($context);
        $this->assertSame('webp', $context->outputFormat);
    }

    /**
     * @return void
     */
    public function testCallback(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $called = false;
        $callback = function ($passed) use ($image, &$called): void {
            $this->assertSame($image, $passed);
            $called = true;
        };

        (new Callback($callback))->apply(new OperationContext($image));
        $this->assertTrue($called);
    }
}
