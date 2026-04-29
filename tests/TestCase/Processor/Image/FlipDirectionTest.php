<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Test\TestCase\Processor\Image;

use Intervention\Image\Direction;
use InvalidArgumentException;
use PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection;
use PhpCollective\Test\TestCase\TestCase;

/**
 * FlipDirectionTest
 */
class FlipDirectionTest extends TestCase
{
    /**
     * @return void
     */
    public function testStringValuesArePreservedFromV1(): void
    {
        $this->assertSame('h', FlipDirection::Horizontal->value);
        $this->assertSame('v', FlipDirection::Vertical->value);
    }

    /**
     * @return void
     */
    public function testFromNameAcceptsCodeAndLongForm(): void
    {
        $this->assertSame(FlipDirection::Horizontal, FlipDirection::fromName('h'));
        $this->assertSame(FlipDirection::Horizontal, FlipDirection::fromName('HORIZONTAL'));
        $this->assertSame(FlipDirection::Vertical, FlipDirection::fromName('  v  '));
        $this->assertSame(FlipDirection::Vertical, FlipDirection::fromName('vertical'));
    }

    /**
     * @return void
     */
    public function testFromNameThrowsOnUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown flip direction "diagonal"');

        FlipDirection::fromName('diagonal');
    }

    /**
     * @return void
     */
    public function testInterventionMapsToUpstreamEnum(): void
    {
        $this->assertSame(Direction::HORIZONTAL, FlipDirection::Horizontal->intervention());
        $this->assertSame(Direction::VERTICAL, FlipDirection::Vertical->intervention());
    }
}
