# Processing Images

```php
use PhpCollective\Infrastructure\Storage\FileFactory;
use PhpCollective\Infrastructure\Storage\FileStorage;
use PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilder;
use PhpCollective\Infrastructure\Storage\Processor\Image\Driver;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;
use PhpCollective\Infrastructure\Storage\StorageAdapterFactory;
use PhpCollective\Infrastructure\Storage\StorageService;

/*******************************************************************************
 * Configuring the stores - Your DI container or bootstrapping should do this
 ******************************************************************************/

$storageService = new StorageService(
    new StorageAdapterFactory()
);

$pathBuilder = new PathBuilder();

$fileStorage = new FileStorage(
    $storageService,
    $pathBuilder,
);

// Driver::Auto picks Imagick when the extension is loaded and falls
// back to GD; use Driver::Gd or Driver::Imagick to choose explicitly.
$imageProcessor = ImageProcessor::create(Driver::Auto, $fileStorage, $pathBuilder);

/*******************************************************************************
 * Save the original first
 ******************************************************************************/

$file = FileFactory::fromDisk('./tests/Fixtures/titus.jpg', 'local')
    ->withUuid('914e1512-9153-4253-a81e-7ee2edc1d973')
    ->addToCollection('avatar')
    ->belongsToModel('User', '1');

$file = $fileStorage->store($file);

/*******************************************************************************
 * Creating manipulated versions of the file
 ******************************************************************************/

$collection = ImageVariantCollection::create();

// Resize with aspect ratio preservation (recommended for most cases)
$collection->addNew('thumbnail')
    ->scale(300, 300)
    ->optimize();

// Resize to exact dimensions (stretches image)
$collection->addNew('resizeAndFlip')
    ->flipHorizontal()
    ->resize(300, 300)
    ->optimize();

// Crop to exact dimensions
$collection->addNew('crop')
    ->crop(100, 100);

$file = $file->withVariants($collection->toArray());

// Process ALL variants (default)
$file = $imageProcessor->process($file);

// Or: process only specific variants (per-call filter)
// $file = $imageProcessor->process($file, ['thumbnail', 'crop']);
```

## Tuning the encoder

`ImageProcessor` exposes a few setters for output tuning:

```php
$imageProcessor
    ->setQuality(90)                                          // single value, all formats
    ->setQuality(['webp' => 80, 'jpg' => 90, 'avif' => 70])   // per-format map
    ->setStripExif(true)                                      // privacy + smaller files (default)
    ->setPreserveProfile(true);                               // wide-gamut color (default)
```

`setQuality()` accepts either an int (1–100, applied to every quality-aware encoder) or an array keyed by extension. `setStripExif(true)` is the default and only affects encoders that support the `strip` argument (jpg / jpeg / pjpg / webp / avif / heic / tiff / jp2). `setPreserveProfile(true)` (also the default) captures the source ICC profile after decode and re-applies it after operations run, so wide-gamut sources keep rendering correctly even if a callback strips the profile mid-pipeline.

## Selecting a subset of variants

Pass an explicit list as the second argument to `process()` to scope a single call:

```php
// Only the named variants are written
$file = $imageProcessor->process($file, ['thumbnail']);

// Default (no second arg) processes every variant on the file
$file = $imageProcessor->process($file);
```

The filter is **per-call** — there's no leakage between invocations, so it's safe to share an `ImageProcessor` instance across requests / queue workers.
