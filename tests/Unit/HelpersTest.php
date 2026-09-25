<?php
    declare(strict_types=1);

    namespace JkPublishPages\Tests\Unit;

    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;
    use ProcessWire\JkPublishPagesRules as Rules;

    /**
     * Tests for the helper methods of JkPublishPagesRules
     */
    final class HelpersTest extends TestCase
    {
        private const NOW = 1_700_000_000;

        public static function provideActions(): array
        {
            return [
                'int 1' => [1, Rules::ACTION_UNPUBLISH],
                'int 2' => [2, Rules::ACTION_TRASH],
                'string 3' => ['3', Rules::ACTION_MOVE],
                'int 4' => [4, Rules::ACTION_DELETE],
                'null' => [null, Rules::ACTION_UNPUBLISH],
                'empty string' => ['', Rules::ACTION_UNPUBLISH],
                'zero' => [0, Rules::ACTION_UNPUBLISH],
                'out of range' => [5, Rules::ACTION_UNPUBLISH],
                'text' => ['delete', Rules::ACTION_UNPUBLISH],
            ];
        }

        #[DataProvider('provideActions')]
        public function testNormalizeAction(mixed $value, int $expected): void
        {
            $this->assertSame($expected, Rules::normalizeAction($value));
        }

        public function testHasEnded(): void
        {
            $this->assertFalse(Rules::hasEnded(null, self::NOW));
            $this->assertFalse(Rules::hasEnded(0, self::NOW));
            $this->assertFalse(Rules::hasEnded(self::NOW + 1, self::NOW));
            $this->assertFalse(Rules::hasEnded(self::NOW, self::NOW), 'end date exactly now is still inside the period');
            $this->assertTrue(Rules::hasEnded(self::NOW - 1, self::NOW));
        }

        public function testNormalizeTimestamp(): void
        {
            $this->assertNull(Rules::normalizeTimestamp(null));
            $this->assertNull(Rules::normalizeTimestamp(0));
            $this->assertNull(Rules::normalizeTimestamp(-5));
            $this->assertSame(self::NOW, Rules::normalizeTimestamp(self::NOW));
        }

        public function testActionToResult(): void
        {
            $this->assertSame(Rules::RESULT_UNPUBLISH, Rules::actionToResult(Rules::ACTION_UNPUBLISH));
            $this->assertSame(Rules::RESULT_TRASH, Rules::actionToResult(Rules::ACTION_TRASH));
            $this->assertSame(Rules::RESULT_MOVE, Rules::actionToResult(Rules::ACTION_MOVE));
            $this->assertSame(Rules::RESULT_DELETE, Rules::actionToResult(Rules::ACTION_DELETE));
        }
    }
