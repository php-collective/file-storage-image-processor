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

use Imagick;
use ImagickPixel;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\DriverInterface;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter;
use PhpCollective\Infrastructure\Storage\File;
use PhpCollective\Infrastructure\Storage\FileFactory;
use PhpCollective\Infrastructure\Storage\FileStorageInterface;
use PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilder;
use PhpCollective\Infrastructure\Storage\Processor\Image\Driver as ImageDriver;
use PhpCollective\Infrastructure\Storage\Processor\Image\Exception\ImageCorruptedException;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;
use PhpCollective\Test\TestCase\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionClass;

/**
 * ImageProcessorTest
 */
#[AllowMockObjectsWithoutExpectations]
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
    public function testSetMimeTypesAcceptsList(): void
    {
        $processor = $this->buildProcessor();
        $this->assertSame($processor, $processor->setMimeTypes(['image/jpeg', 'image/png']));
    }

    /**
     * @return void
     */
    public function testSetMimeTypesRejectsEmptyList(): void
    {
        $processor = $this->buildProcessor();

        $this->expectException(InvalidArgumentException::class);
        $processor->setMimeTypes([]);
    }

    /**
     * @return void
     */
    public function testSetMimeTypesRejectsEmptyEntry(): void
    {
        $processor = $this->buildProcessor();

        $this->expectException(InvalidArgumentException::class);
        $processor->setMimeTypes(['image/jpeg', '']);
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
     * @return void
     */
    public function testConvertOperationAppendsVariantPathExtensionForExtensionlessSource(): void
    {
        $processor = $this->buildProcessor();

        $file = FileFactory::fromDisk($this->getFixtureFile('titus.jpg'), 'local')
            ->withUuid('914e1512-9153-4253-a81e-7ee2edc1d973')
            ->withFilename('foobar')
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
     * @return void
     */
    public function testProcessAppliesRepeatedOperationsInOrder(): void
    {
        $applied = [];
        $storage = $this->createMock(FilesystemAdapter::class);
        $storage->method('writeStream')
            ->willReturnCallback(static function (): void {
            });

        $fileStorage = $this->createMock(FileStorageInterface::class);
        $fileStorage->method('getStorage')->willReturn($storage);

        $processor = new ImageProcessor(
            $fileStorage,
            new PathBuilder(),
            new ImageManager(new Driver()),
        );

        $file = FileFactory::fromDisk($this->getFixtureFile('titus.jpg'), 'local')
            ->withUuid('aabb1100-2200-3300-4400-aabbccddeeff')
            ->withFilename('foobar.jpg')
            ->addToCollection('avatar')
            ->belongsToModel('User', '1');

        $collection = ImageVariantCollection::create();
        $collection
            ->addNew('thumbnail')
            ->callback(function ($image) use (&$applied): void {
                $applied[] = 'first';
            })
            ->callback(function ($image) use (&$applied): void {
                $applied[] = 'second';
            });

        $file = $file->withVariants($collection->toArray());
        $processor->process($file);

        $this->assertSame(['first', 'second'], $applied);
    }

    /**
     * Builds a JPEG fixture with an embedded ICC profile and runs it through
     * the processor with profile preservation enabled. The encoded variant
     * bytes are captured from the storage adapter and re-decoded so the
     * embedded profile can be inspected directly.
     *
     * @return void
     */
    public function testIccProfilePreservedThroughProcessing(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('ICC profile preservation requires the Imagick PHP extension');
        }

        $iccData = $this->loadSystemIccProfile();
        if ($iccData === null) {
            $this->markTestSkipped('No system ICC profile available to build the test fixture');
        }

        $fixturePath = $this->buildFixtureWithProfile($iccData);

        try {
            $captured = '';
            $adapter = $this->createMock(FilesystemAdapter::class);
            $adapter->method('writeStream')
                ->willReturnCallback(function ($path, $stream) use (&$captured): void {
                    $contents = stream_get_contents($stream);
                    if ($contents !== false) {
                        $captured = $contents;
                    }
                });

            $fileStorage = $this->createMock(FileStorageInterface::class);
            $fileStorage->method('getStorage')->willReturn($adapter);

            $processor = new ImageProcessor(
                $fileStorage,
                new PathBuilder(),
                new ImageManager(new ImagickDriver()),
            );
            $processor->setPreserveProfile(true);

            $file = FileFactory::fromDisk($fixturePath, 'local')
                ->withUuid('aabbccdd-1234-5678-9abc-def012345678')
                ->withFilename('iccfixture.jpg')
                ->addToCollection('test')
                ->belongsToModel('User', '1');

            $collection = ImageVariantCollection::create();
            $collection->addNew('thumb')->resize(32, 32);
            $file = $file->withVariants($collection->toArray());

            $processor->process($file);

            $this->assertNotSame('', $captured, 'Encoded variant was not captured');

            $decoded = new Imagick();
            $decoded->readImageBlob($captured);
            $profiles = $decoded->getImageProfiles('icc', true);
            $decoded->clear();

            $this->assertArrayHasKey('icc', $profiles);
            $this->assertSame($iccData, $profiles['icc']);
        } finally {
            @unlink($fixturePath);
        }
    }

    /**
     * The defensive value of the toggle: when an operation in the chain
     * removes the profile (here via a callback that calls removeProfile()
     * directly), `preserveProfile=true` re-applies the source profile after
     * operations run so the variant still carries it. With the toggle off
     * the variant comes out profile-less.
     *
     * @return void
     */
    public function testIccProfileReappliedAfterStrippingOperation(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('ICC profile preservation requires the Imagick PHP extension');
        }

        $iccData = $this->loadSystemIccProfile();
        if ($iccData === null) {
            $this->markTestSkipped('No system ICC profile available to build the test fixture');
        }

        $fixturePath = $this->buildFixtureWithProfile($iccData);

        try {
            $stripCallback = static function ($image): void {
                $image->removeProfile();
            };

            // With preservation OFF, the strip callback wins — no profile.
            $variantBytesOff = $this->captureVariantBytes(
                $fixturePath,
                false,
                $stripCallback,
            );
            $this->assertArrayNotHasKey('icc', $this->profilesOf($variantBytesOff));

            // With preservation ON, the source profile is re-applied after
            // operations run, undoing the explicit strip.
            $variantBytesOn = $this->captureVariantBytes(
                $fixturePath,
                true,
                $stripCallback,
            );
            $reappliedProfiles = $this->profilesOf($variantBytesOn);
            $this->assertArrayHasKey('icc', $reappliedProfiles);
            $this->assertSame($iccData, $reappliedProfiles['icc']);
        } finally {
            @unlink($fixturePath);
        }
    }

    /**
     * @return void
     */
    public function testSetPreserveProfileIsFluent(): void
    {
        $processor = $this->buildProcessor();

        $this->assertSame($processor, $processor->setPreserveProfile(true));
    }

    /**
     * Profile preservation is on by default. Verified indirectly by running
     * the strip-then-process test without the explicit toggle and confirming
     * the source profile still ends up on the encoded variant.
     *
     * @return void
     */
    public function testIccProfilePreservedByDefault(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('ICC profile preservation requires the Imagick PHP extension');
        }

        $iccData = $this->loadSystemIccProfile();
        if ($iccData === null) {
            $this->markTestSkipped('No system ICC profile available to build the test fixture');
        }

        $fixturePath = $this->buildFixtureWithProfile($iccData);

        try {
            $captured = '';
            $adapter = $this->createMock(FilesystemAdapter::class);
            $adapter->method('writeStream')
                ->willReturnCallback(function ($path, $stream) use (&$captured): void {
                    $contents = stream_get_contents($stream);
                    if ($contents !== false) {
                        $captured = $contents;
                    }
                });

            $fileStorage = $this->createMock(FileStorageInterface::class);
            $fileStorage->method('getStorage')->willReturn($adapter);

            // No setPreserveProfile() call — relies on the default
            $processor = new ImageProcessor(
                $fileStorage,
                new PathBuilder(),
                new ImageManager(new ImagickDriver()),
            );

            $file = FileFactory::fromDisk($fixturePath, 'local')
                ->withUuid('aabbccdd-1234-5678-9abc-def012345678')
                ->withFilename('iccfixture.jpg')
                ->addToCollection('test')
                ->belongsToModel('User', '1');

            $collection = ImageVariantCollection::create();
            $collection->addNew('thumb')
                ->resize(32, 32)
                ->callback(static function ($image): void {
                    $image->removeProfile();
                });
            $file = $file->withVariants($collection->toArray());

            $processor->process($file);

            $this->assertNotSame('', $captured);
            $this->assertArrayHasKey('icc', $this->profilesOf($captured));
        } finally {
            @unlink($fixturePath);
        }
    }

    /**
     * Variant filter is now a per-call argument; the default null processes
     * every variant on the file.
     *
     * @return void
     */
    public function testProcessWithoutFilterProcessesAllVariants(): void
    {
        $processor = $this->buildProcessor();

        $file = FileFactory::fromDisk($this->getFixtureFile('titus.jpg'), 'local')
            ->withUuid('aabb1100-2200-3300-4400-aabbccddeeff')
            ->withFilename('foobar.jpg')
            ->addToCollection('avatar')
            ->belongsToModel('User', '1');

        $collection = ImageVariantCollection::create();
        $collection->addNew('thumbnail')->resize(100, 100);
        $collection->addNew('hero')->resize(800, 400);

        $file = $file->withVariants($collection->toArray());
        $file = $processor->process($file);

        $variants = $file->variants();
        $this->assertNotSame('', $variants['thumbnail']['path']);
        $this->assertNotSame('', $variants['hero']['path']);
    }

    /**
     * @return void
     */
    public function testProcessWithFilterSkipsUnselectedVariants(): void
    {
        $processor = $this->buildProcessor();

        $file = FileFactory::fromDisk($this->getFixtureFile('titus.jpg'), 'local')
            ->withUuid('aabb1100-2200-3300-4400-aabbccddeeff')
            ->withFilename('foobar.jpg')
            ->addToCollection('avatar')
            ->belongsToModel('User', '1');

        $collection = ImageVariantCollection::create();
        $collection->addNew('thumbnail')->resize(100, 100);
        $collection->addNew('hero')->resize(800, 400);

        $file = $file->withVariants($collection->toArray());
        $file = $processor->process($file, ['thumbnail']);

        $variants = $file->variants();
        $this->assertNotSame('', $variants['thumbnail']['path']);
        $this->assertSame('', $variants['hero']['path'], 'hero variant should not have been written');
    }

    /**
     * Filter does not persist between calls — each process() runs with
     * exactly the filter passed to that invocation.
     *
     * @return void
     */
    public function testProcessFilterDoesNotLeakAcrossCalls(): void
    {
        $processor = $this->buildProcessor();

        $build = function () {
            $file = FileFactory::fromDisk($this->getFixtureFile('titus.jpg'), 'local')
                ->withUuid('aabb1100-2200-3300-4400-aabbccddeeff')
                ->withFilename('foobar.jpg')
                ->addToCollection('avatar')
                ->belongsToModel('User', '1');

            $collection = ImageVariantCollection::create();
            $collection->addNew('thumbnail')->resize(100, 100);
            $collection->addNew('hero')->resize(800, 400);

            return $file->withVariants($collection->toArray());
        };

        // First call filters to thumbnail
        $first = $processor->process($build(), ['thumbnail']);
        $this->assertNotSame('', $first->variants()['thumbnail']['path']);
        $this->assertSame('', $first->variants()['hero']['path']);

        // Second call has no filter — both variants should be written
        $second = $processor->process($build());
        $this->assertNotSame('', $second->variants()['thumbnail']['path']);
        $this->assertNotSame('', $second->variants()['hero']['path']);
    }

    /**
     * Animated GIF round-trip: a 3-frame source resized through the
     * processor should still be animated with all 3 frames intact when
     * the default animation-preservation behavior is in effect.
     *
     * @return void
     */
    public function testAnimationPreservedByDefault(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Animated-image roundtrip uses Imagick to decode the variant');
        }

        $bytes = $this->captureAnimatedVariantBytes(preserveAnimation: true);

        $decoded = new Imagick();
        $decoded->readImageBlob($bytes);
        $frames = $decoded->getNumberImages();
        $decoded->clear();

        $this->assertSame(3, $frames, 'Expected the 3-frame source to round-trip with all frames intact');
    }

    /**
     * @return void
     */
    public function testAnimationFlattenedWhenPreservationDisabled(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Animated-image roundtrip uses Imagick to decode the variant');
        }

        $bytes = $this->captureAnimatedVariantBytes(preserveAnimation: false);

        $decoded = new Imagick();
        $decoded->readImageBlob($bytes);
        $frames = $decoded->getNumberImages();
        $decoded->clear();

        $this->assertSame(1, $frames, 'Expected the 3-frame source to flatten to a single frame');
    }

    /**
     * @return void
     */
    public function testSetPreserveAnimationIsFluent(): void
    {
        $processor = $this->buildProcessor();

        $this->assertSame($processor, $processor->setPreserveAnimation(false));
    }

    /**
     * @return void
     */
    public function testCreateWithExplicitGdDriver(): void
    {
        $processor = ImageProcessor::create(
            ImageDriver::Gd,
            $this->createMock(FileStorageInterface::class),
            new PathBuilder(),
        );

        $this->assertInstanceOf(GdDriver::class, $this->driverOf($processor));
    }

    /**
     * @return void
     */
    public function testCreateWithExplicitImagickDriver(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick driver requires the Imagick PHP extension');
        }

        $processor = ImageProcessor::create(
            ImageDriver::Imagick,
            $this->createMock(FileStorageInterface::class),
            new PathBuilder(),
        );

        $this->assertInstanceOf(ImagickDriver::class, $this->driverOf($processor));
    }

    /**
     * @return void
     */
    public function testCreateAutoPrefersImagickWhenAvailable(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('Auto-detect prefers Imagick; requires the extension');
        }

        $processor = ImageProcessor::create(
            ImageDriver::Auto,
            $this->createMock(FileStorageInterface::class),
            new PathBuilder(),
        );

        $this->assertInstanceOf(ImagickDriver::class, $this->driverOf($processor));
    }

    /**
     * @return void
     */
    public function testDriverEnumStringValuesAreStable(): void
    {
        $this->assertSame('gd', ImageDriver::Gd->value);
        $this->assertSame('imagick', ImageDriver::Imagick->value);
        $this->assertSame('auto', ImageDriver::Auto->value);
    }

    /**
     * @return void
     */
    public function testCreateAcceptsDriverNameString(): void
    {
        $processor = ImageProcessor::create(
            'gd',
            $this->createMock(FileStorageInterface::class),
            new PathBuilder(),
        );

        $this->assertInstanceOf(GdDriver::class, $this->driverOf($processor));
    }

    /**
     * @return void
     */
    public function testCreateNormalisesDriverNameString(): void
    {
        $processor = ImageProcessor::create(
            '  GD  ',
            $this->createMock(FileStorageInterface::class),
            new PathBuilder(),
        );

        $this->assertInstanceOf(GdDriver::class, $this->driverOf($processor));
    }

    /**
     * @return void
     */
    public function testCreateThrowsOnUnknownDriverName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image driver "vips"');

        ImageProcessor::create(
            'vips',
            $this->createMock(FileStorageInterface::class),
            new PathBuilder(),
        );
    }

    /**
     * @return void
     */
    public function testDriverFromNameRoundTrip(): void
    {
        $this->assertSame(ImageDriver::Auto, ImageDriver::fromName('auto'));
        $this->assertSame(ImageDriver::Gd, ImageDriver::fromName('GD'));
        $this->assertSame(ImageDriver::Imagick, ImageDriver::fromName('  imagick  '));
    }

    /**
     * @return void
     */
    public function testDriverFromNameThrowsOnUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image driver "vips". Expected one of: auto, gd, imagick.');

        ImageDriver::fromName('vips');
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

    /**
     * Reads the protected `imageManager` property and returns its driver,
     * so tests can assert which driver the factory selected without having
     * to expose it via a public getter.
     *
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor $processor Processor
     *
     * @return \Intervention\Image\Interfaces\DriverInterface
     */
    protected function driverOf(ImageProcessor $processor): DriverInterface
    {
        $reflection = new ReflectionClass($processor);
        $property = $reflection->getProperty('imageManager');
        $manager = $property->getValue($processor);

        return $manager->driver;
    }

    /**
     * @return string|null
     */
    protected function loadSystemIccProfile(): ?string
    {
        $candidates = [
            '/usr/share/color/icc/colord/AdobeRGB1998.icc',
            '/usr/share/color/icc/colord/sRGB.icc',
            '/usr/share/color/icc/sRGB.icc',
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                $contents = file_get_contents($candidate);
                if ($contents !== false && $contents !== '') {
                    return $contents;
                }
            }
        }

        return null;
    }

    /**
     * @param string $iccData ICC profile blob
     *
     * @return string Path to a temporary JPEG fixture with the profile embedded
     */
    protected function buildFixtureWithProfile(string $iccData): string
    {
        $imagick = new Imagick();
        $imagick->newImage(64, 64, new ImagickPixel('red'));
        $imagick->setImageFormat('jpeg');
        $imagick->profileImage('icc', $iccData);

        $path = sys_get_temp_dir() . '/' . uniqid('icc_fixture_', true) . '.jpg';
        $imagick->writeImage($path);
        $imagick->clear();

        return $path;
    }

    /**
     * Runs the processor over the given fixture with profile preservation
     * configured, and returns the encoded variant bytes that were written
     * to the storage adapter.
     *
     * @param string $fixturePath Source fixture path
     * @param bool $preserveProfile Toggle value
     * @param callable|null $extraOperation Optional callback added to the variant
     *
     * @return string
     */
    protected function captureVariantBytes(
        string $fixturePath,
        bool $preserveProfile,
        ?callable $extraOperation = null,
    ): string {
        $captured = '';
        $adapter = $this->createMock(FilesystemAdapter::class);
        $adapter->method('writeStream')
            ->willReturnCallback(function ($path, $stream) use (&$captured): void {
                $contents = stream_get_contents($stream);
                if ($contents !== false) {
                    $captured = $contents;
                }
            });

        $fileStorage = $this->createMock(FileStorageInterface::class);
        $fileStorage->method('getStorage')->willReturn($adapter);

        $processor = new ImageProcessor(
            $fileStorage,
            new PathBuilder(),
            new ImageManager(new ImagickDriver()),
        );
        $processor->setPreserveProfile($preserveProfile);

        $file = FileFactory::fromDisk($fixturePath, 'local')
            ->withUuid('aabbccdd-1234-5678-9abc-def012345678')
            ->withFilename('iccfixture.jpg')
            ->addToCollection('test')
            ->belongsToModel('User', '1');

        $collection = ImageVariantCollection::create();
        $variant = $collection->addNew('thumb')->resize(32, 32);
        if ($extraOperation !== null) {
            $variant->callback($extraOperation);
        }
        $file = $file->withVariants($collection->toArray());

        $processor->process($file);

        $this->assertNotSame('', $captured, 'Encoded variant was not captured');

        return $captured;
    }

    /**
     * Decodes the given JPEG bytes and returns the embedded profiles array.
     *
     * @param string $bytes Encoded image bytes
     *
     * @return array<string, string>
     */
    protected function profilesOf(string $bytes): array
    {
        $decoded = new Imagick();
        $decoded->readImageBlob($bytes);
        $profiles = $decoded->getImageProfiles('icc', true);
        $decoded->clear();

        return $profiles;
    }

    /**
     * Runs the 3-frame animated GIF fixture through the processor with
     * the given animation-preservation toggle and returns the encoded
     * variant bytes.
     *
     * @param bool $preserveAnimation Whether to keep animation
     *
     * @return string
     */
    protected function captureAnimatedVariantBytes(bool $preserveAnimation): string
    {
        $captured = '';
        $adapter = $this->createMock(FilesystemAdapter::class);
        $adapter->method('writeStream')
            ->willReturnCallback(function ($path, $stream) use (&$captured): void {
                $contents = stream_get_contents($stream);
                if ($contents !== false) {
                    $captured = $contents;
                }
            });

        $fileStorage = $this->createMock(FileStorageInterface::class);
        $fileStorage->method('getStorage')->willReturn($adapter);

        $processor = new ImageProcessor(
            $fileStorage,
            new PathBuilder(),
            new ImageManager(new ImagickDriver()),
        );
        $processor->setPreserveAnimation($preserveAnimation);

        $file = FileFactory::fromDisk($this->getFixtureFile('animated.gif'), 'local')
            ->withUuid('aabbccdd-1234-5678-9abc-def012345678')
            ->withFilename('animated.gif')
            ->addToCollection('test')
            ->belongsToModel('User', '1');

        $collection = ImageVariantCollection::create();
        $collection->addNew('thumbnail')->resize(16, 16);
        $file = $file->withVariants($collection->toArray());

        $processor->process($file);

        $this->assertNotSame('', $captured, 'Encoded variant was not captured');

        return $captured;
    }

    /**
     * Feeds a corrupt JPEG into the processor and expects the
     * intervention/image decode failure to be wrapped in our
     * ImageCorruptedException.
     *
     * @return void
     */
    public function testCorruptImageThrowsImageCorruptedException(): void
    {
        $corruptPath = sys_get_temp_dir() . '/' . uniqid('corrupt_', true) . '.jpg';
        file_put_contents($corruptPath, 'this is not a jpeg');

        try {
            $processor = $this->buildProcessor();

            $file = FileFactory::fromDisk($corruptPath, 'local')
                ->withUuid('aabbccdd-1234-5678-9abc-def012345678')
                ->withFilename('corrupt.jpg')
                ->addToCollection('test')
                ->belongsToModel('User', '1');

            $collection = ImageVariantCollection::create();
            $collection->addNew('thumb')->resize(32, 32);
            $file = $file->withVariants($collection->toArray());

            $this->expectException(ImageCorruptedException::class);
            $processor->process($file);
        } finally {
            @unlink($corruptPath);
        }
    }
}
