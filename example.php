<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

use PhpCollective\Infrastructure\Storage\Factories\LocalFactory;
use PhpCollective\Infrastructure\Storage\FileFactory;
use PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilder;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;
use PhpCollective\Infrastructure\Storage\FileStorage;
use PhpCollective\Infrastructure\Storage\StorageAdapterFactory;
use PhpCollective\Infrastructure\Storage\StorageService;
use Intervention\Image\ImageManager;

/*******************************************************************************
 * Just a Utility function for this example and some output
 ******************************************************************************/

function readableSize(int $size, int $precision = 2)
{
    for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {}

    return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
}

function memoryOutput()
{
    echo PHP_EOL;
    echo 'Peak Memory: ' . readableSize(memory_get_peak_usage(true)) . PHP_EOL;
    echo 'Memory: ' . readableSize(memory_get_usage(true)) . PHP_EOL;
    echo PHP_EOL;
}

memoryOutput();

/*******************************************************************************
 * Configuring the stores - Your DI container or bootstrapping should do this
 ******************************************************************************/

$ds = DIRECTORY_SEPARATOR;

$storageService = new StorageService(
    new StorageAdapterFactory()
);

$storageService->setAdapterConfigFromArray([
    'local' => [
        'class' => LocalFactory::class,
        'options' => [
            'root' => '.' . $ds . 'tmp' . $ds . 'storage1' . $ds
        ]
    ],
    'local2' => [
        'class' => LocalFactory::class,
        'options' => [
            'root' => '.' . $ds . 'tmp' . $ds . 'storage2' . $ds
        ]
    ]
]);

/*******************************************************************************
 * Build services - Your DI container should do this for you
 ******************************************************************************/

$pathBuilder = new PathBuilder();

$fileStorage = new FileStorage(
    $storageService,
    $pathBuilder,
);

$imageManager = new ImageManager(
    new \Intervention\Image\Drivers\Gd\Driver()
);

$imageProcessor = new ImageProcessor(
    $fileStorage,
    $pathBuilder,
    $imageManager,
);

/*******************************************************************************
 * Working with files
 *
 * This is a very exhaustive example for demonstrating what can bed done,
 * setting the id would be already enough!
 ******************************************************************************/

$file = FileFactory::fromDisk('./tests/Fixtures/titus.jpg', 'local')
    ->withUuid('914e1512-9153-4253-a81e-7ee2edc1d973')
    ->withFilename('foobar.jpg')
    ->addToCollection('avatar')
    ->belongsToModel('User', '1')
    ->withMetadata([
        'one' => 'two',
        'two' => 'one',
    ])
    ->withMetadataKey('bar', 'foo');

$file = $fileStorage->store($file);

echo var_export($file->toArray(), true);
echo PHP_EOL . PHP_EOL;

/*******************************************************************************
 * Creating manipulated versions of the file
 ******************************************************************************/

$collection = ImageVariantCollection::create();

// Resize with aspect ratio preservation (recommended for most cases)
$collection->addNew('thumbnail')
    ->scale(300, 300)  // Scales to fit within 300x300, maintains aspect ratio
    ->optimize();

// Resize to exact dimensions (stretches image)
$collection->addNew('resizeAndFlip')
    ->flipHorizontal()
    ->resize(300, 300)  // Exact 300x300, may distort
    ->optimize();

// Crop to exact dimensions
$collection->addNew('crop')
    ->crop(100, 100);

$file = $file->withVariants($collection->toArray());

/*******************************************************************************
 * Processing variants
 *
 * You can either:
 * 1. Process all variants at once (default)
 * 2. Process only specific variants (useful for progressive loading or updates)
 ******************************************************************************/

// Option 1: Process ALL variants
$file = $imageProcessor->process($file);

// Option 2: Process only specific variants (uncomment to use)
// $file = $imageProcessor
//     ->processOnlyTheseVariants(['thumbnail', 'crop'])
//     ->process($file);

// Option 3: Process in stages (quick preview, then full processing)
// Step 1: Fast variants first
// $file = $imageProcessor
//     ->processOnlyTheseVariants(['thumbnail'])
//     ->process($file);
//
// displayPreview($file); // Show user something immediately
//
// Step 2: Process remaining variants
// $file = $imageProcessor
//     ->processOnlyTheseVariants(['resizeAndFlip', 'crop'])
//     ->process($file);

echo var_export($file->toArray(), true);
echo PHP_EOL;

/*******************************************************************************
 * Removing the file
 ******************************************************************************/

//$fileStorage->remove($file);

/*******************************************************************************
 * Just some output
 ******************************************************************************/

memoryOutput();
