<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Operation;

/**
 * A single image transformation step. Each implementer carries its own
 * typed parameters via the constructor and applies them to the image
 * through a shared `OperationContext`. The context is also how
 * out-of-band signals (the requested output format from `Convert`)
 * travel back to the processor.
 */
interface Operation
{
    /**
     * Operation name as used in variant array data (e.g. `'cover'`,
     * `'resize'`). Stable identifier — the processor and the
     * `OperationRegistry` look operations up by this string.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Applies the operation to the image carried by the context.
     *
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Operation\OperationContext $context Context
     *
     * @return void
     */
    public function apply(OperationContext $context): void;

    /**
     * Serialises the operation back to the array form used in
     * `ImageVariant::toArray()`. The result is the inverse of the
     * factory closure registered for this operation in
     * `OperationRegistry`, so a round-trip
     * `fromArray($op->toArray())` returns an equivalent operation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
