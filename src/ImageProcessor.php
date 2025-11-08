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

namespace PhpCollective\Infrastructure\Storage\Processor\Image;

use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;
use League\Flysystem\Config;
use PhpCollective\Infrastructure\Storage\FileInterface;
use PhpCollective\Infrastructure\Storage\FileStorageInterface;
use PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilderInterface;
use PhpCollective\Infrastructure\Storage\Processor\Image\Exception\TempFileCreationFailedException;
use PhpCollective\Infrastructure\Storage\Processor\ProcessorInterface;
use PhpCollective\Infrastructure\Storage\UrlBuilder\UrlBuilderInterface;
use PhpCollective\Infrastructure\Storage\Utility\TemporaryFile;
use function PhpCollective\Infrastructure\Storage\openFile;

/**
 * Image Operator
 */
class ImageProcessor implements ProcessorInterface
{
    use OptimizerTrait;

    /**
     * @var array<int, string>
     */
    protected array $mimeTypes = [
        'image/gif',
        'image/jpg',
        'image/jpeg',
        'image/png',
    ];

    /**
     * @var array<int, string>
     */
    protected array $processOnlyTheseVariants = [];

    /**
     * @var \PhpCollective\Infrastructure\Storage\FileStorageInterface
     */
    protected FileStorageInterface $storageHandler;

    /**
     * @var \PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilderInterface
     */
    protected PathBuilderInterface $pathBuilder;

    /**
     * @var \PhpCollective\Infrastructure\Storage\UrlBuilder\UrlBuilderInterface|null
     */
    protected ?UrlBuilderInterface $urlBuilder;

    /**
     * @var \Intervention\Image\ImageManager
     */
    protected ImageManager $imageManager;

    /**
     * @var \Intervention\Image\Interfaces\ImageInterface
     */
    protected ImageInterface $image;

    /**
     * Quality setting for writing images
     *
     * @var int
     */
    protected int $quality = 90;

    /**
     * @param \PhpCollective\Infrastructure\Storage\FileStorageInterface $storageHandler File Storage Handler
     * @param \PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilderInterface $pathBuilder Path Builder
     * @param \Intervention\Image\ImageManager $imageManager Image Manager
     * @param \PhpCollective\Infrastructure\Storage\UrlBuilder\UrlBuilderInterface|null $urlBuilder
     */
    public function __construct(
        FileStorageInterface $storageHandler,
        PathBuilderInterface $pathBuilder,
        ImageManager $imageManager,
        ?UrlBuilderInterface $urlBuilder = null,
    ) {
        $this->storageHandler = $storageHandler;
        $this->pathBuilder = $pathBuilder;
        $this->imageManager = $imageManager;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array<int, string> $mimeTypes Mime Type List
     *
     * @return $this
     */
    protected function setMimeTypes(array $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;

        return $this;
    }

    /**
     * @param int $quality Quality
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setQuality(int $quality)
    {
        if ($quality > 100 || $quality <= 0) {
            throw new InvalidArgumentException(sprintf(
                'Quality has to be a positive integer between 1 and 100. %s was provided',
                (string)$quality,
            ));
        }

        $this->quality = $quality;

        return $this;
    }

    /**
     * @param \PhpCollective\Infrastructure\Storage\FileInterface $file File
     *
     * @return bool
     */
    protected function isApplicable(FileInterface $file): bool
    {
        return $file->hasVariants()
            && in_array($file->mimeType(), $this->mimeTypes, true);
    }

    /**
     * @param array<int, string> $variants Variants by name
     *
     * @return $this
     */
    public function processOnlyTheseVariants(array $variants)
    {
        $this->processOnlyTheseVariants = $variants;

        return $this;
    }

    /**
     * @return $this
     */
    public function processAll()
    {
        $this->processOnlyTheseVariants = [];

        return $this;
    }

    /**
     * Read the data from the files resource if (still) present,
     * if not fetch it from the storage backend and write the data
     * to the stream of the temp file
     *
     * @param \PhpCollective\Infrastructure\Storage\FileInterface $file File
     * @param resource $tempFileStream Temp File Stream Resource
     *
     * @return int|bool False on error
     */
    protected function copyOriginalFileData(FileInterface $file, $tempFileStream)
    {
        $stream = $file->resource();
        $storage = $this->storageHandler->getStorage($file->storage());

        if ($stream === null) {
            $stream = $storage->readStream($file->path());
        } else {
            rewind($stream);
        }
        $result = stream_copy_to_stream(
            $stream,
            $tempFileStream,
        );
        fclose($tempFileStream);

        return $result;
    }

    /**
     * @param string $variant Variant name
     * @param array<string, mixed> $variantData Variant data
     *
     * @return bool
     */
    protected function shouldProcessVariant(string $variant, array $variantData): bool
    {
        return !(
            // Empty operations
            empty($variantData['operations'])
            || (
                // Check if the operation should be processed
                !empty($this->processOnlyTheseVariants)
                && !in_array($variant, $this->processOnlyTheseVariants, true)
            )
        );
    }

    /**
     * @inheritDoc
     *
     * @throws \PhpCollective\Infrastructure\Storage\Processor\Image\Exception\TempFileCreationFailedException
     */
    public function process(FileInterface $file): FileInterface
    {
        if (!$this->isApplicable($file)) {
            return $file;
        }

        $storage = $this->storageHandler->getStorage($file->storage());

        // Create a local tmp file on the processing system / machine
        $tempFile = TemporaryFile::create();
        $tempFileStream = openFile($tempFile, 'wb+');

        // Read the data from the files resource if (still) present,
        // if not fetch it from the storage backend and write the data
        // to the stream of the temp file
        $result = $this->copyOriginalFileData($file, $tempFileStream);

        // Stop if the temp file could not be generated
        if ($result === false) {
            throw TempFileCreationFailedException::withFilename($tempFile);
        }

        // Iterate over the variants described as an array
        foreach ($file->variants() as $variant => $data) {
            if (!$this->shouldProcessVariant($variant, $data)) {
                continue;
            }

            $this->image = $this->imageManager->read($tempFile);
            $operations = new Operations($this->image);

            // Apply the operations
            foreach ($data['operations'] as $operation => $arguments) {
                $operations->{$operation}($arguments);
            }

            $path = $this->pathBuilder->pathForVariant($file, $variant);

            if (isset($data['optimize']) && $data['optimize'] === true) {
                $this->optimizeAndStore($file, $path);
            } else {
                $encoded = $this->image->encodeByExtension($file->extension(), $this->quality);
                $storage->writeStream(
                    $path,
                    $encoded->toFilePointer(),
                    new Config(),
                );
            }

            $data['path'] = $path;
            $file = $file->withVariant($variant, $data);

            if ($this->urlBuilder !== null) {
                $data['url'] = $this->urlBuilder->urlForVariant($file, $variant);
            }

            $file = $file->withVariant($variant, $data);
        }

        unlink($tempFile);

        return $file;
    }

    /**
     * @param \PhpCollective\Infrastructure\Storage\FileInterface $file File
     * @param string $path Path
     *
     * @return void
     */
    protected function optimizeAndStore(FileInterface $file, string $path): void
    {
        $storage = $this->storageHandler->getStorage($file->storage());

        // We need temp files because the optimizer requires file paths
        // rather than streams for compatibility with various optimization tools
        $optimizerTempFile = TemporaryFile::create();
        $optimizerOutput = TemporaryFile::create();

        // Encode the image with the proper format and write to temp file
        $encoded = $this->image->encodeByExtension($file->extension(), $this->quality);
        file_put_contents($optimizerTempFile, (string)$encoded);

        // Optimize it and write it to another file
        $this->optimizer()->optimize($optimizerTempFile, $optimizerOutput);

        // Open a new stream for the storage system
        $optimizerOutputHandler = openFile($optimizerOutput, 'rb+');

        // And store it...
        $storage->writeStream(
            $path,
            $optimizerOutputHandler,
            new Config(),
        );

        // Cleanup
        fclose($optimizerOutputHandler);
        unlink($optimizerTempFile);
        unlink($optimizerOutput);

        // Cleanup
        unset(
            $optimizerOutputHandler,
            $optimizerTempFile,
            $optimizerOutput,
        );
    }
}
