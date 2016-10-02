<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$roboTaskName = 'lint:stylish-std-output';
$expectedDir = codecept_data_dir('expected');

$i = new AcceptanceTester($scenario);
$i->wantTo("Run Robo task '<comment>$roboTaskName</comment>'.");
$i
    ->clearTheReportsDir()
    ->runRoboTask($roboTaskName)
    ->expectTheExitCodeToBe(2)
    ->seeThisTextInTheStdOutput(file_get_contents("$expectedDir/native.stylish.txt"))
    ->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
