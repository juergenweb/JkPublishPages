<?php

declare(strict_types=1);

namespace JkPublishPages\Tests\Integration;

/**
 * Integration tests for the permission checks when a page is saved in the page editor
 * (checkPublishingPermissions) and for the status change after the date validation (changeStatus)
 */
final class PermissionsTest extends IntegrationTestCase
{
    private const H = 3600;

    public function testEditorFixtureIsValid(): void
    {
        $page = self::createPage(['unpublished' => true]);

        self::loginEditor();
        $page = self::fresh($page);

        $this->assertTrue($page->editable(), 'the test editor must be able to edit test pages');
        $this->assertFalse($page->publishable(), 'the test editor must not be able to publish test pages');
        $this->assertFalse($page->deleteable(), 'the test editor must not be able to delete test pages');
    }

    public function testEditorWithoutPublishPermissionCannotChangeDates(): void
    {
        $page = self::createPage(['unpublished' => true]);

        self::loginEditor();
        self::simulateEditorRequest($page);
        $page = self::fresh($page);
        $page->of(false);
        $page->jk_publish_from = time() - self::H;
        $page->save();

        self::loginSuperuser();
        $this->assertEmpty(
            self::fresh($page)->getUnformatted('jk_publish_from'),
            'change of the start date must be reverted'
        );
    }

    public function testEditorCannotSelectDeleteAction(): void
    {
        $page = self::createPage(['until' => time() + self::H, 'action' => 1]);

        self::loginEditor();
        self::simulateEditorRequest($page);
        $page = self::fresh($page);
        $page->of(false);
        $page->jk_action_after = 4;
        $page->save();

        self::loginSuperuser();
        $this->assertSame(1, self::actionOf(self::fresh($page)), 'action "delete" must be reverted');
    }

    public function testEditorCannotSelectTrashAction(): void
    {
        $page = self::createPage(['until' => time() + self::H, 'action' => 1]);

        self::loginEditor();
        self::simulateEditorRequest($page);
        $page = self::fresh($page);
        $page->of(false);
        $page->jk_action_after = 2;
        $page->save();

        self::loginSuperuser();
        $this->assertSame(1, self::actionOf(self::fresh($page)), 'action "trash" must be reverted');
    }

    public function testSuperuserCanChangeDatesAndAction(): void
    {
        $page = self::createPage(['unpublished' => true, 'action' => 1]);
        $from = time() + self::H;

        self::simulateEditorRequest($page);
        $page = self::fresh($page);
        $page->of(false);
        $page->jk_publish_from = $from;
        $page->jk_action_after = 4;
        $page->save();

        $fresh = self::fresh($page);
        $this->assertSame($from, (int)$fresh->getUnformatted('jk_publish_from'));
        $this->assertSame(4, self::actionOf($fresh));
    }

    public function testStatusFromPostParameterIsIgnored(): void
    {
        $page = self::createPage(['unpublished' => true]);

        // a manipulated POST parameter must not change the status (the status is only set internally)
        self::simulateEditorRequest($page, ['changestatus' => 'pub']);
        $page = self::fresh($page);
        $page->of(false);
        $page->title = $page->title . ' (changed)';
        $page->save();

        $this->assertTrue(self::fresh($page)->isUnpublished());
    }

    public function testStatusChangeKeepsOtherStatusFlags(): void
    {
        $page = self::createPage(['hidden' => true]);

        self::simulateEditorRequest($page);
        self::setModuleProperty('statusToSet', 'unpub'); // as set by validateDates()
        $page = self::fresh($page);
        $page->of(false);
        $page->save();

        $fresh = self::fresh($page);
        $this->assertTrue($fresh->isUnpublished());
        $this->assertTrue($fresh->isHidden(), 'the hidden status must not be removed (addStatus instead of setStatus)');
    }

    public function testStatusChangeIsNotAppliedToOtherPages(): void
    {
        $edited = self::createPage();
        $other = self::createPage();

        self::simulateEditorRequest($edited);
        self::setModuleProperty('statusToSet', 'unpub');
        $other = self::fresh($other);
        $other->of(false);
        $other->save();

        $this->assertFalse(self::fresh($other)->isUnpublished(), 'only the edited page may be changed');
    }
}
