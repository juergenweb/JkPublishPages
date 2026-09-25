<?php

declare(strict_types=1);

namespace JkPublishPages\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use ProcessWire\Inputfield;
use ProcessWire\NoticeWarning;

/**
 * Integration tests for the date validation in the page editor (validateDates)
 *
 * For every scenario the two date fields are validated like in the page editor (start date first, then end date).
 * Checked are: error on the start date field, error on the end date field, the status the page will be saved with
 * and the warning that the wanted status cannot be saved.
 *
 * The date fields in the editor have minute precision, so all dates are at least 2 hours away from "now".
 * The expected texts are taken from the module itself, so the tests also work with a translated admin.
 */
final class ValidationTest extends IntegrationTestCase
{
    private const H = 3600;

    protected function setUp(): void
    {
        parent::setUp();
        $field = self::$wire->fields->get('jk_publish_from');
        if ($field->inputType !== 'html') {
            $this->markTestSkipped('The date fields do not use the input type "html" in this installation.');
        }
        self::$wire->notices->removeAll();
    }

    /**
     * Scenarios: [wanted status, start (offset to now in hours or null), end (offset or null),
     *             error on start field, error on end field, saved status, warning]
     */
    public static function provideScenarios(): array
    {
        return [
            // no dates: the wanted status is saved
            'publish, no dates' => ['pub', null, null, false, false, 'pub', false],
            'unpublish, no dates' => ['unpub', null, null, false, false, 'unpub', false],

            // only start date
            'publish, start in past' => ['pub', -2, null, false, false, 'pub', false],
            'publish, start in future' => ['pub', 2, null, true, false, 'unpub', true],
            'unpublish, start in past' => ['unpub', -2, null, true, false, 'pub', true],
            'unpublish, start in future' => ['unpub', 2, null, false, false, 'unpub', false],

            // only end date
            'publish, end in future' => ['pub', null, 2, false, false, 'pub', false],
            'publish, end in past' => ['pub', null, -2, false, true, 'unpub', true],
            'unpublish, end in future' => ['unpub', null, 2, false, false, 'unpub', false],
            'unpublish, end in past' => ['unpub', null, -2, false, false, 'unpub', false],

            // start and end date
            'publish, inside period' => ['pub', -2, 2, false, false, 'pub', false],
            'publish, before period' => ['pub', 2, 4, true, false, 'unpub', true],
            'publish, after period' => ['pub', -4, -2, false, true, 'unpub', true],
            'unpublish, inside period' => ['unpub', -2, 2, true, false, 'pub', true],
            'unpublish, before period' => ['unpub', 2, 4, false, false, 'unpub', false],
            'unpublish, after period' => ['unpub', -4, -2, false, false, 'unpub', false],

            // start after end: always an error on the start field
            'publish, start in future after end in future' => ['pub', 4, 2, true, false, 'unpub', true],
            'unpublish, start in future after end in future' => ['unpub', 4, 2, true, false, 'unpub', false],
            'publish, start in future, end in past' => ['pub', 2, -2, true, true, 'unpub', true],
            'unpublish, start in past after end in past' => ['unpub', -2, -4, true, false, 'unpub', false],
        ];
    }

    #[DataProvider('provideScenarios')]
    public function testValidation(
        string $wanted,
        ?int $startOffset,
        ?int $endOffset,
        bool $startError,
        bool $endError,
        string $savedStatus,
        bool $warning
    ): void {
        $now = time();
        $start = ($startOffset === null) ? null : $now + $startOffset * self::H;
        $end = ($endOffset === null) ? null : $now + $endOffset * self::H;

        $result = $this->validate($wanted, $start, $end);

        $this->assertSame(
            $startError,
            $result['startErrors'] !== [],
            'error on start date field: ' . implode(' ', $result['startErrors'])
        );
        $this->assertSame(
            $endError,
            $result['endErrors'] !== [],
            'error on end date field: ' . implode(' ', $result['endErrors'])
        );
        $this->assertSame($savedStatus, $result['status'], 'status the page will be saved with');
        $this->assertSame(
            $warning,
            $result['warnings'] !== [],
            'warning about the status change: ' . implode(' ', $result['warnings'])
        );
    }

    public function testStartAfterEndShowsBothDates(): void
    {
        // start and end on different days, so both dates can be recognized in the message
        $start = time() + 3 * 86400;
        $end = time() + 86400;
        $result = $this->validate('unpub', $start, $end);

        $error = html_entity_decode(implode(' ', $result['startErrors']), ENT_QUOTES);
        $this->assertStringContainsString(date('Y-m-d', $start), $error, 'the entered start date must be shown');
        $this->assertStringContainsString(date('Y-m-d', $end), $error, 'the end date must be shown');
    }

    public function testErrorForStartInFutureNamesThePast(): void
    {
        $result = $this->validate('pub', time() + 2 * self::H, null);

        // "... this field must have a value in the PAST or it must remain EMPTY"
        $error = html_entity_decode(implode(' ', $result['startErrors']), ENT_QUOTES);
        $past = mb_strtoupper(html_entity_decode(self::module()->_('past'), ENT_QUOTES));
        $this->assertStringContainsString($past, $error);
    }

    /**
     * Validate the two date fields like the page editor does and return the result
     */
    private function validate(string $wanted, ?int $start, ?int $end): array
    {
        $post = self::$wire->input->post;
        foreach (['jk_publish_from' => $start, 'jk_publish_until' => $end] as $name => $timestamp) {
            $post->set($name, $timestamp === null ? '' : date('Y-m-d', $timestamp));
            $post->set($name . '__time', $timestamp === null ? '' : date('H:i', $timestamp));
        }
        if ($wanted === 'pub') {
            $post->set('submit_publish', 'Publish');
        } else {
            $post->set('status', ['2048']); // checkbox "unpublished" in the settings tab
        }

        $warningsBefore = $this->warnings();

        $startField = $this->inputfield('jk_publish_from');
        $endField = $this->inputfield('jk_publish_until');
        self::callModule('validateDates', $this->hookEvent($startField));
        self::callModule('validateDates', $this->hookEvent($endField));

        return [
            'startErrors' => $startField->getErrors(true),
            'endErrors' => $endField->getErrors(true),
            'status' => (new \ReflectionProperty(self::module(), 'statusToSet'))->getValue(self::module()),
            'warnings' => array_values(array_diff($this->warnings(), $warningsBefore)),
        ];
    }

    private function inputfield(string $name): Inputfield
    {
        $inputfield = self::$wire->modules->get('InputfieldDatetime');
        $inputfield->attr('name', $name);
        return $inputfield;
    }

    private function hookEvent(Inputfield $inputfield): \ProcessWire\HookEvent
    {
        $event = new \ProcessWire\HookEvent(['object' => $inputfield, 'arguments' => [self::$wire->input->post]]);
        self::$wire->wire($event);
        return $event;
    }

    private function warnings(): array
    {
        $texts = [];
        foreach (self::$wire->notices as $notice) {
            if ($notice instanceof NoticeWarning) {
                $texts[] = $notice->text;
            }
        }
        return $texts;
    }
}
