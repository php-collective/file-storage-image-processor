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

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use PhpCollective\Infrastructure\Storage\File;
use PhpCollective\Infrastructure\Storage\FileFactory;
use PhpCollective\Infrastructure\Storage\FileStorageInterface;
use PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilder;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;
use PhpCollective\Test\TestCase\TestCase;

/**
 * ImageProcessorTest
 */
class ImageProcessorTest extends TestCase
{
    /**
     * @return void
     */
    public function testProcessor(): void
    {
        $fileStorage = $this->getMockBuilder(FileStorageInterface::class)
            ->getMock();

        $pathBuilder = new PathBuilder();

        $imageManager = new ImageManager(new Driver());

        $processor = new ImageProcessor(
            $fileStorage,
            $pathBuilder,
            $imageManager,
        );

        $fileOnDisk = $this->getFixtureFile('titus.jpg');

        $file = FileFactory::fromDisk($fileOnDisk, 'local')
            ->withUuid('914e1512-9153-4253-a81e-7ee2edc1d973')
            ->withFilename('foobar.jpg')
            ->addToCollection('avatar')
            ->belongsToModel('User', '1');

        $collection = ImageVariantCollection::create();
        $collection
            ->addNew('resizeAndFlip')
            ->flipHorizontal()
            ->resize(300, 300)
            ->optimize();

        $file = $file->withVariants($collection->toArray());

        $file = $processor->process($file);

        $this->assertInstanceOf(File::class, $file);
    }

    /**
     * @return void
     */
    public function testSetQualityRejectsInvalidInteger(): void
    {
        $processor = $this->buildProcessor();

        $this->expectException(InvalidArgumentException::class);
        $processor->setQuality(0);
    }

    /**
     * @return void
     */
    public function testSetQualityRejectsTooHighInteger(): void
    {
        $processor = $this->buildProcessor();

        $this->expectException(InvalidArgumentException::class);
        $processor->setQuality(101);
    }

    /**
     * @return void
     */
    public function testSetQualityAcceptsArrayMap(): void
    {
        $processor = $this->buildProcessor();

        $this->assertSame($processor, $processor->setQuality([
            'webp' => 80,
            'JPG' => 90,
        ]));
    }

    /**
     * @return void
     */
    public function testSetQualityRejectsInvalidMapValue(): void
    {
        $processor = $this->buildProcessor();

        $this->expectException(InvalidArgumentException::class);
        $processor->setQuality(['webp' => 0]);
    }

    /**
     * @return void
     */
    public function testSetStripExifIsFluent(): void
    {
        $processor = $this->buildProcessor();

        $this->assertSame($processor, $processor->setStripExif(false));
    }

    /**
     * @return void
     */
    public function testConvertOperationSwapsVariantPathExtension(): void
    {
        $processor = $this->buildProcessor();

        $fileOnDisk = $this->getFixtureFile('titus.jpg');

        $file = FileFactory::fromDisk($fileOnDisk, 'local')
            ->withUuid('914e1512-9153-4253-a81e-7ee2edc1d973')
            ->withFilename('foobar.jpg')
            ->addToCollection('avatar')
            ->belongsToModel('User', '1');

        $collection = ImageVariantCollection::create();
        $collection
            ->addNew('asWebp')
            ->resize(100, 100)
            ->convert('webp');

        $file = $file->withVariants($collection->toArray());
        $file = $processor->process($file);

        $variants = $file->variants();
        $this->assertArrayHasKey('asWebp', $variants);
        $this->assertStringEndsWith('.webp', $variants['asWebp']['path']);
    }

    /**
     * @return \PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor
     */
    protected function buildProcessor(): ImageProcessor
    {
        $fileStorage = $this->createMock(FileStorageInterface::class);

        return new ImageProcessor(
            $fileStorage,
            new PathBuilder(),
            new ImageManager(new Driver()),
        );
    }
}
