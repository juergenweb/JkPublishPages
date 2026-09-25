<?php
    declare(strict_types=1);

    namespace ProcessWire;

    /**
     * JkPublishPagesRules
     *
     * Pure decision logic of the JkPublishPages module - without any dependency on ProcessWire.
     * All methods only work with timestamps and plain values, so they can be tested with PHPUnit without a
     * ProcessWire installation. The current time is always passed as parameter ($now) - never taken from time()
     * inside this class.
     *
     * Rules:
     * - A page is inside the publication period if: start <= now <= end (an empty value means "no limit")
     * - The publication has ended if: end < now
     * - The action after the publication (trash, move, delete) is only executed if the publication has ended,
     *   not if the start date is still in the future
     */
    class JkPublishPagesRules
    {

        // actions after the publication has ended (= option ids of the field jk_action_after)
        public const ACTION_UNPUBLISH = 1;
        public const ACTION_TRASH = 2;
        public const ACTION_MOVE = 3;
        public const ACTION_DELETE = 4;

        // results of decide()
        public const RESULT_NONE = 'none';
        public const RESULT_PUBLISH = 'publish';
        public const RESULT_UNPUBLISH = 'unpublish';
        public const RESULT_TRASH = 'trash';
        public const RESULT_MOVE = 'move';
        public const RESULT_DELETE = 'delete';

        /**
         * Normalize a timestamp: empty values (null, 0, '', negative) mean "no date"
         * @param int|null $timestamp
         * @return int|null
         */
        public static function normalizeTimestamp(int|null $timestamp): int|null
        {
            return ($timestamp !== null && $timestamp > 0) ? $timestamp : null;
        }

        /**
         * Get the publishing code according to the start and end date
         *  0 ... page can be published or unpublished (no dates, or only an end date in the future)
         * -1 ... page must be unpublished
         *  1 ... page must be published
         * @param int|null $start
         * @param int|null $end
         * @param int $now
         * @return int
         */
        public static function getPublishingCode(int|null $start, int|null $end, int $now): int
        {
            $start = self::normalizeTimestamp($start);
            $end = self::normalizeTimestamp($end);

            // no dates → page can be published or unpublished
            if ($start === null && $end === null) return 0;

            // only an end date
            if ($start === null) return ($end >= $now) ? 0 : -1;

            // only a start date
            if ($end === null) return ($start > $now) ? -1 : 1;

            // start and end date
            return ($now >= $start && $now <= $end) ? 1 : -1;
        }

        /**
         * Check if the publication has ended (end date is in the past)
         * @param int|null $end
         * @param int $now
         * @return bool
         */
        public static function hasEnded(int|null $end, int $now): bool
        {
            $end = self::normalizeTimestamp($end);
            return $end !== null && $end < $now;
        }

        /**
         * Normalize the action after the publication has ended - unknown values fall back to "unpublish"
         * @param mixed $action
         * @return int
         */
        public static function normalizeAction(mixed $action): int
        {
            $action = is_numeric($action) ? (int)$action : 0;
            return in_array($action, [self::ACTION_UNPUBLISH, self::ACTION_TRASH, self::ACTION_MOVE, self::ACTION_DELETE], true)
                ? $action
                : self::ACTION_UNPUBLISH;
        }

        /**
         * Decide what the cron job has to do with a page
         * @param bool $isUnpublished current status of the page
         * @param int|null $start start date (timestamp) or null
         * @param int|null $end end date (timestamp) or null
         * @param int $action action after the publication has ended (1-4)
         * @param int $now current time (timestamp)
         * @return string one of the RESULT_* constants
         */
        public static function decide(bool $isUnpublished, int|null $start, int|null $end, int $action, int $now): string
        {
            $start = self::normalizeTimestamp($start);
            $end = self::normalizeTimestamp($end);
            $action = self::normalizeAction($action);
            $code = self::getPublishingCode($start, $end, $now);
            $ended = self::hasEnded($end, $now);

            // the start date must not be after the end date - such settings are ignored
            // (the date validation in the page editor does not allow them)
            if ($start !== null && $end !== null && $start > $end) return self::RESULT_NONE;

            if ($isUnpublished) {
                // publication period has started (start date is required for automatic publishing)
                if ($code === 1) return self::RESULT_PUBLISH;

                // publication has ended → execute the action (trash, move, delete), unpublish = nothing to do
                if ($ended && $action !== self::ACTION_UNPUBLISH) return self::actionToResult($action);

                return self::RESULT_NONE;
            }

            // page is published
            if ($code !== -1) return self::RESULT_NONE;

            // the action after the publication is only executed if the end date has been reached,
            // otherwise (start date in the future) the page will only be unpublished
            return $ended ? self::actionToResult($action) : self::RESULT_UNPUBLISH;
        }

        /**
         * Get the schedule of a page: all status changes and actions the cron job will execute, in chronological order
         * The schedule is calculated with decide() - so it always matches what the cron job really does.
         * Each entry: ['result' => RESULT_*, 'at' => timestamp or null]
         * 'at' = null means: on the next cron run (the page does not match its settings at the moment)
         * @param bool $isUnpublished
         * @param int|null $start
         * @param int|null $end
         * @param int $action
         * @param int $now
         * @return array
         */
        public static function schedule(bool $isUnpublished, int|null $start, int|null $end, int $action, int $now): array
        {
            $start = self::normalizeTimestamp($start);
            $end = self::normalizeTimestamp($end);

            // points in time at which the decision can change: now, the start date and the moment after the end date
            $points = [[$now, null]];
            if ($start !== null && $start > $now) $points[] = [$start, $start];
            if ($end !== null && $end >= $now) $points[] = [$end + 1, $end];
            usort($points, fn($a, $b) => $a[0] <=> $b[0]);

            $events = [];
            foreach ($points as [$time, $displayTime]) {
                $result = self::decide($isUnpublished, $start, $end, $action, $time);
                if ($result === self::RESULT_NONE) continue;

                $events[] = ['result' => $result, 'at' => $displayTime];

                if ($result === self::RESULT_PUBLISH) {
                    $isUnpublished = false;
                } else if ($result === self::RESULT_UNPUBLISH) {
                    $isUnpublished = true;
                } else {
                    // trash, move, delete: the page is not processed anymore afterwards
                    // (after moving, the end date is removed and the action is reset)
                    break;
                }
            }
            return $events;
        }

        /**
         * Convert an action id to a result string
         * @param int $action
         * @return string
         */
        public static function actionToResult(int $action): string
        {
            return match ($action) {
                self::ACTION_TRASH => self::RESULT_TRASH,
                self::ACTION_MOVE => self::RESULT_MOVE,
                self::ACTION_DELETE => self::RESULT_DELETE,
                default => self::RESULT_UNPUBLISH,
            };
        }

    }
