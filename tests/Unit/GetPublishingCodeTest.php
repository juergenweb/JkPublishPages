<?php
    declare(strict_types=1);

    namespace JkPublishPages\Tests\Unit;

    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;
    use ProcessWire\JkPublishPagesRules as Rules;

    /**
     * Tests for JkPublishPagesRules::getPublishingCode()
     *  0 ... page can be published or unpublished
     * -1 ... page must be unpublished
     *  1 ... page must be published
     */
    final class GetPublishingCodeTest extends TestCase
    {
        private const NOW = 1_700_000_000;

        public static function provideCodes(): array
        {
            $now = self::NOW;
            $hour = 3600;

            return [
                // no dates
                'no dates' => [null, null, 0],
                'zero values count as empty' => [0, 0, 0],

                // only end date
                'only end, future' => [null, $now + $hour, 0],
                'only end, exactly now' => [null, $now, 0],
                'only end, past' => [null, $now - $hour, -1],

                // only start date
                'only start, future' => [$now + $hour, null, -1],
                'only start, exactly now' => [$now, null, 1],
                'only start, past' => [$now - $hour, null, 1],

                // start and end date
                'inside period' => [$now - $hour, $now + $hour, 1],
                'start exactly now' => [$now, $now + $hour, 1],
                'end exactly now' => [$now - $hour, $now, 1],
                'before period' => [$now + $hour, $now + 2 * $hour, -1],
                'after period' => [$now - 2 * $hour, $now - $hour, -1],
                'start after end (invalid)' => [$now + $hour, $now - $hour, -1],
            ];
        }

        #[DataProvider('provideCodes')]
        public function testGetPublishingCode(?int $start, ?int $end, int $expected): void
        {
            $this->assertSame($expected, Rules::getPublishingCode($start, $end, self::NOW));
        }
    }
