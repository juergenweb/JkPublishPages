<?php

declare(strict_types=1);

namespace JkPublishPages\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\JkPublishPagesRules as Rules;

/**
 * Tests for JkPublishPagesRules::schedule() - the schedule plan shown in the page editor
 */
final class ScheduleTest extends TestCase
{
    private const NOW = 1_700_000_000;
    private const H = 3600;

    private static function schedule(
        bool $isUnpublished,
        ?int $start,
        ?int $end,
        int $action = Rules::ACTION_UNPUBLISH
    ): array {
        return Rules::schedule($isUnpublished, $start, $end, $action, self::NOW);
    }

    public function testNoDatesNoEvents(): void
    {
        $this->assertSame([], self::schedule(true, null, null));
        $this->assertSame([], self::schedule(false, null, null));
    }

    public function testPublishAtStartAndUnpublishAtEnd(): void
    {
        $start = self::NOW + self::H;
        $end = self::NOW + 2 * self::H;

        $this->assertSame([
            ['result' => Rules::RESULT_PUBLISH, 'at' => $start],
            ['result' => Rules::RESULT_UNPUBLISH, 'at' => $end],
        ], self::schedule(true, $start, $end));
    }

    public function testActionAtEnd(): void
    {
        $end = self::NOW + self::H;

        $this->assertSame(
            [['result' => Rules::RESULT_TRASH, 'at' => $end]],
            self::schedule(false, self::NOW - self::H, $end, Rules::ACTION_TRASH)
        );
        $this->assertSame(
            [['result' => Rules::RESULT_DELETE, 'at' => $end]],
            self::schedule(false, null, $end, Rules::ACTION_DELETE)
        );
        $this->assertSame(
            [['result' => Rules::RESULT_MOVE, 'at' => $end]],
            self::schedule(false, null, $end, Rules::ACTION_MOVE)
        );
    }

    public function testManuallyPublishedPageWithStartInFuture(): void
    {
        // the old schedule text said nothing here, but the cron job unpublishes the page on its next run
        $start = self::NOW + self::H;
        $end = self::NOW + 2 * self::H;

        $this->assertSame([
            ['result' => Rules::RESULT_UNPUBLISH, 'at' => null],
            ['result' => Rules::RESULT_PUBLISH, 'at' => $start],
            ['result' => Rules::RESULT_DELETE, 'at' => $end],
        ], self::schedule(false, $start, $end, Rules::ACTION_DELETE));
    }

    public function testUnpublishedPageInsidePeriodIsPublishedOnNextRun(): void
    {
        $end = self::NOW + self::H;

        $this->assertSame([
            ['result' => Rules::RESULT_PUBLISH, 'at' => null],
            ['result' => Rules::RESULT_UNPUBLISH, 'at' => $end],
        ], self::schedule(true, self::NOW - self::H, $end));
    }

    public function testUnpublishedPageWithOnlyEndDateStaysUnpublished(): void
    {
        // no start date → no automatic publishing, the page is already unpublished
        $this->assertSame([], self::schedule(true, null, self::NOW + self::H));
    }

    public function testExpiredPageGetsActionOnNextRun(): void
    {
        $this->assertSame(
            [['result' => Rules::RESULT_TRASH, 'at' => null]],
            self::schedule(true, null, self::NOW - self::H, Rules::ACTION_TRASH)
        );
    }

    public function testNoEventsAfterTrashMoveOrDelete(): void
    {
        // after the page has been moved to the trash, nothing else happens
        $events = self::schedule(false, self::NOW + self::H, self::NOW - self::H + 1, Rules::ACTION_TRASH);
        $this->assertSame([], $events, 'start after end is ignored');

        $events = self::schedule(true, null, self::NOW - 1, Rules::ACTION_DELETE);
        $this->assertCount(1, $events);
    }

    public function testScheduleMatchesDecideAtEveryPoint(): void
    {
        // the schedule must always match what the cron job would do
        $start = self::NOW + self::H;
        $end = self::NOW + 3 * self::H;
        $events = self::schedule(true, $start, $end, Rules::ACTION_TRASH);

        $this->assertSame(Rules::RESULT_NONE, Rules::decide(true, $start, $end, Rules::ACTION_TRASH, self::NOW));
        $this->assertSame(Rules::RESULT_PUBLISH, Rules::decide(true, $start, $end, Rules::ACTION_TRASH, $start));
        $this->assertSame(Rules::RESULT_TRASH, Rules::decide(false, $start, $end, Rules::ACTION_TRASH, $end + 1));
        $this->assertSame([
            ['result' => Rules::RESULT_PUBLISH, 'at' => $start],
            ['result' => Rules::RESULT_TRASH, 'at' => $end],
        ], $events);
    }
}
