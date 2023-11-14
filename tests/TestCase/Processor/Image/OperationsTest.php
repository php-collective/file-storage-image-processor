<?php

/**
 * Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * @author    Florian Krämer
 * @link      https://github.com/Phauthentic
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace PhpCollective\Test\TestCase\Processor\Image;

use Intervention\Image\Image;
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
        $imageMock = $this->getMockBuilder(Image::class)
            ->addMethods([
                'resize'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('resize')
            ->with(100, 100);

        $operations->resize(['height' => 100, 'width' => 100]);
    }

    /**
     * @return void
     */
    public function testHeighten(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->addMethods([
                'heighten'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('heighten')
            ->with(100);

        $operations->heighten(['height' => 100]);
    }

    /**
     * @return void
     */
    public function testWiden(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->addMethods([
                'widen'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('widen')
            ->with(100);

        $operations->widen(['width' => 100]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width');
        $operations->widen([]);
    }

    /**
     * @return void
     */
    public function testRotate(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->addMethods([
                'rotate'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('rotate')
            ->with(90);

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
        $imageMock = $this->getMockBuilder(Image::class)
            ->addMethods([
                'sharpen'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('sharpen')
            ->with(90);

        $operations->sharpen(['amount' => 90]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing amount');
        $operations->sharpen([]);
    }

    /**
     * @return void
     */
    public function testFit(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->addMethods([
                'fit'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('fit')
            ->with(100);

        $operations->fit(['width' => 100]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width');
        $operations->fit([]);
    }

    /**
     * @return void
     */
    public function testCrop(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->addMethods([
                'crop'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('crop')
            ->with(100);

        $operations->crop(['width' => 100, 'height' => 200]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width or height');
        $operations->crop([]);
    }
}
