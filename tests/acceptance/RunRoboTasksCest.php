<?php

namespace Sweetchuck\Robo\ESLint\Tests\Acceptance;

use Sweetchuck\Robo\ESLint\Test\AcceptanceTester;

class RunRoboTasksCest
{
    /**
     * @var string
     */
    protected $expectedDir = '';

    public function __construct()
    {
        $this->expectedDir = codecept_data_dir('expected');
    }

    // @codingStandardsIgnoreStart
    public function _before(AcceptanceTester $i)
    {
        // @codingStandardsIgnoreEnd
        $i->clearTheReportsDir();
    }

    public function lintAllInOneTask(AcceptanceTester $i): void
    {
        $roboTaskName = 'lint:all-in-one';

        $i->wantTo("Run Robo task '<comment>$roboTaskName</comment>'.");
        $i->clearTheReportsDir();
        $i->runRoboTask($roboTaskName);
        $i->expectTheExitCodeToBe(2);
        $i->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/extra.verbose.txt"));
        $i->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/extra.summary.txt"));
        $i->haveAFileLikeThis('extra.verbose.txt');
        $i->haveAFileLikeThis('extra.summary.txt');
        $i->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintStylishFileTask(AcceptanceTester $i): void
    {
        $roboTaskName = 'lint:stylish-file';

        $i->wantTo("Run Robo task '<comment>$roboTaskName</comment>'.");
        $i->runRoboTask($roboTaskName);
        $i->expectTheExitCodeToBe(2);
        $i->haveAFileLikeThis('native.stylish.txt');
        $i->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintStylishStdOutputTask(AcceptanceTester $i): void
    {
        $roboTaskName = 'lint:stylish-std-output';

        $i->wantTo("Run Robo task '<comment>$roboTaskName</comment>'.");
        $i->runRoboTask($roboTaskName);
        $i->expectTheExitCodeToBe(2);
        $i->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/native.stylish.txt"));
        $i->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    /**
     * This test is ignored.
     *
     * @link https://github.com/Sweetchuck/robo-eslint/issues/6
     */
    protected function lintInputTaskCommandOnlyFalse(AcceptanceTester $i): void
    {
        $this->runLintInput($i, 'lint:input');
    }

    public function runLintInputTaskCommandOnlyTrue(AcceptanceTester $i): void
    {
        $this->runLintInput($i, 'lint:input', [], ['command-only' => null]);
    }

    protected function runLintInput(AcceptanceTester $i, string $roboTaskName, array $args = [], array $options = [])
    {
        $cmdPattern = '%s';
        $cmdArgs = [
            escapeshellarg($roboTaskName),
        ];

        foreach ($options as $option => $value) {
            $cmdPattern .= " --$option";
            if ($value !== null) {
                $cmdPattern .= '=%s';
                $cmdArgs[] = escapeshellarg($value);
            }
        }

        $cmdPattern .= str_repeat(' %s', count($args));
        foreach ($args as $arg) {
            $cmdArgs[] = escapeshellarg($arg);
        }

        $command = vsprintf($cmdPattern, $cmdArgs);

        $i->wantTo("Run Robo task '<comment>$command</comment>'.");
        $i->runRoboTask($roboTaskName, $args, $options);
        $i->expectTheExitCodeToBe(2);
        $i->haveAFileLikeThis('extra.summary.txt');
        $i->haveAFileLikeThis('extra.verbose.txt');
        $i->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }
}
