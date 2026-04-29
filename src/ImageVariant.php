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

use Closure;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Blur;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Brightness;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Callback;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Colorize;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Contrast;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Convert;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Cover;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Crop;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Flip;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\FlipHorizontal;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\FlipVertical;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Grayscale;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Heighten;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Operation;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Orient;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Padding;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Pixelate;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Place;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Resize;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\ResizeCanvas;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Rotate;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Scale;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Sharpen;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Trim;
use PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Widen;
use PhpCollective\Infrastructure\Storage\Processor\Variant;

/**
 * Image Manipulation
 *
 * Each fluent builder method below is a thin wrapper around an
 * `Operation` class — `cover()` constructs `Cover`, `resize()`
 * constructs `Resize`, etc. The Operation class is the canonical
 * source of truth for an operation's parameters. Use `add()`
 * directly when you want to attach an `Operation` instance built
 * elsewhere (custom registry, factory).
 */
class ImageVariant extends Variant
{
    protected string $name;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $operations = [];

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
     * Attaches an Operation to the variant. Used by every fluent
     * builder method on this class as the underlying primitive, and
     * exposed for callers that want to pass operation instances
     * built elsewhere (e.g. from a custom factory).
     *
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Operation $operation Operation
     *
     * @return $this
     */
    public function add(Operation $operation)
    {
        $this->operations[$operation->name()] = $operation->toArray();

        return $this;
    }

    /**
     * @param int $width Width
     * @param int $height Height
     * @param int|null $x X
     * @param int|null $y Y
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Alignment when x/y are not set
     *
     * @return $this
     */
    public function crop(
        int $width,
        int $height,
        ?int $x = null,
        ?int $y = null,
        Position $position = Position::Center,
    ) {
        $this->add(new Crop($width, $height, $x, $y, $position));

        return $this;
    }

    /**
     * @param int $amount Amount, 0 to 100
     *
     * @return $this
     */
    public function sharpen(int $amount)
    {
        $this->add(new Sharpen($amount));

        return $this;
    }

    /**
     * @param int $angle Angle in degrees
     *
     * @return $this
     */
    public function rotate(int $angle)
    {
        $this->add(new Rotate($angle));

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
        $this->add(new Heighten($height, $preventUpscale));

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
        $this->add(new Widen($width, $preventUpscale));

        return $this;
    }

    /**
     * @param int $width Width
     * @param int $height Height
     * @param bool $preventUpscale Prevents upscaling
     *
     * @return $this
     */
    public function resize(int $width, int $height, bool $preventUpscale = false)
    {
        $this->add(new Resize($width, $height, $preventUpscale));

        return $this;
    }

    /**
     * @param int $width Width
     * @param int $height Height
     * @param bool $preventUpscale Prevents upscaling
     *
     * @return $this
     */
    public function scale(int $width, int $height, bool $preventUpscale = false)
    {
        $this->add(new Scale($width, $height, $preventUpscale));

        return $this;
    }

    /**
     * @return $this
     */
    public function flipHorizontal()
    {
        $this->add(new FlipHorizontal());

        return $this;
    }

    /**
     * @return $this
     */
    public function flipVertical()
    {
        $this->add(new FlipVertical());

        return $this;
    }

    /**
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection|string $direction Direction
     *
     * @return $this
     */
    public function flip(FlipDirection|string $direction)
    {
        if (is_string($direction)) {
            $direction = FlipDirection::fromName($direction);
        }

        $this->add(new Flip($direction));

        return $this;
    }

    /**
     * @param callable $callback Receives the intervention/image instance
     *
     * @return $this
     */
    public function callback(callable $callback)
    {
        $this->add(new Callback(Closure::fromCallable($callback)));

        return $this;
    }

    /**
     * @param int $width Width
     * @param int $height Height
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Position
     * @param bool $preventUpscale Prevent Upscaling
     *
     * @return $this
     */
    public function cover(
        int $width,
        int $height,
        Position $position = Position::Center,
        bool $preventUpscale = false,
    ) {
        $this->add(new Cover($width, $height, $position, $preventUpscale));

        return $this;
    }

    /**
     * @return $this
     */
    public function orient()
    {
        $this->add(new Orient());

        return $this;
    }

    /**
     * @param int $level Brightness level, -100 to 100
     *
     * @return $this
     */
    public function brightness(int $level)
    {
        $this->add(new Brightness($level));

        return $this;
    }

    /**
     * @param int $level Contrast level, -100 to 100
     *
     * @return $this
     */
    public function contrast(int $level)
    {
        $this->add(new Contrast($level));

        return $this;
    }

    /**
     * @return $this
     */
    public function grayscale()
    {
        $this->add(new Grayscale());

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
        $this->add(new Colorize($red, $green, $blue));

        return $this;
    }

    /**
     * @param int $level Blur level, 0 to 100
     *
     * @return $this
     */
    public function blur(int $level = 5)
    {
        $this->add(new Blur($level));

        return $this;
    }

    /**
     * @param int $size Pixel block size
     *
     * @return $this
     */
    public function pixelate(int $size)
    {
        $this->add(new Pixelate($size));

        return $this;
    }

    /**
     * @param int $tolerance Color tolerance for trimming, 0 to 100
     *
     * @return $this
     */
    public function trim(int $tolerance = 0)
    {
        $this->add(new Trim($tolerance));

        return $this;
    }

    /**
     * @param int $width Canvas width
     * @param int $height Canvas height
     * @param string|null $background Background color (hex or named)
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Alignment of the original image
     *
     * @return $this
     */
    public function resizeCanvas(
        int $width,
        int $height,
        ?string $background = null,
        Position $position = Position::Center,
    ) {
        $this->add(new ResizeCanvas($width, $height, $background, $position));

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
        $this->add(new Padding(amount: $amount, background: $background));

        return $this;
    }

    /**
     * Inserts a watermark or overlay on top of the image.
     *
     * @param string $image Path to overlay image
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Alignment, e.g. Position::BottomRight
     * @param int $x Horizontal offset from alignment position
     * @param int $y Vertical offset from alignment position
     * @param float $opacity Opacity, 0.0 to 1.0
     *
     * @return $this
     */
    public function place(
        string $image,
        Position $position = Position::BottomCenter,
        int $x = 0,
        int $y = 0,
        float $opacity = 1.0,
    ) {
        $this->add(new Place($image, $position, $x, $y, $opacity));

        return $this;
    }

    /**
     * Re-encode the variant in a different format than the source.
     * Pass a `Format` enum case (preferred — type-safe) or an
     * equivalent extension string for config-driven setups.
     *
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Format|string $format Target output format
     *
     * @return $this
     */
    public function convert(Format|string $format)
    {
        if (is_string($format)) {
            $format = Format::fromName($format);
        }

        $this->add(new Convert($format));

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
