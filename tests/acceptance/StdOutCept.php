<?php
/**
 * @var \Codeception\Scenario $scenario
 */

use \PHPUnit_Framework_Assert as Assert;

$i = new AcceptanceTester($scenario);
$i->wantTo('robo lint:stylish-stdout');
$i->runRoboTask('lint:stylish-stdout');

Assert::assertContains(
    " error  'a' is not defined  no-undef",
    $i->getStdOutput()
);

Assert::assertEquals(1, $i->getExitCode());
