<?php
/**
 * @var \Codeception\Scenario $scenario
 */

use \PHPUnit_Framework_Assert as Assert;

$i = new AcceptanceTester($scenario);
$i->wantTo('robo lint:json-file');
$i->clearTheReportsDir();
$i->runRoboTask('lint:json-file');
$i->seeAValidJsonFile('reports/eslint-samples.json');

Assert::assertEquals(1, $i->getExitCode());
