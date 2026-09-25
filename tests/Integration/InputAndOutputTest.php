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
                $this->markTestSkipped('The field jk_publish_from does not use the input type "html" in this installation.');
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
            $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', self::callModule('esc', '<script>alert("x")</script>'));
            $this->assertSame('M&auml;rz &amp; April', self::callModule('esc', 'M&auml;rz & April'), 'existing entities must not be encoded twice');
        }

        public function testScheduleIconTitleIsEncoded(): void
        {
            $page = self::createPage(['unpublished' => true, 'from' => time() + self::H]);

            $event = new \ProcessWire\HookEvent(['object' => null, 'arguments' => [$page], 'return' => "<span class='label_title'>Title</span>"]);
            self::$wire->wire($event);
            self::callModule('addPublishIcon', $event);

            $this->assertStringContainsString('fa-clock-o', $event->return);
            $this->assertMatchesRegularExpression('/title="[^"<>]*"/', $event->return, 'the title attribute must not contain unencoded markup');
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
