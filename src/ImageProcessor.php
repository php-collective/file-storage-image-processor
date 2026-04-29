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
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use PhpCollective\Infrastructure\Storage\FileInterface;
use PhpCollective\Infrastructure\Storage\FileStorageInterface;
use PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilderInterface;
use PhpCollective\Infrastructure\Storage\Processor\Image\Exception\TempFileCreationFailedException;
use PhpCollective\Infrastructure\Storage\Processor\ProcessorInterface;
use PhpCollective\Infrastructure\Storage\UrlBuilder\UrlBuilderInterface;
use PhpCollective\Infrastructure\Storage\Utility\TemporaryFile;
use Throwable;
use function PhpCollective\Infrastructure\Storage\openFile;

/**
 * Image Operator
 */
class ImageProcessor implements ProcessorInterface
{
    use OptimizerTrait;

    /**
     * Default quality used when no per-format quality has been configured.
     *
     * @var int
     */
    public const DEFAULT_QUALITY = 90;

    /**
     * File extensions whose encoder accepts a `quality` named argument under
     * intervention/image v4. Encoders for png/gif/bmp/ico do not accept it
     * and would throw on an unknown named argument.
     *
     * @var array<int, string>
     */
    protected const QUALITY_AWARE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'pjpg',
        'pjpeg',
        'webp',
        'avif',
        'heic',
        'heif',
        'tif',
        'tiff',
        'jp2',
        'j2k',
    ];

    /**
     * @var array<int, string>
     */
    protected array $mimeTypes = [
        'image/avif',
        'image/bmp',
        'image/gif',
        'image/heic',
        'image/heif',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/tiff',
        'image/webp',
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
     * Default quality used when no per-format override is configured.
     *
     * @var int
     */
    protected int $defaultQuality = self::DEFAULT_QUALITY;

    /**
     * Per-extension quality overrides, keyed by lower-cased file extension.
     *
     * @var array<string, int>
     */
    protected array $qualityMap = [];

    /**
     * Whether to strip EXIF/metadata when encoding output. Defaults to true
     * for privacy and smaller file sizes; disable if downstream consumers
     * rely on metadata such as orientation or copyright tags.
     *
     * Note that the ICC color profile is handled independently — under the
     * Imagick driver, intervention's strip implementation re-applies the
     * profile after stripping metadata, and the explicit toggle below
     * controls preservation across the operations chain.
     *
     * @var bool
     */
    protected bool $stripExif = true;

    /**
     * Whether to preserve the source image's embedded ICC color profile
     * across the operations chain. When enabled the profile is captured
     * after decoding and re-applied to the encoded variant so wide-gamut
     * sources (DisplayP3, AdobeRGB) keep rendering correctly.
     *
     * Defaults to `true`: color correctness is the safer default for a
     * generic image pipeline, and the profile blob (~3 KB) is negligible
     * compared to the visual cost of mis-rendered wide-gamut images.
     * Profiles are only supported by the Imagick driver — under GD this
     * toggle is effectively a no-op because GD has no concept of color
     * profiles, and any setProfile failure is swallowed silently.
     *
     * @var bool
     */
    protected bool $preserveProfile = true;

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
     * Builds an ImageProcessor with the intervention/image manager wired
     * up for you. Pass a `Driver` enum case (preferred — type-safe) or
     * an equivalent string (`'gd'`, `'imagick'`, `'auto'`) for setups
     * that read the driver name from config or env vars:
     *
     *     ImageProcessor::create(Driver::Imagick, $storage, $pathBuilder);
     *     ImageProcessor::create(Driver::Auto, $storage, $pathBuilder);
     *     ImageProcessor::create($config['driver'] ?? 'auto', $storage, $pathBuilder);
     *
     * String input is lower-cased and trimmed before resolution, and an
     * unknown name throws `InvalidArgumentException` with the list of
     * accepted values.
     *
     * Use the regular constructor when a custom-configured
     * `ImageManager` is needed (e.g. with a non-default background
     * color or font config).
     *
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Driver|string $driver Driver enum case or equivalent string
     * @param \PhpCollective\Infrastructure\Storage\FileStorageInterface $storageHandler File Storage Handler
     * @param \PhpCollective\Infrastructure\Storage\PathBuilder\PathBuilderInterface $pathBuilder Path Builder
     * @param \PhpCollective\Infrastructure\Storage\UrlBuilder\UrlBuilderInterface|null $urlBuilder Url Builder
     *
     * @return self
     */
    public static function create(
        Driver|string $driver,
        FileStorageInterface $storageHandler,
        PathBuilderInterface $pathBuilder,
        ?UrlBuilderInterface $urlBuilder = null,
    ): self {
        if (is_string($driver)) {
            $driver = Driver::fromName($driver);
        }

        return new self(
            $storageHandler,
            $pathBuilder,
            new ImageManager($driver->build()),
            $urlBuilder,
        );
    }

    /**
     * Replaces the list of MIME types the processor will operate on.
     * Files with any other MIME type are returned untouched by `process()`.
     * Useful when the application wants to widen support to types the
     * underlying intervention/image driver accepts but that aren't in
     * the default allowlist, or to narrow it for security reasons.
     *
     * @param array<int, string> $mimeTypes MIME Type List, e.g. `['image/jpeg', 'image/png']`
     *
     * @throws \InvalidArgumentException When the list is empty or any entry is not a non-empty string
     *
     * @return $this
     */
    public function setMimeTypes(array $mimeTypes)
    {
        if ($mimeTypes === []) {
            throw new InvalidArgumentException('MIME type list must not be empty');
        }

        foreach ($mimeTypes as $type) {
            if ($type === '') {
                throw new InvalidArgumentException('MIME type list must not contain empty strings');
            }
        }

        $this->mimeTypes = array_values($mimeTypes);

        return $this;
    }

    /**
     * Sets the encoder quality. Pass an int (1-100) to apply a single value
     * to every quality-aware format, or an array keyed by extension (e.g.
     * `['webp' => 80, 'jpg' => 90]`) to use different qualities per format.
     *
     * @param array<string, int>|int $quality Quality
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setQuality(int|array $quality)
    {
        if (is_int($quality)) {
            $this->assertValidQuality($quality);
            $this->defaultQuality = $quality;
            $this->qualityMap = [];

            return $this;
        }

        $map = [];
        foreach ($quality as $extension => $value) {
            if ($extension === '') {
                throw new InvalidArgumentException(
                    'Quality map keys must be non-empty extension strings',
                );
            }
            $intValue = (int)$value;
            $this->assertValidQuality($intValue);
            $map[strtolower($extension)] = $intValue;
        }

        $this->qualityMap = $map;

        return $this;
    }

    /**
     * Toggles whether EXIF/metadata is stripped from encoded output. Only
     * affects encoders that support it (jpg/jpeg/webp/avif/heic/tiff/jp2).
     *
     * @param bool $strip Whether to strip metadata
     *
     * @return $this
     */
    public function setStripExif(bool $strip)
    {
        $this->stripExif = $strip;

        return $this;
    }

    /**
     * Toggles whether the source image's embedded ICC color profile is
     * preserved through processing. When enabled the profile is captured
     * right after decoding and re-applied to the variant after operations
     * have run, so wide-gamut sources (DisplayP3, AdobeRGB) keep their
     * intended color rendering.
     *
     * Profiles are only supported by the Imagick driver. Under GD the
     * toggle is a no-op because GD has no concept of color profiles.
     *
     * @param bool $preserve Whether to preserve the ICC profile
     *
     * @return $this
     */
    public function setPreserveProfile(bool $preserve)
    {
        $this->preserveProfile = $preserve;

        return $this;
    }

    /**
     * @param int $quality Quality
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function assertValidQuality(int $quality): void
    {
        if ($quality > 100 || $quality <= 0) {
            throw new InvalidArgumentException(sprintf(
                'Quality has to be a positive integer between 1 and 100. %s was provided',
                (string)$quality,
            ));
        }
    }

    /**
     * @param string $extension File extension (lower-case)
     *
     * @return int
     */
    protected function qualityFor(string $extension): int
    {
        return $this->qualityMap[$extension] ?? $this->defaultQuality;
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
     * Copies the source file's bytes into the given temp-file stream,
     * preferring the in-memory resource on the file object when present
     * and falling back to a fresh read from storage. Closes the temp
     * stream regardless of outcome and throws when the copy fails so
     * callers don't have to remember to inspect the return value.
     *
     * @param \PhpCollective\Infrastructure\Storage\FileInterface $file File
     * @param resource $tempFileStream Temp File Stream Resource
     * @param string $tempFile Path to the destination temp file (used in the error message)
     *
     * @throws \PhpCollective\Infrastructure\Storage\Processor\Image\Exception\TempFileCreationFailedException
     *
     * @return void
     */
    protected function copyOriginalFileData(FileInterface $file, $tempFileStream, string $tempFile): void
    {
        $stream = $file->resource();
        if ($stream === null) {
            $storage = $this->storageHandler->getStorage($file->storage());
            $stream = $storage->readStream($file->path());
        } else {
            rewind($stream);
        }

        try {
            $result = stream_copy_to_stream($stream, $tempFileStream);
        } finally {
            fclose($tempFileStream);
        }

        if ($result === false) {
            $this->safeUnlink($tempFile);

            throw TempFileCreationFailedException::withFilename($tempFile);
        }
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
     */
    public function process(FileInterface $file): FileInterface
    {
        if (!$this->isApplicable($file)) {
            return $file;
        }

        $storage = $this->storageHandler->getStorage($file->storage());

        $tempFile = TemporaryFile::create();
        $tempFileStream = openFile($tempFile, 'wb+');

        $this->copyOriginalFileData($file, $tempFileStream, $tempFile);

        try {
            foreach ($file->variants() as $variant => $data) {
                if (!$this->shouldProcessVariant($variant, $data)) {
                    continue;
                }

                $file = $this->processVariant($file, $variant, $data, $tempFile, $storage);
            }
        } finally {
            $this->safeUnlink($tempFile);
        }

        return $file;
    }

    /**
     * Decodes the source temp file, runs the variant's operations, encodes
     * the result and writes it to storage. Returns the file with the
     * variant's path and URL filled in.
     *
     * @param \PhpCollective\Infrastructure\Storage\FileInterface $file File
     * @param string $variant Variant name
     * @param array<string, mixed> $data Variant config
     * @param string $tempFile Source temp file path
     * @param \League\Flysystem\FilesystemAdapter $storage Storage adapter
     *
     * @return \PhpCollective\Infrastructure\Storage\FileInterface
     */
    protected function processVariant(
        FileInterface $file,
        string $variant,
        array $data,
        string $tempFile,
        FilesystemAdapter $storage,
    ): FileInterface {
        $image = $this->imageManager->decodePath($tempFile);

        $sourceProfile = $this->preserveProfile ? $this->captureProfile($image) : null;

        $operations = new Operations($image);
        foreach ($data['operations'] as $operation => $arguments) {
            $operations->{$operation}($arguments);
        }

        if ($sourceProfile !== null) {
            $this->restoreProfile($image, $sourceProfile);
        }

        $outputFormat = $operations->getOutputFormat();
        $extension = $outputFormat ?? $file->extension();
        $path = $this->pathForVariant($file, $variant, $outputFormat);

        if (isset($data['optimize']) && $data['optimize'] === true) {
            $this->optimizeAndStore($image, $file, $path, $extension);
        } else {
            $this->encodeAndStore($image, $extension, $path, $storage);
        }

        $data['path'] = $path;
        $file = $file->withVariant($variant, $data);

        if ($this->urlBuilder !== null) {
            $data['url'] = $this->urlBuilder->urlForVariant($file, $variant);
            $file = $file->withVariant($variant, $data);
        }

        return $file;
    }

    /**
     * Captures the source image's ICC profile so we can re-apply it after
     * operations run. profile() throws when the source has no profile —
     * for preservation purposes we treat that as "nothing to restore".
     *
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image
     *
     * @return mixed
     */
    protected function captureProfile(ImageInterface $image): mixed
    {
        try {
            return $image->profile();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image
     * @param mixed $profile Profile to restore
     *
     * @return void
     */
    protected function restoreProfile(ImageInterface $image, mixed $profile): void
    {
        try {
            $image->setProfile($profile);
        } catch (Throwable) {
            // Driver doesn't support profiles (e.g. GD); silently skip
        }
    }

    /**
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image
     * @param string|null $extension File extension
     * @param string $path Destination path in storage
     * @param \League\Flysystem\FilesystemAdapter $storage Storage adapter
     *
     * @return void
     */
    protected function encodeAndStore(
        ImageInterface $image,
        ?string $extension,
        string $path,
        FilesystemAdapter $storage,
    ): void {
        $encoded = $this->encodeImage($image, $extension);
        $stream = openFile('php://temp', 'rb+');
        try {
            fwrite($stream, (string)$encoded);
            rewind($stream);
            $storage->writeStream($path, $stream, new Config());
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param string $path File path
     *
     * @return void
     */
    protected function safeUnlink(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Returns the variant's storage path. When a `convert` operation requested
     * a different output format the source extension in the built path is
     * swapped for the requested one so the stored file's extension matches
     * its actual encoding.
     *
     * @param \PhpCollective\Infrastructure\Storage\FileInterface $file File
     * @param string $variant Variant name
     * @param string|null $outputFormat Override extension or null
     *
     * @return string
     */
    protected function pathForVariant(FileInterface $file, string $variant, ?string $outputFormat): string
    {
        $path = $this->pathBuilder->pathForVariant($file, $variant);
        if ($outputFormat === null || $outputFormat === '') {
            return $path;
        }

        return preg_replace('/\.[^.\/\\\]+$/', '.' . $outputFormat, $path) ?? $path;
    }

    /**
     * Encodes the given image using the given file extension. `quality`
     * and `strip` are only forwarded to encoders that accept them; for
     * png/gif/bmp passing them would trigger an unknown-named-argument
     * error in intervention/image v4.
     *
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image
     * @param string|null $extension File extension
     *
     * @throws \InvalidArgumentException If extension is empty
     *
     * @return \Intervention\Image\Interfaces\EncodedImageInterface
     */
    protected function encodeImage(ImageInterface $image, ?string $extension): EncodedImageInterface
    {
        if ($extension === null || $extension === '') {
            throw new InvalidArgumentException('Cannot encode image without a file extension');
        }

        $extension = strtolower($extension);
        if (in_array($extension, self::QUALITY_AWARE_EXTENSIONS, true)) {
            return $image->encodeUsingFileExtension(
                $extension,
                quality: $this->qualityFor($extension),
                strip: $this->stripExif,
            );
        }

        return $image->encodeUsingFileExtension($extension);
    }

    /**
     * Encodes via the spatie optimizer chain, which needs file paths
     * (not streams) for compatibility with the underlying CLI tools.
     * Both temp files are cleaned up even when storage I/O throws.
     *
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image
     * @param \PhpCollective\Infrastructure\Storage\FileInterface $file File
     * @param string $path Destination path in storage
     * @param string|null $extension Output file extension
     *
     * @return void
     */
    protected function optimizeAndStore(
        ImageInterface $image,
        FileInterface $file,
        string $path,
        ?string $extension = null,
    ): void {
        $storage = $this->storageHandler->getStorage($file->storage());

        $optimizerTempFile = TemporaryFile::create();
        $optimizerOutput = TemporaryFile::create();

        try {
            $encoded = $this->encodeImage($image, $extension ?? $file->extension());
            file_put_contents($optimizerTempFile, (string)$encoded);

            $this->optimizer()->optimize($optimizerTempFile, $optimizerOutput);

            $stream = openFile($optimizerOutput, 'rb+');
            try {
                $storage->writeStream($path, $stream, new Config());
            } finally {
                fclose($stream);
            }
        } finally {
            $this->safeUnlink($optimizerTempFile);
            $this->safeUnlink($optimizerOutput);
        }
    }
}
