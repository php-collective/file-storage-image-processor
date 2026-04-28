<?php declare(strict_types = 1);

/**
 * Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * @author Florian Krämer
 * @link https://github.com/Phauthentic
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Test\TestCase\Processor\Image;

use Intervention\Image\Interfaces\ImageInterface;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operations;
use PhpCollective\Test\TestCase\TestCase;

/**
 * OperationsTest
 */
class OperationsTest extends TestCase
{
    /**
     * @return void
     */
    public function testOperations(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);

        $imageMock->expects($this->once())
            ->method('resize')
            ->with(100, 100);

        $operations = new Operations($imageMock);
        $operations->resize(['height' => 100, 'width' => 100]);
    }

    /**
     * @return void
     */
    public function testScale(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);

        $imageMock->expects($this->once())
            ->method('scale')
            ->with(100, 200);

        $operations = new Operations($imageMock);
        $operations->scale(['height' => 200, 'width' => 100]);
    }

    /**
     * @return void
     */
    public function testRotate(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);

        $imageMock->expects($this->once())
            ->method('rotate')
            ->with(90);

        $operations = new Operations($imageMock);
        $operations->rotate(['angle' => 90]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing angle');
        $operations->rotate([]);
    }

    /**
     * @return void
     */
    public function testSharpen(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);

        $imageMock->expects($this->once())
            ->method('sharpen')
            ->with(90);

        $operations = new Operations($imageMock);
        $operations->sharpen(['amount' => 90]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing amount');
        $operations->sharpen([]);
    }

    /**
     * @return void
     */
    public function testCover(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);

        $imageMock->expects($this->once())
            ->method('cover')
            ->with(100, 100, 'center');

        $operations = new Operations($imageMock);
        $operations->cover(['width' => 100, 'height' => 100]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width or height');
        $operations->cover([]);
    }

    /**
     * @return void
     */
    public function testCrop(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);

        $imageMock->expects($this->once())
            ->method('crop')
            ->with(
                $this->equalTo(100),
                $this->equalTo(200),
                $this->anything(),
            );

        $operations = new Operations($imageMock);
        $operations->crop(['width' => 100, 'height' => 200]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width or height');
        $operations->crop([]);
    }

    /**
     * @return void
     */
    public function testOrient(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('orient');

        $operations = new Operations($imageMock);
        $operations->orient();
    }

    /**
     * @return void
     */
    public function testBrightness(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('brightness')->with(20);

        $operations = new Operations($imageMock);
        $operations->brightness(['level' => 20]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing level');
        $operations->brightness([]);
    }

    /**
     * @return void
     */
    public function testContrast(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('contrast')->with(-15);

        $operations = new Operations($imageMock);
        $operations->contrast(['level' => -15]);
    }

    /**
     * @return void
     */
    public function testGrayscale(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->exactly(2))->method('grayscale');

        $operations = new Operations($imageMock);
        $operations->grayscale();
        $operations->greyscale();
    }

    /**
     * @return void
     */
    public function testColorize(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('colorize')->with(10, -5, 20);

        $operations = new Operations($imageMock);
        $operations->colorize(['red' => 10, 'green' => -5, 'blue' => 20]);
    }

    /**
     * @return void
     */
    public function testColorizeDefaultsToZero(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('colorize')->with(0, 0, 0);

        $operations = new Operations($imageMock);
        $operations->colorize([]);
    }

    /**
     * @return void
     */
    public function testBlur(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('blur')->with(7);

        $operations = new Operations($imageMock);
        $operations->blur(['level' => 7]);
    }

    /**
     * @return void
     */
    public function testBlurDefaultLevel(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('blur')->with(5);

        $operations = new Operations($imageMock);
        $operations->blur([]);
    }

    /**
     * @return void
     */
    public function testPixelate(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('pixelate')->with(8);

        $operations = new Operations($imageMock);
        $operations->pixelate(['size' => 8]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing size');
        $operations->pixelate([]);
    }

    /**
     * @return void
     */
    public function testTrim(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())->method('trim')->with(15);

        $operations = new Operations($imageMock);
        $operations->trim(['tolerance' => 15]);
    }

    /**
     * @return void
     */
    public function testResizeCanvas(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())
            ->method('resizeCanvas')
            ->with(200, 200, '#ffffff', 'center');

        $operations = new Operations($imageMock);
        $operations->resizeCanvas([
            'width' => 200,
            'height' => 200,
            'background' => '#ffffff',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width or height');
        $operations->resizeCanvas([]);
    }

    /**
     * @return void
     */
    public function testPadding(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())
            ->method('resizeCanvasRelative')
            ->with(20, 20, null, 'center');

        $operations = new Operations($imageMock);
        $operations->padding(['amount' => 10]);
    }

    /**
     * @return void
     */
    public function testPaddingExplicitDimensions(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())
            ->method('resizeCanvasRelative')
            ->with(40, 10, '#000', 'center');

        $operations = new Operations($imageMock);
        $operations->padding([
            'width' => 40,
            'height' => 10,
            'background' => '#000',
        ]);
    }

    /**
     * @return void
     */
    public function testPlace(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects($this->once())
            ->method('insert')
            ->with('/tmp/wm.png', 5, 10, 'bottom-right', 0.5);

        $operations = new Operations($imageMock);
        $operations->place([
            'image' => '/tmp/wm.png',
            'position' => 'bottom-right',
            'x' => 5,
            'y' => 10,
            'opacity' => 0.5,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing image');
        $operations->place([]);
    }

    /**
     * @return void
     */
    public function testConvert(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $operations = new Operations($imageMock);

        $this->assertNull($operations->getOutputFormat());

        $operations->convert(['format' => 'WEBP']);
        $this->assertSame('webp', $operations->getOutputFormat());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing format');
        $operations->convert([]);
    }
}
