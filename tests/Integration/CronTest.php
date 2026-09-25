<?php

declare(strict_types=1);

namespace JkPublishPages\Tests\Integration;

use ProcessWire\Page;

/**
 * Integration tests for the cron job: findCandidates() (database selectors) and processPage() (actions)
 * Only test pages are processed - the cron job itself is never executed.
 */
final class CronTest extends IntegrationTestCase
{
    private const H = 3600;

    private function candidates(): \ProcessWire\PageArray
    {
        return self::callModule('findCandidates');
    }

    private function process(Page $page): void
    {
        self::callModule('processPage', self::fresh($page), time());
    }

    /* publishing ------------------------------------------------------------------------------------------- */

    public function testUnpublishedPageIsPublishedWhenStartIsReached(): void
    {
        $page = self::createPage(['unpublished' => true, 'from' => time() - self::H]);

        $this->assertTrue($this->candidates()->has($page), 'unpublished page must be found (include=all)');
        $this->process($page);

        $this->assertFalse(self::fresh($page)->isUnpublished());
    }

    public function testHiddenUnpublishedPageIsFoundAndKeepsHiddenStatus(): void
    {
        $page = self::createPage(['unpublished' => true, 'hidden' => true, 'from' => time() - self::H]);

        $this->assertTrue($this->candidates()->has($page), 'hidden page must be found (include=all)');
        $this->process($page);

        $fresh = self::fresh($page);
        $this->assertFalse($fresh->isUnpublished());
        $this->assertTrue($fresh->isHidden(), 'the hidden status must not be removed');
    }

    public function testHiddenPublishedPageIsFoundWhenEnded(): void
    {
        $page = self::createPage(['hidden' => true, 'until' => time() - self::H, 'action' => 1]);

        $this->assertTrue($this->candidates()->has($page), 'hidden page must be found (include=all)');
    }

    public function testCandidatesAreFoundWhenCronRunsAsGuest(): void
    {
        // LazyCron usually runs as guest - guests have no view access to the test template
        $toPublish = self::createPage(['unpublished' => true, 'from' => time() - self::H]);
        $toUnpublish = self::createPage(['until' => time() - self::H, 'action' => 1]);

        self::loginGuest();
        $this->assertFalse(self::fresh($toUnpublish)->viewable(), 'fixture: guest must not be able to view test pages');

        $candidates = $this->candidates();
        $this->assertTrue($candidates->has($toPublish), 'page to publish must be found without access check');
        $this->assertTrue($candidates->has($toUnpublish), 'page to unpublish must be found without access check');

        // processing also works as guest
        $this->process($toPublish);
        $this->process($toUnpublish);
        self::loginSuperuser();
        $this->assertFalse(self::fresh($toPublish)->isUnpublished());
        $this->assertTrue(self::fresh($toUnpublish)->isUnpublished());
    }

    public function testPageIsNotPublishedBeforeStart(): void
    {
        $page = self::createPage(['unpublished' => true, 'from' => time() + self::H]);

        $this->assertFalse($this->candidates()->has($page));
        $this->process($page);

        $this->assertTrue(self::fresh($page)->isUnpublished());
    }

    public function testPageWithoutDatesIsNoCandidate(): void
    {
        $published = self::createPage();
        $unpublished = self::createPage(['unpublished' => true]);

        $candidates = $this->candidates();
        $this->assertFalse($candidates->has($published));
        $this->assertFalse($candidates->has($unpublished));
    }

    /* end of publication ----------------------------------------------------------------------------------- */

    public function testPageIsUnpublishedWhenEnded(): void
    {
        $page = self::createPage(['from' => time() - 2 * self::H, 'until' => time() - self::H, 'action' => 1]);

        $this->assertTrue($this->candidates()->has($page));
        $this->process($page);

        $fresh = self::fresh($page);
        $this->assertTrue($fresh->id > 0);
        $this->assertTrue($fresh->isUnpublished());
    }

    public function testUnpublishingKeepsHiddenStatus(): void
    {
        $page = self::createPage(['hidden' => true, 'until' => time() - self::H, 'action' => 1]);

        $this->process($page);

        $fresh = self::fresh($page);
        $this->assertTrue($fresh->isUnpublished());
        $this->assertTrue($fresh->isHidden());
    }

    public function testPageIsMovedToTrashWhenEnded(): void
    {
        $page = self::createPage(['until' => time() - self::H, 'action' => 2]);

        $this->process($page);

        $this->assertTrue(self::fresh($page)->isTrash());
    }

    public function testPageIsDeletedWhenEnded(): void
    {
        $page = self::createPage(['until' => time() - self::H, 'action' => 4]);
        $id = $page->id;

        $this->process($page);

        $this->assertSame(0, self::$wire->pages->getFresh($id)->id);
    }

    public function testPageWithChildrenIsNotDeletedButUnpublished(): void
    {
        $page = self::createPage(['until' => time() - self::H, 'action' => 4]);
        $child = self::createPage(['parent' => $page]);

        $this->process($page);

        $fresh = self::fresh($page);
        $this->assertTrue($fresh->id > 0, 'page with children must not be deleted');
        $this->assertTrue($fresh->isUnpublished());
        $this->assertTrue(self::fresh($child)->id > 0);
    }

    public function testPageIsMovedToNewParentWhenEnded(): void
    {
        $target = self::createPage();
        $page = self::createPage(['until' => time() - self::H, 'action' => 3, 'move_to' => $target]);

        $this->process($page);

        $fresh = self::fresh($page);
        $this->assertSame($target->id, $fresh->parent_id);
        $this->assertFalse($fresh->isUnpublished(), 'moving does not change the status');
        $this->assertEmpty($fresh->getUnformatted('jk_publish_until'), 'end date must be removed after moving');
        $this->assertSame(1, self::actionOf($fresh), 'action must be reset to "unpublish" after moving');
    }

    public function testPageWithoutNewParentIsUnpublishedInsteadOfMoved(): void
    {
        $page = self::createPage(['until' => time() - self::H, 'action' => 3]);
        $parentId = $page->parent_id;

        $this->process($page);

        $fresh = self::fresh($page);
        $this->assertSame($parentId, $fresh->parent_id);
        $this->assertTrue($fresh->isUnpublished());
    }

    /* regressions ------------------------------------------------------------------------------------------ */

    public function testManuallyPublishedPageWithStartInFutureIsOnlyUnpublishedNotDeleted(): void
    {
        $page = self::createPage([
            'from' => time() + self::H,
            'until' => time() + 2 * self::H,
            'action' => 4,
        ]);

        $this->assertTrue($this->candidates()->has($page));
        $this->process($page);

        $fresh = self::fresh($page);
        $this->assertTrue($fresh->id > 0, 'page must not be deleted before the end date');
        $this->assertTrue($fresh->isUnpublished());
    }

    public function testManuallyUnpublishedExpiredPageIsMovedToTrash(): void
    {
        $page = self::createPage([
            'unpublished' => true,
            'from' => time() - 2 * self::H,
            'until' => time() - self::H,
            'action' => 2,
        ]);

        $this->assertTrue($this->candidates()->has($page));
        $this->process($page);

        $this->assertTrue(self::fresh($page)->isTrash());
    }
}
