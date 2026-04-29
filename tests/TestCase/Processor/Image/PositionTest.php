<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Test\TestCase\Processor\Image;

use InvalidArgumentException;
use PhpCollective\Infrastructure\Storage\Processor\Image\Position;
use PhpCollective\Test\TestCase\TestCase;

/**
 * PositionTest
 */
class PositionTest extends TestCase
{
    /**
     * @return void
     */
    public function testStringValuesMatchInterventionAlignmentNames(): void
    {
        $this->assertSame('center', Position::Center->value);
        $this->assertSame('top-left', Position::TopLeft->value);
        $this->assertSame('bottom-right', Position::BottomRight->value);
    }

    /**
     * @return void
     */
    public function testFromNameNormalisesInput(): void
    {
        $this->assertSame(Position::Center, Position::fromName('CENTER'));
        $this->assertSame(Position::TopLeft, Position::fromName('  top-left  '));
        $this->assertSame(Position::BottomRight, Position::fromName('Bottom-Right'));
    }

    /**
     * @return void
     */
    public function testFromNameThrowsOnUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image position "middle"');

        Position::fromName('middle');
    }
}
