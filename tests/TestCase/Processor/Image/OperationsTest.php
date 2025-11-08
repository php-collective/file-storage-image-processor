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
            ->with(100, 200, 0, 0, 'center');

        $operations = new Operations($imageMock);
        $operations->crop(['width' => 100, 'height' => 200]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing width or height');
        $operations->crop([]);
    }
}
