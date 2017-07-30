<?php

namespace Sweetchuck\Robo\ESLint\Tests\Acceptance;

use Sweetchuck\Robo\ESLint\Test\AcceptanceTester;
use Sweetchuck\Robo\ESLint\Test\Helper\RoboFiles\ESLintRoboFile;

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
        $id = __METHOD__;
        $this->runRoboTask(
            $i,
            $id,
            ESLintRoboFile::class,
            'lint:all-in-one'
        );

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdOutput = $i->getRoboTaskStdOutput($id);
        $stdError = $i->getRoboTaskStdError($id);

        $i->assertEquals(2, $exitCode);
        $i->assertContains(file_get_contents("{$this->expectedDir}/extra.verbose.txt"), $stdOutput);
        $i->assertContains(file_get_contents("{$this->expectedDir}/extra.summary.txt"), $stdOutput);
        $i->assertContains('One or more errors were reported (and any number of warnings)', $stdError);
        $i->haveAFileLikeThis('extra.verbose.txt');
        $i->haveAFileLikeThis('extra.summary.txt');
    }

    public function lintStylishFileTask(AcceptanceTester $i): void
    {
        $id = __METHOD__;
        $this->runRoboTask(
            $i,
            $id,
            ESLintRoboFile::class,
            'lint:stylish-file'
        );

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdError = $i->getRoboTaskStdError($id);

        $i->assertEquals(2, $exitCode);
        $i->assertContains('One or more errors were reported (and any number of warnings)', $stdError);
        $i->haveAFileLikeThis('native.stylish.txt');
    }

    public function lintStylishStdOutputTask(AcceptanceTester $i): void
    {
        $id = __METHOD__;
        $this->runRoboTask(
            $i,
            $id,
            ESLintRoboFile::class,
            'lint:stylish-std-output'
        );

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdOutput = $i->getRoboTaskStdOutput($id);
        $stdError = $i->getRoboTaskStdError($id);

        $i->assertEquals(2, $exitCode);
        $i->assertContains(file_get_contents("{$this->expectedDir}/native.stylish.txt"), $stdOutput);
        $i->assertContains('One or more errors were reported (and any number of warnings)', $stdError);
    }

    public function lintInputTaskCommandOnlyFalse(AcceptanceTester $i): void
    {
        $this->lintInput($i, ['lint:input']);
    }

    public function lintInputTaskCommandOnlyTrue(AcceptanceTester $i): void
    {
        $this->lintInput($i, ['lint:input', '--command-only']);
    }

    protected function lintInput(AcceptanceTester $i, array $argsAndOptions = [])
    {
        $id = implode(' ', $argsAndOptions);
        $this->runRoboTask($i, $id, ESLintRoboFile::class, ...$argsAndOptions);

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdError = $i->getRoboTaskStdError($id);

        $i->assertEquals(2, $exitCode);
        $i->assertContains('One or more errors were reported (and any number of warnings)', $stdError);
        $i->haveAFileLikeThis('extra.summary.txt');
        $i->haveAFileLikeThis('extra.verbose.txt');
    }

    protected function runRoboTask(AcceptanceTester $i, string $id, string $class, string ...$args)
    {
        $command = implode(' ', $args);
        $i->wantTo("Run Robo task: $command");

        $cwd = getcwd();
        chdir(codecept_data_dir());
        $i->runRoboTask($id, $class, ...$args);
        chdir($cwd);
    }
}
