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

use Intervention\Image\Direction;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;
use PhpCollective\Infrastructure\Storage\Processor\Image\Exception\UnsupportedOperationException;

/**
 * Operations
 *
 * @link https://image.intervention.io/v4
 */
class Operations
{
    /**
     * @var string
     */
    public const POSITION_CENTER = 'center';

    /**
     * @var string
     */
    public const POSITION_TOP_CENTER = 'top-center';

    /**
     * @var string
     */
    public const POSITION_BOTTOM_CENTER = 'bottom-center';

    /**
     * @var string
     */
    public const POSITION_LEFT_TOP = 'left-top';

    /**
     * @var string
     */
    public const POSITION_RIGHT_TOP = 'right-top';

    /**
     * @var string
     */
    public const POSITION_LEFT_CENTER = 'left-center';

    /**
     * @var string
     */
    public const POSITION_RIGHT_CENTER = 'right-center';

    /**
     * @var string
     */
    public const POSITION_LEFT_BOTTOM = 'left-bottom';

    /**
     * @var string
     */
    public const POSITION_RIGHT_BOTTOM = 'right-bottom';

    /**
     * @var \Intervention\Image\Interfaces\ImageInterface
     */
    protected ImageInterface $image;

    /**
     * Output format requested via the `convert` operation. When set the
     * processor will encode the variant using this extension instead of the
     * source file's extension.
     *
     * @var string|null
     */
    protected ?string $outputFormat = null;

    /**
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image
     */
    public function __construct(ImageInterface $image)
    {
        $this->image = $image;
    }

    /**
     * @return string|null
     */
    public function getOutputFormat(): ?string
    {
        return $this->outputFormat;
    }

    /**
     * @param string $name Name
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \PhpCollective\Infrastructure\Storage\Processor\Image\Exception\UnsupportedOperationException
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        throw UnsupportedOperationException::withName($name);
    }

    /**
     * Crops the image
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function cover(array $arguments): void
    {
        if (!isset($arguments['height'], $arguments['width'])) {
            throw new InvalidArgumentException('Missing width or height');
        }

        $arguments += ['position' => static::POSITION_CENTER];
        $preventUpscale = $arguments['preventUpscale'] ?? false;
        if ($preventUpscale) {
            $this->image->coverDown(
                (int)$arguments['width'],
                (int)$arguments['height'],
                $arguments['position'],
            );

            return;
        }

        $this->image->cover(
            (int)$arguments['width'],
            (int)$arguments['height'],
            $arguments['position'],
        );
    }

    /**
     * Crops the image
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function crop(array $arguments): void
    {
        if (!isset($arguments['height'], $arguments['width'])) {
            throw new InvalidArgumentException('Missing width or height');
        }

        $arguments += ['x' => null, 'y' => null, 'position' => static::POSITION_CENTER];
        $height = (int)$arguments['height'];
        $width = (int)$arguments['width'];

        // If x and y are explicitly provided, use coordinate-based cropping
        if ($arguments['x'] !== null && $arguments['y'] !== null) {
            $x = (int)$arguments['x'];
            $y = (int)$arguments['y'];
            $this->image->crop($width, $height, $x, $y, alignment: $arguments['position']);
        } else {
            // Use position-based cropping only
            $this->image->crop($width, $height, alignment: $arguments['position']);
        }
    }

    /**
     * Flips the image horizontal
     *
     * @return void
     */
    public function flipHorizontal(): void
    {
        $this->image->flip(Direction::HORIZONTAL);
    }

    /**
     * Flips the image vertical
     *
     * @return void
     */
    public function flipVertical(): void
    {
        $this->image->flip(Direction::VERTICAL);
    }

    /**
     * Flips the image
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function flip(array $arguments): void
    {
        if (!isset($arguments['direction'])) {
            throw new InvalidArgumentException('Direction missing');
        }

        if ($arguments['direction'] !== 'v' && $arguments['direction'] !== 'h') {
            throw new InvalidArgumentException(
                'Invalid argument, you must provide h or v',
            );
        }

        if ($arguments['direction'] === 'h') {
            $this->image->flip(Direction::HORIZONTAL);

            return;
        }

        $this->image->flip(Direction::VERTICAL);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function scale(array $arguments): void
    {
        if (!isset($arguments['height'], $arguments['width'])) {
            throw new InvalidArgumentException(
                'Missing height or width',
            );
        }

        $preventUpscale = $arguments['preventUpscale'] ?? false;

        if ($preventUpscale) {
            $this->image->scaleDown(
                $arguments['width'],
                $arguments['height'],
            );

            return;
        }

        $this->image->scale(
            $arguments['width'],
            $arguments['height'],
        );
    }

    /**
     * Resizes the image
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function resize(array $arguments): void
    {
        if (!isset($arguments['height'], $arguments['width'])) {
            throw new InvalidArgumentException(
                'Missing height or width',
            );
        }

        $preventUpscale = $arguments['preventUpscale'] ?? false;

        if ($preventUpscale) {
            $this->image->resizeDown(
                $arguments['width'],
                $arguments['height'],
            );

            return;
        }

        $this->image->resize(
            $arguments['width'],
            $arguments['height'],
        );
    }

    /**
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function rotate(array $arguments): void
    {
        if (!isset($arguments['angle'])) {
            throw new InvalidArgumentException(
                'Missing angle',
            );
        }

        $this->image->rotate((int)$arguments['angle']);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function sharpen(array $arguments): void
    {
        if (!isset($arguments['amount'])) {
            throw new InvalidArgumentException(
                'Missing amount',
            );
        }

        $this->image->sharpen((int)$arguments['amount']);
    }

    /**
     * Auto-rotates the image based on EXIF orientation data and clears the
     * EXIF orientation tag. Useful as the first operation to normalise camera
     * uploads before further processing.
     *
     * @return void
     */
    public function orient(): void
    {
        $this->image->orient();
    }

    /**
     * Adjusts the image brightness. Level ranges from -100 (darker) to
     * 100 (brighter).
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function brightness(array $arguments): void
    {
        if (!isset($arguments['level'])) {
            throw new InvalidArgumentException('Missing level');
        }

        $this->image->brightness((int)$arguments['level']);
    }

    /**
     * Adjusts the image contrast. Level ranges from -100 (less) to
     * 100 (more).
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function contrast(array $arguments): void
    {
        if (!isset($arguments['level'])) {
            throw new InvalidArgumentException('Missing level');
        }

        $this->image->contrast((int)$arguments['level']);
    }

    /**
     * Converts the image to grayscale.
     *
     * @return void
     */
    public function grayscale(): void
    {
        $this->image->grayscale();
    }

    /**
     * Alias of grayscale() for callers using British spelling.
     *
     * @return void
     */
    public function greyscale(): void
    {
        $this->grayscale();
    }

    /**
     * Tints the image by shifting the red, green and blue channels. Each
     * value ranges from -100 to 100.
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @return void
     */
    public function colorize(array $arguments): void
    {
        $red = (int)($arguments['red'] ?? 0);
        $green = (int)($arguments['green'] ?? 0);
        $blue = (int)($arguments['blue'] ?? 0);

        $this->image->colorize($red, $green, $blue);
    }

    /**
     * Blurs the image. Level ranges from 0 to 100; defaults to 5.
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @return void
     */
    public function blur(array $arguments): void
    {
        $level = (int)($arguments['level'] ?? 5);
        $this->image->blur($level);
    }

    /**
     * Pixelates the image with the given block size in pixels.
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function pixelate(array $arguments): void
    {
        if (!isset($arguments['size'])) {
            throw new InvalidArgumentException('Missing size');
        }

        $this->image->pixelate((int)$arguments['size']);
    }

    /**
     * Trims border areas of similar color around the image. Tolerance defaults
     * to 0 (only exactly matching colors are trimmed).
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @return void
     */
    public function trim(array $arguments): void
    {
        $tolerance = (int)($arguments['tolerance'] ?? 0);
        $this->image->trim($tolerance);
    }

    /**
     * Resizes the canvas without resampling the original image. Use this to
     * extend or shrink the working area; new areas are filled with the given
     * background color.
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function resizeCanvas(array $arguments): void
    {
        if (!isset($arguments['width'], $arguments['height'])) {
            throw new InvalidArgumentException('Missing width or height');
        }

        $arguments += [
            'background' => null,
            'position' => static::POSITION_CENTER,
        ];

        $this->image->resizeCanvas(
            (int)$arguments['width'],
            (int)$arguments['height'],
            $arguments['background'],
            $arguments['position'],
        );
    }

    /**
     * Adds padding around the image by extending the canvas relative to the
     * current dimensions. `amount` adds N pixels on each side, or pass `width`
     * and `height` for asymmetric padding.
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @return void
     */
    public function padding(array $arguments): void
    {
        $amount = isset($arguments['amount']) ? (int)$arguments['amount'] : null;
        $width = isset($arguments['width']) ? (int)$arguments['width'] : ($amount !== null ? $amount * 2 : 0);
        $height = isset($arguments['height']) ? (int)$arguments['height'] : ($amount !== null ? $amount * 2 : 0);

        $this->image->resizeCanvasRelative(
            $width,
            $height,
            $arguments['background'] ?? null,
            $arguments['position'] ?? static::POSITION_CENTER,
        );
    }

    /**
     * Inserts another image (typically a watermark) on top of the current one.
     * `image` accepts a file path, image data string, or any value
     * intervention/image's insert() accepts.
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function place(array $arguments): void
    {
        if (!isset($arguments['image'])) {
            throw new InvalidArgumentException('Missing image');
        }

        $position = $arguments['position'] ?? static::POSITION_BOTTOM_CENTER;
        $x = (int)($arguments['x'] ?? 0);
        $y = (int)($arguments['y'] ?? 0);
        $opacity = (float)($arguments['opacity'] ?? 1);

        $this->image->insert($arguments['image'], $x, $y, $position, $opacity);
    }

    /**
     * Records a target output format for this variant. The processor will
     * encode the variant using this extension instead of the source file's
     * extension and adjust the variant path accordingly.
     *
     * Example: `'convert' => ['format' => 'webp']` stores a JPEG source as
     * a WebP variant.
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function convert(array $arguments): void
    {
        if (!isset($arguments['format'])) {
            throw new InvalidArgumentException('Missing format');
        }

        $this->outputFormat = strtolower((string)$arguments['format']);
    }

    /**
     * Allows the declaration of a callable that gets the image manager instance
     * and the arguments passed to it.
     *
     * @param array<string, mixed> $arguments Arguments
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function callback(array $arguments): void
    {
        if (!isset($arguments['callback'])) {
            throw new InvalidArgumentException(
                'Missing callback argument',
            );
        }

        if (!is_callable($arguments['callback'])) {
            throw new InvalidArgumentException(
                'Provided value for callback is not a callable',
            );
        }

        $arguments['callback']($this->image, $arguments);
    }
}
