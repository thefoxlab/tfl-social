<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Tests;

require_once __DIR__ . '/TokenLifecycleTest.php';
require_once __DIR__ . '/ConnectionServiceTest.php';
require_once __DIR__ . '/PostServiceTest.php';
require_once __DIR__ . '/FacebookOAuthTest.php';
require_once __DIR__ . '/ConnectorTest.php';
require_once __DIR__ . '/SynchronizerTest.php';
require_once __DIR__ . '/ConfigResolutionTest.php';

echo "====================================================\n";
echo "       TFL Social Complete Test Suite Runner        \n";
echo "====================================================\n\n";

(new TokenLifecycleTest())->run();
echo "\n----------------------------------------------------\n\n";

(new ConnectionServiceTest())->run();
echo "\n----------------------------------------------------\n\n";

(new PostServiceTest())->run();
echo "\n----------------------------------------------------\n\n";

(new FacebookOAuthTest())->run();
echo "\n----------------------------------------------------\n\n";

(new ConnectorTest())->run();
echo "\n----------------------------------------------------\n\n";

(new SynchronizerTest())->run();
echo "\n----------------------------------------------------\n\n";

(new ConfigResolutionTest())->run();
echo "\n----------------------------------------------------\n\n";

echo "====================================================\n";
echo "   SUCCESS: ALL SUITES COMPLETED WITH NO FAILURES   \n";
echo "====================================================\n";
