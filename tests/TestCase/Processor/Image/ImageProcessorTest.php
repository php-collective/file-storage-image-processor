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
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
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
        $fileStorage = $this->createStub(FileStorageInterface::class);

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
    public function testProcessorAppliesRepeatedOperationsInOrder(): void
    {
        $applied = [];
        $fileStorage = $this->createStub(FileStorageInterface::class);

        $processor = new ImageProcessor(
            $fileStorage,
            new PathBuilder(),
            new ImageManager(new Driver()),
        );

        $file = FileFactory::fromDisk($this->getFixtureFile('titus.jpg'), 'local')
            ->withUuid('914e1512-9153-4253-a81e-7ee2edc1d973')
            ->withFilename('foobar.jpg')
            ->addToCollection('avatar')
            ->belongsToModel('User', '1');

        $collection = ImageVariantCollection::create();
        $collection
            ->addNew('effects')
            ->callback(function ($image, $args) use (&$applied): void {
                $applied[] = 'first';
            })
            ->callback(function ($image, $args) use (&$applied): void {
                $applied[] = 'second';
            });

        $file = $file->withVariants($collection->toArray());
        $processor->process($file);

        $this->assertSame(['first', 'second'], $applied);
    }

    /**
     * @return void
     */
    public function testProcessorWithImagickDriver(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick driver requires the Imagick PHP extension');
        }

        $fileStorage = $this->createStub(FileStorageInterface::class);

        $processor = new ImageProcessor(
            $fileStorage,
            new PathBuilder(),
            new ImageManager(new ImagickDriver()),
        );

        $file = FileFactory::fromDisk($this->getFixtureFile('titus.jpg'), 'local')
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
}
