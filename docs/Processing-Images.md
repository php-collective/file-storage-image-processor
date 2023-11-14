# Processing Images

```php
use PhpCollective\Infrastructure\Storage\FileFactory;
use PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilder;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageProcessor;
use PhpCollective\Infrastructure\Storage\Processor\Image\ImageManipulationCollection;
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

$imageManager = new ImageManager([
    'driver' => 'gd'
]);

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

$collection = ImageManipulationCollection::create();
$collection->addNew('resizeAndFlip')
    ->flipHorizontal()
    ->resize(300, 300)
    ->optimize();
$collection->addNew('crop')
    ->crop(100, 100);

$file = $file->withManipulations($collection->toArray());

$file = $imageProcessor
    ->processOnlyTheseVersions([
        //'resizeAndFlip'
    ])
    ->process($file);
```
