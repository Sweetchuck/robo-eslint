<?php

namespace Cheppers\Robo\ESLint\Test\Acceptance;

use AcceptanceTester;

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

    public function lintAllInOneTask(AcceptanceTester $i)
    {
        $roboTaskName = 'lint:all-in-one';
        $i->wantTo("Run Robo task '<comment>$roboTaskName</comment>'.");
        $i
            ->clearTheReportsDir()
            ->runRoboTask($roboTaskName)
            ->expectTheExitCodeToBe(2)
            ->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/extra.verbose.txt"))
            ->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/extra.summary.txt"))
            ->haveAFileLikeThis('extra.verbose.txt')
            ->haveAFileLikeThis('extra.summary.txt')
            ->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintStylishFileTask(AcceptanceTester $i)
    {
        $roboTaskName = 'lint:stylish-file';

        $i->wantTo("Run Robo task '<comment>$roboTaskName</comment>'.");
        $i
            ->runRoboTask($roboTaskName)
            ->expectTheExitCodeToBe(2)
            ->haveAFileLikeThis('native.stylish.txt')
            ->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintStylishStdOutputTask(AcceptanceTester $i)
    {
        $roboTaskName = 'lint:stylish-std-output';

        $i->wantTo("Run Robo task '<comment>$roboTaskName</comment>'.");
        $i
            ->runRoboTask($roboTaskName)
            ->expectTheExitCodeToBe(2)
            ->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/native.stylish.txt"))
            ->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    /**
     * This test is ignored.
     *
     * @param AcceptanceTester $i
     *
     * @link https://github.com/Cheppers/robo-eslint/issues/6
     */
    protected function lintInputWithoutJarTaskCommandOnlyFalse(AcceptanceTester $i)
    {
        $this->runLintInput($i, 'lint:input-without-jar');
    }

    public function runLintInputWithoutJarTaskCommandOnlyTrue(AcceptanceTester $i)
    {
        $this->runLintInput($i, 'lint:input-without-jar', [], ['command-only' => null]);
    }

    /**
     * This test is ignored.
     *
     * @param AcceptanceTester $i
     *
     * @link https://github.com/Cheppers/robo-eslint/issues/6
     */
    protected function lintInputWithJarTaskCommandOnlyFalse(AcceptanceTester $i)
    {
        $this->runLintInput($i, 'lint:input-with-jar');
    }

    public function runLintInputWithJarTaskCommandOnlyTrue(AcceptanceTester $i)
    {
        $this->runLintInput($i, 'lint:input-with-jar', [], ['command-only' => null]);
    }

    /**
     * @param AcceptanceTester $i
     * @param string $roboTaskName
     * @param array $args
     * @param array $options
     */
    protected function runLintInput(AcceptanceTester $i, $roboTaskName, array $args = [], array $options = [])
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
        $i
            ->runRoboTask($roboTaskName, $args, $options)
            ->expectTheExitCodeToBe(2)
            ->haveAFileLikeThis('extra.summary.txt')
            ->haveAFileLikeThis('extra.verbose.txt')
            ->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }
}
