<?php

declare(strict_types=1);

namespace JkPublishPages\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ProcessWire\Fieldgroup;
use ProcessWire\Page;
use ProcessWire\ProcessWire;
use ProcessWire\Template;

/**
 * Base class for integration tests against a real ProcessWire installation
 *
 * - ProcessWire is bootstrapped once per PHPUnit run (default: the site this module is installed in,
 *   or the index.php given in the environment variable JKPP_PW_INDEX)
 * - The tests only work on their own data: a test template "jkpp_test", a test parent page "/jkpp-test-root/",
 *   a test role and a test user. All of it is removed again after each test class (and also before, in case
 *   a previous run was aborted).
 * - The cron job itself (runJkPublishPages) is NEVER executed, because it would process all pages of the site.
 *   The tests only call findCandidates() (read only) and processPage() on their own test pages.
 *
 * The tests will be skipped if ProcessWire cannot be found or the module is not installed.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected const TEMPLATE = 'jkpp_test';
    protected const ROOT_NAME = 'jkpp-test-root';
    protected const ROLE = 'jkpp-test-editor';
    protected const USER = 'jkpp-test-editor';

    protected const MODULE_FIELDS = [
        'jk_publish_open',
        'jk_publish_from',
        'jk_publish_until',
        'jk_action_after',
        'jk_move_child',
        'jk_publish_open_END',
    ];

    protected static ?ProcessWire $wire = null;
    protected static ?Page $root = null;
    protected static ?string $skipReason = null;
    private static bool $createdPublishPermission = false;

    public static function setUpBeforeClass(): void
    {
        self::bootProcessWire();
        if (self::$skipReason !== null) {
            return;
        }

        self::loginSuperuser();
        self::removeFixtures(); // leftovers of an aborted run
        self::createFixtures();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$wire === null || self::$skipReason !== null) {
            return;
        }
        self::loginSuperuser();
        self::removeFixtures();
    }

    protected function setUp(): void
    {
        if (self::$skipReason !== null) {
            $this->markTestSkipped(self::$skipReason);
        }
        self::loginSuperuser();
        self::resetRequestState();
    }

    protected function tearDown(): void
    {
        if (self::$skipReason !== null) {
            return;
        }
        self::resetRequestState();
        self::loginSuperuser();
    }

    /* ---------------------------------------------------------------------------------------------------- */
    /* Bootstrap                                                                                             */
    /* ---------------------------------------------------------------------------------------------------- */

    private static function bootProcessWire(): void
    {
        if (self::$wire !== null || self::$skipReason !== null) {
            return;
        }

        // tests/Integration → tests → JkPublishPages → modules → site → root of the installation
        $index = getenv('JKPP_PW_INDEX') ?: dirname(__DIR__, 5) . '/index.php';
        if (!is_file($index)) {
            self::$skipReason = "ProcessWire index.php not found ($index). Set the environment variable JKPP_PW_INDEX.";
            return;
        }

        // some values ProcessWire expects, which are not set on the command line
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

        ob_start();
        try {
            include $index;
        } finally {
            ob_end_clean();
        }

        $wire = \ProcessWire\wire();
        if (!$wire instanceof ProcessWire) {
            self::$skipReason = 'ProcessWire could not be bootstrapped.';
            return;
        }
        self::$wire = $wire;

        if (!$wire->modules->isInstalled('JkPublishPages')) {
            self::$skipReason = 'The module JkPublishPages is not installed in this ProcessWire installation.';
            return;
        }
        foreach (self::MODULE_FIELDS as $name) {
            if (!$wire->fields->get($name)) {
                self::$skipReason = "The field \"$name\" of the module does not exist - please reinstall the module.";
                return;
            }
        }
    }

    /* ---------------------------------------------------------------------------------------------------- */
    /* Fixtures                                                                                              */
    /* ---------------------------------------------------------------------------------------------------- */

    private static function createFixtures(): void
    {
        $wire = self::$wire;

        // permission page-publish: must exist, otherwise every editor may publish (ProcessWire default)
        // (check with an empty cache - a permission deleted by a previous test class may still be cached)
        self::resetPermissionCache();
        if (!$wire->permissions->has('page-publish')) {
            $wire->permissions->add('page-publish');
            self::$createdPublishPermission = true;
            self::resetPermissionCache();
        }

        // role for an editor with page-edit, but without page-publish, page-delete and page-trash
        $role = $wire->roles->add(self::ROLE);
        $role->addPermission('page-view');
        $role->addPermission('page-edit');
        $wire->roles->save($role);

        // user with this role
        $user = $wire->users->add(self::USER);
        $user->pass = bin2hex(random_bytes(16));
        $user->addRole($role);
        $wire->users->save($user);

        // fieldgroup and template with all fields of the module
        $fieldgroup = new Fieldgroup();
        $wire->wire($fieldgroup);
        $fieldgroup->name = self::TEMPLATE;
        $fieldgroup->add($wire->fields->get('title'));
        foreach (self::MODULE_FIELDS as $name) {
            $fieldgroup->add($wire->fields->get($name));
        }
        $fieldgroup->save();

        $template = new Template();
        $wire->wire($template);
        $template->name = self::TEMPLATE;
        $template->fieldgroup = $fieldgroup;
        $template->useRoles = 1;
        // no view access for guests - the cron job (LazyCron) usually runs as guest and must find the pages anyway
        $template->set('roles', [$role->id]);
        $template->set('editRoles', [$role->id]);
        $template->save();

        // parent page for all test pages
        $root = new Page();
        $wire->wire($root);
        $root->template = $template;
        $root->parent = $wire->pages->get(1);
        $root->name = self::ROOT_NAME;
        $root->title = 'JkPublishPages integration tests';
        $root->addStatus(Page::statusHidden);
        $root->save();
        self::$root = $root;
    }

    private static function removeFixtures(): void
    {
        $wire = self::$wire;

        $template = $wire->templates->get(self::TEMPLATE);
        if ($template) {
            // delete the deepest pages first (also pages in the trash)
            $pages = $wire->pages->find('templates_id=' . $template->id . ', include=all, sort=-parent_id');
            foreach ($pages as $p) {
                try {
                    $fresh = $wire->pages->getFresh($p->id);
                    if ($fresh->id) {
                        $wire->pages->delete($fresh, true);
                    }
                } catch (\Throwable $e) {
                    // continue with the other pages
                }
            }
            $wire->templates->delete($template);
        }

        $fieldgroup = $wire->fieldgroups->get(self::TEMPLATE);
        if ($fieldgroup) {
            $wire->fieldgroups->delete($fieldgroup);
        }

        $user = $wire->users->get('name=' . self::USER);
        if ($user->id) {
            $wire->users->delete($user);
        }

        $role = $wire->roles->get('name=' . self::ROLE);
        if ($role->id) {
            $wire->roles->delete($role);
        }

        if (self::$createdPublishPermission) {
            $permission = $wire->permissions->get('page-publish');
            if ($permission->id) {
                $wire->permissions->delete($permission);
            }
            self::$createdPublishPermission = false;
            self::resetPermissionCache();
        }

        self::$root = null;
    }

    /* ---------------------------------------------------------------------------------------------------- */
    /* Helpers                                                                                               */
    /* ---------------------------------------------------------------------------------------------------- */

    /**
     * ProcessWire keeps the names of all permissions in memory (Permissions::$permissionNames) and does not
     * update this list if a permission is added or deleted during the same request
     */
    private static function resetPermissionCache(): void
    {
        $permissions = self::$wire->permissions;
        $reflection = new \ReflectionProperty($permissions, 'permissionNames');
        $reflection->setValue($permissions, []);
        self::$wire->cache->delete('Permissions.names');
    }

    protected static function loginSuperuser(): void
    {
        $wire = self::$wire;
        $wire->users->setCurrentUser($wire->users->get($wire->config->superUserPageID));
    }

    protected static function loginGuest(): void
    {
        self::$wire->users->setCurrentUser(self::$wire->users->getGuestUser());
    }

    protected static function loginEditor(): void
    {
        $wire = self::$wire;
        $wire->users->setCurrentUser($wire->users->get('name=' . self::USER));
    }

    /**
     * Simulate that the given page is edited in the page editor (POST id)
     */
    protected static function simulateEditorRequest(Page $page, array $post = []): void
    {
        $input = self::$wire->input;
        $input->post->set('id', $page->id);
        foreach ($post as $key => $value) {
            $input->post->set($key, $value);
        }
    }

    private static function resetRequestState(): void
    {
        self::$wire->input->post->removeAll();
        self::$wire->input->get->removeAll();
        self::setModuleProperty('statusToSet', '');
    }

    /**
     * Create a test page
     * Options: unpublished, hidden (bool), from, until (timestamp), action (1-4), move_to (Page), parent (Page)
     */
    protected static function createPage(array $options = []): Page
    {
        $wire = self::$wire;

        $page = new Page();
        $wire->wire($page);
        $page->template = $wire->templates->get(self::TEMPLATE);
        $page->parent = $options['parent'] ?? self::$root;
        $page->name = 'jkpp-' . bin2hex(random_bytes(6));
        $page->title = $page->name;
        if (!empty($options['unpublished'])) {
            $page->addStatus(Page::statusUnpublished);
        }
        if (!empty($options['hidden'])) {
            $page->addStatus(Page::statusHidden);
        }
        $page->save();

        $page->of(false);
        if (isset($options['from'])) {
            $page->jk_publish_from = $options['from'];
        }
        if (isset($options['until'])) {
            $page->jk_publish_until = $options['until'];
        }
        $page->jk_action_after = $options['action'] ?? 1;
        if (isset($options['move_to'])) {
            $page->jk_move_child = $options['move_to'];
        }
        $page->save();

        return $wire->pages->getFresh($page->id);
    }

    protected static function fresh(Page $page): Page
    {
        return self::$wire->pages->getFresh($page->id);
    }

    protected static function module(): object
    {
        return self::$wire->modules->get('JkPublishPages');
    }

    protected static function callModule(string $method, mixed ...$args): mixed
    {
        $module = self::module();
        $reflection = new \ReflectionMethod($module, $method);
        return $reflection->invoke($module, ...$args);
    }

    protected static function setModuleProperty(string $property, mixed $value): void
    {
        $module = self::module();
        $reflection = new \ReflectionProperty($module, $property);
        $reflection->setValue($module, $value);
    }

    protected static function actionOf(Page $page): int
    {
        return (int)self::callModule('getActionAfter', $page);
    }
}
