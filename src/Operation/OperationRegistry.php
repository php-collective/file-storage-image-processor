<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Operation;

use Closure;
use InvalidArgumentException;
use PhpCollective\Infrastructure\Storage\Processor\Image\Exception\UnsupportedOperationException;
use PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection;
use PhpCollective\Infrastructure\Storage\Processor\Image\Position;

/**
 * Maps operation names (as they appear in variant array data) to
 * concrete `Operation` instances. The registry is the single source
 * of truth for which operations exist — adding a new operation means
 * registering it here.
 *
 * The default registry has every built-in operation registered;
 * applications that need extra operations can call `register()` on a
 * shared instance.
 */
class OperationRegistry
{
    /**
     * Factory closures keyed by operation name. Each closure takes the
     * variant config array and returns an `Operation` instance.
     *
     * @var array<string, \Closure(array<string, mixed>): \PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Operation>
     */
    protected array $factories = [];

    /**
     * Returns a registry with every built-in operation registered.
     *
     * @return self
     */
    public static function default(): self
    {
        $registry = new self();
        $registry
            ->register('cover', static fn (array $a): Operation => new Cover(
                width: (int)$a['width'],
                height: (int)$a['height'],
                position: self::resolvePosition($a['position'] ?? null, Position::Center),
                preventUpscale: (bool)($a['preventUpscale'] ?? false),
            ))
            ->register('crop', static fn (array $a): Operation => new Crop(
                width: (int)$a['width'],
                height: (int)$a['height'],
                x: isset($a['x']) ? (int)$a['x'] : null,
                y: isset($a['y']) ? (int)$a['y'] : null,
                position: self::resolvePosition($a['position'] ?? null, Position::Center),
            ))
            ->register('resize', static fn (array $a): Operation => new Resize(
                width: (int)$a['width'],
                height: (int)$a['height'],
                preventUpscale: (bool)($a['preventUpscale'] ?? false),
            ))
            ->register('scale', static fn (array $a): Operation => new Scale(
                width: (int)$a['width'],
                height: (int)$a['height'],
                preventUpscale: (bool)($a['preventUpscale'] ?? false),
            ))
            ->register('heighten', static fn (array $a): Operation => new Heighten(
                height: (int)$a['height'],
                preventUpscale: (bool)($a['preventUpscale'] ?? false),
            ))
            ->register('widen', static fn (array $a): Operation => new Widen(
                width: (int)$a['width'],
                preventUpscale: (bool)($a['preventUpscale'] ?? false),
            ))
            ->register('rotate', static fn (array $a): Operation => new Rotate((int)$a['angle']))
            ->register('sharpen', static fn (array $a): Operation => new Sharpen((int)$a['amount']))
            ->register('flip', static fn (array $a): Operation => new Flip(
                self::resolveFlipDirection($a['direction']),
            ))
            ->register('flipHorizontal', static fn (array $a): Operation => new FlipHorizontal())
            ->register('flipVertical', static fn (array $a): Operation => new FlipVertical())
            ->register('orient', static fn (array $a): Operation => new Orient())
            ->register('brightness', static fn (array $a): Operation => new Brightness((int)$a['level']))
            ->register('contrast', static fn (array $a): Operation => new Contrast((int)$a['level']))
            ->register('grayscale', static fn (array $a): Operation => new Grayscale())
            ->register('greyscale', static fn (array $a): Operation => new Grayscale())
            ->register('colorize', static fn (array $a): Operation => new Colorize(
                red: (int)($a['red'] ?? 0),
                green: (int)($a['green'] ?? 0),
                blue: (int)($a['blue'] ?? 0),
            ))
            ->register('blur', static fn (array $a): Operation => new Blur(
                level: (int)($a['level'] ?? 5),
            ))
            ->register('pixelate', static fn (array $a): Operation => new Pixelate((int)$a['size']))
            ->register('trim', static fn (array $a): Operation => new Trim(
                tolerance: (int)($a['tolerance'] ?? 0),
            ))
            ->register('resizeCanvas', static fn (array $a): Operation => new ResizeCanvas(
                width: (int)$a['width'],
                height: (int)$a['height'],
                background: $a['background'] ?? null,
                position: self::resolvePosition($a['position'] ?? null, Position::Center),
            ))
            ->register('padding', static fn (array $a): Operation => new Padding(
                amount: isset($a['amount']) ? (int)$a['amount'] : null,
                width: isset($a['width']) ? (int)$a['width'] : null,
                height: isset($a['height']) ? (int)$a['height'] : null,
                background: $a['background'] ?? null,
                position: self::resolvePosition($a['position'] ?? null, Position::Center),
            ))
            ->register('place', static fn (array $a): Operation => new Place(
                image: (string)$a['image'],
                position: self::resolvePosition($a['position'] ?? null, Position::BottomCenter),
                x: (int)($a['x'] ?? 0),
                y: (int)($a['y'] ?? 0),
                opacity: (float)($a['opacity'] ?? 1),
            ))
            ->register('convert', static fn (array $a): Operation => new Convert((string)$a['format']))
            ->register('callback', static function (array $a): Operation {
                if (!isset($a['callback']) || !$a['callback'] instanceof Closure) {
                    throw new InvalidArgumentException('Missing or non-Closure callback argument');
                }

                return new Callback($a['callback']);
            });

        return $registry;
    }

    /**
     * Registers a factory for an operation name. Replaces any prior
     * registration for the same name.
     *
     * @param string $name Operation name
     * @param \Closure(array<string, mixed>): \PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Operation $factory Factory
     *
     * @return $this
     */
    public function register(string $name, Closure $factory)
    {
        $this->factories[$name] = $factory;

        return $this;
    }

    /**
     * Resolves a name + args pair into a concrete Operation. Throws
     * `UnsupportedOperationException` when the name is not registered.
     *
     * @param string $name Operation name
     * @param array<string, mixed> $arguments Variant config array for this operation
     *
     * @throws \PhpCollective\Infrastructure\Storage\Processor\Image\Exception\UnsupportedOperationException
     *
     * @return \PhpCollective\Infrastructure\Storage\Processor\Image\Operation\Operation
     */
    public function resolve(string $name, array $arguments): Operation
    {
        if (!isset($this->factories[$name])) {
            throw UnsupportedOperationException::withName($name);
        }

        return ($this->factories[$name])($arguments);
    }

    /**
     * @param string $name Operation name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->factories[$name]);
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->factories);
    }

    /**
     * @param mixed $value Position name or enum case
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $default Fallback
     *
     * @return \PhpCollective\Infrastructure\Storage\Processor\Image\Position
     */
    protected static function resolvePosition(mixed $value, Position $default): Position
    {
        if ($value instanceof Position) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            return Position::fromName($value);
        }

        return $default;
    }

    /**
     * @param mixed $value Direction enum case or string code
     *
     * @return \PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection
     */
    protected static function resolveFlipDirection(mixed $value): FlipDirection
    {
        if ($value instanceof FlipDirection) {
            return $value;
        }

        return FlipDirection::fromName((string)$value);
    }
}
