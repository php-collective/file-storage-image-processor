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

use PhpCollective\Infrastructure\Storage\Processor\Image\Operations;
use PhpCollective\Test\TestCase\TestCase;
use TestApp\Image;

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
            ->disableOriginalConstructor()
            ->onlyMethods([
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
    public function testScale(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'scale'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('scale')
            ->with(100, 200);

        $operations->scale(['height' => 200, 'width' => 100]);
    }

    /**
     * @return void
     */
    public function testRotate(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
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
            ->disableOriginalConstructor()
            ->onlyMethods([
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
    public function testCover(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'cover'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('cover')
            ->with(100, 100);

        $operations->cover(['width' => 100, 'height' => 100]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width');
        $operations->cover([]);
    }

    /**
     * @return void
     */
    public function testCrop(): void
    {
        $imageMock = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'crop'
            ])
            ->getMock();

        $operations = new Operations($imageMock);

        $imageMock->expects($this->once())
            ->method('crop')
            ->with(100, 200);

        $operations->crop(['width' => 100, 'height' => 200]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width or height');
        $operations->crop([]);
    }
}
