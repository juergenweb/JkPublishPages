<?php

/**
 * PHPUnit bootstrap
 *
 * Loads the Composer autoloader (PHPUnit, test classes) and the rules class directly, so the tests do not
 * depend on a regenerated Composer classmap.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../JkPublishPagesRules.php';
require_once __DIR__ . '/Integration/IntegrationTestCase.php';
