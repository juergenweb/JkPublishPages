<?php

declare(strict_types=1);

namespace JkPublishPages\Tests\Integration;

/**
 * Integration tests for reading the date input (POST only), the escaping of admin output and the cron log
 */
final class InputAndOutputTest extends IntegrationTestCase
{
    private const H = 3600;

    /* date input: only POST, only scalar values ------------------------------------------------------------ */

    public function testDateIsReadFromPost(): void
    {
        $field = self::$wire->fields->get('jk_publish_from');
        if ($field->inputType !== 'html') {
            $this->markTestSkipped(
                'The field jk_publish_from does not use the input type "html" in this installation.'
            );
        }

        self::$wire->input->post->set('jk_publish_from', '2030-01-02');
        self::$wire->input->post->set('jk_publish_from__time', '10:30');

        $this->assertSame(mktime(10, 30, 0, 1, 2, 2030), self::callModule('convertDateToTimeStamp', 'jk_publish_from'));
    }

    public function testDateFromGetIsIgnored(): void
    {
        self::$wire->input->get->set('jk_publish_from', '2030-01-02');
        self::$wire->input->get->set('jk_publish_from__time', '10:30');

        $this->assertNull(self::callModule('convertDateToTimeStamp', 'jk_publish_from'));
    }

    public function testArrayValueIsIgnored(): void
    {
        self::$wire->input->post->set('jk_publish_from', ['2030-01-02']);

        $this->assertNull(self::callModule('convertDateToTimeStamp', 'jk_publish_from'));
    }

    /* escaping --------------------------------------------------------------------------------------------- */

    public function testOutputIsEntityEncoded(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;',
            self::callModule('esc', '<script>alert("x")</script>')
        );
        $this->assertSame(
            'M&auml;rz &amp; April',
            self::callModule('esc', 'M&auml;rz & April'),
            'existing entities must not be encoded twice'
        );
    }

    public function testScheduleIconTitleIsEncoded(): void
    {
        $page = self::createPage(['unpublished' => true, 'from' => time() + self::H]);

        $event = new \ProcessWire\HookEvent([
            'object' => null,
            'arguments' => [$page],
            'return' => "<span class='label_title'>Title</span>",
        ]);
        self::$wire->wire($event);
        self::callModule('addPublishIcon', $event);

        $this->assertStringContainsString('fa-clock-o', $event->return);
        $this->assertMatchesRegularExpression(
            '/title="[^"<>]*"/',
            $event->return,
            'the title attribute must not contain unencoded markup'
        );
    }

    /* toggle link in the module configuration -------------------------------------------------------------- */

    public function testToggleLinkIsOnlyAddedToTheTemplateSelection(): void
    {
        $inputfields = self::$wire->modules->get('InputfieldWrapper');
        self::module()->getModuleConfigInputfields($inputfields);
        $templates = $inputfields->getChildByName('input_templates');

        $this->assertStringContainsString('data-jkpp-toggle', (string)$templates->prependMarkup);
        $this->assertStringContainsString('type="button"', (string)$templates->prependMarkup);

        // a checkbox field with the same name in another module must not get the toggle link
        $other = self::$wire->modules->get('InputfieldCheckboxes');
        $other->attr('name', 'input_templates');
        $other->addOption('a', 'A');
        $this->assertStringNotContainsString('data-jkpp-toggle', $other->render());
    }

    /* schedule plan ---------------------------------------------------------------------------------------- */

    public function testScheduleShowsUnpublishingOfManuallyPublishedPageWithStartInFuture(): void
    {
        // the old schedule text did not mention that the cron job unpublishes such a page on its next run
        $page = self::fresh(self::createPage([
            'from' => time() + self::H,
            'until' => time() + 2 * self::H,
            'action' => 2,
        ]));
        $start = (int)$page->getUnformatted('jk_publish_from');
        $end = (int)$page->getUnformatted('jk_publish_until');

        // expected texts from the module itself, so the test also works with a translated admin
        $expected = [
            sprintf(self::text('will be "%s" on the next run of the cron job'), self::text('unpublished')),
            sprintf(
                self::text('will be "%s" on %s'),
                self::text('published'),
                self::callModule('formatScheduleDate', $start, 'jk_publish_from')
            ),
            sprintf(
                self::text('will be moved to the trash on %s'),
                self::callModule('formatScheduleDate', $end, 'jk_publish_until')
            ),
        ];

        $this->assertSame($expected, self::callModule('getScheduleItems', $page));
    }

    public function testScheduleShowsRemainsTextWithoutDates(): void
    {
        $page = self::fresh(self::createPage());

        $this->assertSame(
            [sprintf(self::text('remains "%s"'), self::text('published'))],
            self::callModule('getScheduleItems', $page)
        );
        $this->assertSame([], self::callModule('getScheduleItems', $page, true));
    }

    /**
     * Translated text of the module (in the language of the current user)
     */
    private static function text(string $text): string
    {
        return self::module()->_($text);
    }

    /* cron log --------------------------------------------------------------------------------------------- */

    public function testCronLogContainsUserWhoChangedThePage(): void
    {
        $superuser = self::$wire->users->get(self::$wire->config->superUserPageID);
        $page = self::createPage(['unpublished' => true, 'from' => time() - self::H]); // saved as superuser

        self::loginGuest(); // the cron job usually runs as guest
        self::callModule('processPage', self::fresh($page), time());
        self::loginSuperuser();

        $lines = self::$wire->log->getLines('jkpublishpages', ['limit' => 20]);
        $found = false;
        foreach ($lines as $line) {
            if (str_contains($line, 'Page ' . $page->id . ' ') && str_contains($line, 'published')) {
                $this->assertStringContainsString('[last changed by: ' . $superuser->name . ']', $line);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'log entry for the published page not found');
    }
}
