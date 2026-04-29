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

/**
 * Escape hatch for one-off image manipulation. The callback receives
 * the intervention/image instance and can use any of its methods
 * directly — useful when no first-class operation matches.
 *
 * Note: `Callback` does not survive a `toArray()` round-trip cleanly
 * (closures are not serialisable). Use it for in-process pipelines,
 * not for persisted variant configurations.
 */
final class Callback implements Operation
{
    /**
     * @param \Closure $callback Receives the intervention/image instance and modifies it in place
     */
    public function __construct(public readonly Closure $callback)
    {
    }

    public function name(): string
    {
        return 'callback';
    }

    public function apply(OperationContext $context): void
    {
        ($this->callback)($context->image);
    }

    public function toArray(): array
    {
        return ['callback' => $this->callback];
    }
}
