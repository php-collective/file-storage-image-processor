# Processing Images

```php
use PhpCollective\Infrastructure\Storage\FileFactory;
use PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilder;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariantCollection;
use PhpCollective\Infrastructure\Storage\FileStorage;
use PhpCollective\Infrastructure\Storage\StorageAdapterFactory;
use PhpCollective\Infrastructure\Storage\StorageService;
use Intervention\Image\ImageManager;

/*******************************************************************************
 * Configuring the stores - Your DI container or bootstrapping should do this
 ******************************************************************************/

$storageService = new StorageService(
    new StorageAdapterFactory()
);

$pathBuilder = new PathBuilder();

$fileStorage = new FileStorage(
    $storageService,
    $pathBuilder
);

$imageManager = new ImageManager(
    new \Intervention\Image\Drivers\Gd\Driver()
);

$imageProcessor = new ImageProcessor(
    $fileStorage,
    $pathBuilder,
    $imageManager
);

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

// Repeating the same operation type keeps both steps in order
$collection->addNew('effects')
    ->callback(function ($image, $args) {
        // first pass
    })
    ->callback(function ($image, $args) {
        // second pass
    });

$file = $file->withVariants($collection->toArray());

// Process ALL variants (default behavior - empty array processes everything)
$file = $imageProcessor
    ->processOnlyTheseVariants([])
    ->process($file);

// OR: Process only specific variants (useful for re-generating single variants)
// $file = $imageProcessor
//     ->processOnlyTheseVariants(['thumbnail', 'crop'])
//     ->process($file);

// OR: Skip the filter entirely to process all
// $file = $imageProcessor->process($file);
```

## Variant serialization

`ImageVariantCollection::toArray()` preserves operation order, including repeated operations of the same name. Single operations keep the legacy shape:

```php
'resize' => ['width' => 300, 'height' => 300]
```

If the same operation is added more than once, that entry becomes a list:

```php
'callback' => [
    ['callback' => $first],
    ['callback' => $second],
]
```

`ImageVariantCollection::fromArray()` accepts both shapes and rebuilds the variant chain in the same order, while preserving serialized `path` and `url` values.
