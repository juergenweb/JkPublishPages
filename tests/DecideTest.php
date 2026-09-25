<?php
    declare(strict_types=1);

    namespace JkPublishPages\Tests;

    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;
    use ProcessWire\JkPublishPagesRules as Rules;

    /**
     * Tests for JkPublishPagesRules::decide() - what the cron job does with a page
     */
    final class DecideTest extends TestCase
    {
        private const NOW = 1_700_000_000;
        private const H = 3600;

        public static function provideUnpublishedPages(): array
        {
            $now = self::NOW;
            $h = self::H;

            return [
                // automatic publishing
                'no dates → nothing' => [null, null, Rules::ACTION_UNPUBLISH, Rules::RESULT_NONE],
                'start reached → publish' => [$now - $h, null, Rules::ACTION_UNPUBLISH, Rules::RESULT_PUBLISH],
                'start exactly now → publish' => [$now, null, Rules::ACTION_UNPUBLISH, Rules::RESULT_PUBLISH],
                'inside period → publish' => [$now - $h, $now + $h, Rules::ACTION_TRASH, Rules::RESULT_PUBLISH],
                'start in future → nothing' => [$now + $h, null, Rules::ACTION_UNPUBLISH, Rules::RESULT_NONE],
                'only end in future → nothing (no start = no automatic publishing)' => [null, $now + $h, Rules::ACTION_TRASH, Rules::RESULT_NONE],

                // publication ended, page was unpublished manually before
                'ended, action unpublish → nothing' => [$now - 2 * $h, $now - $h, Rules::ACTION_UNPUBLISH, Rules::RESULT_NONE],
                'ended, action trash → trash' => [$now - 2 * $h, $now - $h, Rules::ACTION_TRASH, Rules::RESULT_TRASH],
                'ended, action move → move' => [$now - 2 * $h, $now - $h, Rules::ACTION_MOVE, Rules::RESULT_MOVE],
                'ended, action delete → delete' => [$now - 2 * $h, $now - $h, Rules::ACTION_DELETE, Rules::RESULT_DELETE],
                'only end, ended, action delete → delete' => [null, $now - $h, Rules::ACTION_DELETE, Rules::RESULT_DELETE],
                'end exactly now → not ended yet' => [$now - $h, $now, Rules::ACTION_DELETE, Rules::RESULT_PUBLISH],

                // invalid settings
                'start after end → nothing' => [$now - $h, $now - 2 * $h, Rules::ACTION_DELETE, Rules::RESULT_NONE],
            ];
        }

        #[DataProvider('provideUnpublishedPages')]
        public function testUnpublishedPage(?int $start, ?int $end, int $action, string $expected): void
        {
            $this->assertSame($expected, Rules::decide(true, $start, $end, $action, self::NOW));
        }

        public static function providePublishedPages(): array
        {
            $now = self::NOW;
            $h = self::H;

            return [
                'no dates → nothing' => [null, null, Rules::ACTION_DELETE, Rules::RESULT_NONE],
                'inside period → nothing' => [$now - $h, $now + $h, Rules::ACTION_DELETE, Rules::RESULT_NONE],
                'only start in past → nothing' => [$now - $h, null, Rules::ACTION_DELETE, Rules::RESULT_NONE],
                'only end in future → nothing' => [null, $now + $h, Rules::ACTION_DELETE, Rules::RESULT_NONE],
                'end exactly now → nothing' => [$now - $h, $now, Rules::ACTION_DELETE, Rules::RESULT_NONE],

                // publication ended → execute the action
                'ended, action unpublish → unpublish' => [$now - 2 * $h, $now - $h, Rules::ACTION_UNPUBLISH, Rules::RESULT_UNPUBLISH],
                'ended, action trash → trash' => [$now - 2 * $h, $now - $h, Rules::ACTION_TRASH, Rules::RESULT_TRASH],
                'ended, action move → move' => [$now - 2 * $h, $now - $h, Rules::ACTION_MOVE, Rules::RESULT_MOVE],
                'ended, action delete → delete' => [$now - 2 * $h, $now - $h, Rules::ACTION_DELETE, Rules::RESULT_DELETE],
                'only end, ended → action' => [null, $now - $h, Rules::ACTION_TRASH, Rules::RESULT_TRASH],

                // start in the future (page was published manually): only unpublish, never trash/delete
                'start in future, action delete → only unpublish' => [$now + $h, $now + 2 * $h, Rules::ACTION_DELETE, Rules::RESULT_UNPUBLISH],
                'start in future, action trash → only unpublish' => [$now + $h, null, Rules::ACTION_TRASH, Rules::RESULT_UNPUBLISH],
                'start in future, action move → only unpublish' => [$now + $h, $now + 2 * $h, Rules::ACTION_MOVE, Rules::RESULT_UNPUBLISH],

                // invalid settings
                'start after end → nothing' => [$now + $h, $now - $h, Rules::ACTION_DELETE, Rules::RESULT_NONE],
            ];
        }

        #[DataProvider('providePublishedPages')]
        public function testPublishedPage(?int $start, ?int $end, int $action, string $expected): void
        {
            $this->assertSame($expected, Rules::decide(false, $start, $end, $action, self::NOW));
        }

        public function testUnknownActionFallsBackToUnpublish(): void
        {
            $now = self::NOW;
            $this->assertSame(Rules::RESULT_UNPUBLISH, Rules::decide(false, $now - 2 * self::H, $now - self::H, 99, $now));
            $this->assertSame(Rules::RESULT_NONE, Rules::decide(true, $now - 2 * self::H, $now - self::H, 99, $now));
        }

        public function testDecisionDependsOnlyOnGivenTime(): void
        {
            $start = self::NOW;
            $end = self::NOW + self::H;

            $this->assertSame(Rules::RESULT_NONE, Rules::decide(true, $start, $end, Rules::ACTION_TRASH, $start - 1));
            $this->assertSame(Rules::RESULT_PUBLISH, Rules::decide(true, $start, $end, Rules::ACTION_TRASH, $start));
            $this->assertSame(Rules::RESULT_NONE, Rules::decide(false, $start, $end, Rules::ACTION_TRASH, $end));
            $this->assertSame(Rules::RESULT_TRASH, Rules::decide(false, $start, $end, Rules::ACTION_TRASH, $end + 1));
        }
    }
