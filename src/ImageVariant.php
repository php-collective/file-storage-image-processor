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

use PhpCollective\Infrastructure\Storage\Processor\Variant;

/**
 * Image Manipulation
 */
class ImageVariant extends Variant
{
    protected string $name;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $operations;

    protected string $path = '';

    protected bool $optimize = false;

    protected string $url = '';

    /**
     * @param string $name Name
     *
     * @return self
     */
    public static function create(string $name): self
    {
        $self = new self();
        $self->name = $name;

        return $self;
    }

    /**
     * Try to apply image optimizations if available on the system
     *
     * @return $this
     */
    public function optimize()
    {
        $this->optimize = true;

        return $this;
    }

    /**
     * @param int $width Width
     * @param int $height Height
     * @param int|null $x X
     * @param int|null $y Y
     *
     * @return $this
     */
    public function crop(int $width, int $height, ?int $x = null, ?int $y = null)
    {
        $this->operations['crop'] = [
            'width' => $width,
            'height' => $height,
            'x' => $x,
            'y' => $y,
        ];

        return $this;
    }

    /**
     * @param int $amount Angle
     *
     * @return $this
     */
    public function sharpen(int $amount)
    {
        $this->operations['sharpen'] = [
            'amount' => $amount,
        ];

        return $this;
    }

    /**
     * @param int $angle Angle
     *
     * @return $this
     */
    public function rotate(int $angle)
    {
        $this->operations['rotate'] = [
            'angle' => $angle,
        ];

        return $this;
    }

    /**
     * @param int $height Height
     * @param bool $preventUpscale Prevent Upscaling
     *
     * @return $this
     */
    public function heighten(int $height, bool $preventUpscale = false)
    {
        $this->operations['heighten'] = [
            'height' => $height,
            'preventUpscale' => $preventUpscale,
        ];

        return $this;
    }

    /**
     * @param int $width Width
     * @param bool $preventUpscale Prevent Upscaling
     *
     * @return $this
     */
    public function widen(int $width, bool $preventUpscale = false)
    {
        $this->operations['widen'] = [
            'width' => $width,
            'preventUpscale' => $preventUpscale,
        ];

        return $this;
    }

    /**
     * Resizes the image to exact dimensions (does not preserve aspect ratio)
     *
     * @param int $width Width
     * @param int $height Height
     * @param bool $preventUpscale Prevents upscaling
     *
     * @return $this
     */
    public function resize(int $width, int $height, bool $preventUpscale = false)
    {
        $this->operations['resize'] = [
            'width' => $width,
            'height' => $height,
            'preventUpscale' => $preventUpscale,
        ];

        return $this;
    }

    /**
     * Scales the image while preserving aspect ratio
     *
     * @param int $width Width
     * @param int $height Height
     * @param bool $preventUpscale Prevents upscaling
     *
     * @return $this
     */
    public function scale(int $width, int $height, bool $preventUpscale = false)
    {
        $this->operations['scale'] = [
            'width' => $width,
            'height' => $height,
            'preventUpscale' => $preventUpscale,
        ];

        return $this;
    }

    /**
     * Flips the image horizontal
     *
     * @return $this
     */
    public function flipHorizontal()
    {
        $this->operations['flipHorizontal'] = [];

        return $this;
    }

    /**
     * Flips the image vertical
     *
     * @return $this
     */
    public function flipVertical()
    {
        $this->operations['flipVertical'] = [];

        return $this;
    }

    /**
     * Flips the image. Accepts a `FlipDirection` enum case or one of the
     * string codes `'h'` / `'v'` / `'horizontal'` / `'vertical'`.
     *
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection|string $direction Direction
     *
     * @return $this
     */
    public function flip(FlipDirection|string $direction)
    {
        if (is_string($direction)) {
            $direction = FlipDirection::fromName($direction);
        }

        $this->operations['flip'] = [
            'direction' => $direction->value,
        ];

        return $this;
    }

    /**
     * Allows the declaration of a callable that gets the image manager instance
     * and the arguments passed to it.
     *
     * @param callable $callback callback
     *
     * @return $this
     */
    public function callback(callable $callback)
    {
        $this->operations['callback'] = [
            'callback' => $callback,
        ];

        return $this;
    }

    /**
     * @param int $width Width
     * @param int $height Height
     * @param callable|null $callback Callback
     * @param bool $preventUpscale Prevent Upscaling
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position|string $position Position
     *
     * @return $this
     */
    public function cover(
        int $width,
        int $height,
        ?callable $callback = null,
        bool $preventUpscale = false,
        Position|string $position = Position::Center,
    ) {
        $this->operations['cover'] = [
            'width' => $width,
            'height' => $height,
            'callback' => $callback,
            'preventUpscale' => $preventUpscale,
            'position' => $position instanceof Position ? $position->value : $position,
        ];

        return $this;
    }

    /**
     * Auto-rotates the image based on EXIF orientation. Best placed first
     * to normalise camera uploads before further operations.
     *
     * @return $this
     */
    public function orient()
    {
        $this->operations['orient'] = [];

        return $this;
    }

    /**
     * @param int $level Brightness level, -100 to 100
     *
     * @return $this
     */
    public function brightness(int $level)
    {
        $this->operations['brightness'] = ['level' => $level];

        return $this;
    }

    /**
     * @param int $level Contrast level, -100 to 100
     *
     * @return $this
     */
    public function contrast(int $level)
    {
        $this->operations['contrast'] = ['level' => $level];

        return $this;
    }

    /**
     * @return $this
     */
    public function grayscale()
    {
        $this->operations['grayscale'] = [];

        return $this;
    }

    /**
     * @param int $red Red shift, -100 to 100
     * @param int $green Green shift, -100 to 100
     * @param int $blue Blue shift, -100 to 100
     *
     * @return $this
     */
    public function colorize(int $red = 0, int $green = 0, int $blue = 0)
    {
        $this->operations['colorize'] = [
            'red' => $red,
            'green' => $green,
            'blue' => $blue,
        ];

        return $this;
    }

    /**
     * @param int $level Blur level, 0 to 100
     *
     * @return $this
     */
    public function blur(int $level = 5)
    {
        $this->operations['blur'] = ['level' => $level];

        return $this;
    }

    /**
     * @param int $size Pixel block size
     *
     * @return $this
     */
    public function pixelate(int $size)
    {
        $this->operations['pixelate'] = ['size' => $size];

        return $this;
    }

    /**
     * @param int $tolerance Color tolerance for trimming, 0 to 100
     *
     * @return $this
     */
    public function trim(int $tolerance = 0)
    {
        $this->operations['trim'] = ['tolerance' => $tolerance];

        return $this;
    }

    /**
     * @param int $width Canvas width
     * @param int $height Canvas height
     * @param string|null $background Background color (hex or named)
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position|string $position Alignment of the original image
     *
     * @return $this
     */
    public function resizeCanvas(
        int $width,
        int $height,
        ?string $background = null,
        Position|string $position = Position::Center,
    ) {
        $this->operations['resizeCanvas'] = [
            'width' => $width,
            'height' => $height,
            'background' => $background,
            'position' => $position instanceof Position ? $position->value : $position,
        ];

        return $this;
    }

    /**
     * @param int $amount Padding in pixels (added on each side)
     * @param string|null $background Background color
     *
     * @return $this
     */
    public function padding(int $amount, ?string $background = null)
    {
        $this->operations['padding'] = [
            'amount' => $amount,
            'background' => $background,
        ];

        return $this;
    }

    /**
     * Inserts a watermark or overlay on top of the image.
     *
     * @param string $image Path to overlay image
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position|string $position Alignment, e.g. Position::BottomRight
     * @param int $x Horizontal offset from alignment position
     * @param int $y Vertical offset from alignment position
     * @param float $opacity Opacity, 0.0 to 1.0
     *
     * @return $this
     */
    public function place(
        string $image,
        Position|string $position = Position::BottomCenter,
        int $x = 0,
        int $y = 0,
        float $opacity = 1.0,
    ) {
        $this->operations['place'] = [
            'image' => $image,
            'position' => $position instanceof Position ? $position->value : $position,
            'x' => $x,
            'y' => $y,
            'opacity' => $opacity,
        ];

        return $this;
    }

    /**
     * Re-encode the variant in a different format than the source. Use this
     * to e.g. generate WebP variants from JPEG uploads.
     *
     * @param string $format Target file extension, e.g. 'webp'
     *
     * @return $this
     */
    public function convert(string $format)
    {
        $this->operations['convert'] = ['format' => $format];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'operations' => $this->operations,
            'path' => $this->path,
            'url' => $this->url,
            'optimize' => $this->optimize,
        ];
    }
}
