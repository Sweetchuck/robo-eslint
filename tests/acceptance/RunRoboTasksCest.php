<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Acceptance;

use Sweetchuck\Robo\ESLint\Tests\AcceptanceTester;
use Sweetchuck\Robo\ESLint\Tests\Helper\RoboFiles\ESLintRoboFile;

class RunRoboTasksCest
{
    protected string $expectedDir = '';

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

    public function lintAllInOneTask(AcceptanceTester $tester): void
    {
        $id = __METHOD__;
        $tester->runRoboTask(
            $id,
            ESLintRoboFile::class,
            'lint:all-in-one',
        );

        $exitCode = $tester->getRoboTaskExitCode($id);
        $stdOutput = $tester->getRoboTaskStdOutput($id);
        $stdError = $tester->getRoboTaskStdError($id);

        $tester->assertSame(2, $exitCode);
        $tester->assertStringContainsString(file_get_contents("{$this->expectedDir}/extra.verbose.txt"), $stdOutput);
        $tester->assertStringContainsString(file_get_contents("{$this->expectedDir}/extra.summary.txt"), $stdOutput);
        $tester->assertStringContainsString('One or more errors were reported (and any number of warnings)', $stdError);
        $tester->haveAFileLikeThis('extra.verbose.txt');
        $tester->haveAFileLikeThis('extra.summary.txt');
    }

    public function lintStylishFileTask(AcceptanceTester $tester): void
    {
        $id = __METHOD__;
        $tester->runRoboTask(
            $id,
            ESLintRoboFile::class,
            'lint:stylish-file'
        );

        $exitCode = $tester->getRoboTaskExitCode($id);
        $stdError = $tester->getRoboTaskStdError($id);

        $tester->assertSame(2, $exitCode);
        $tester->assertStringContainsString(
            'One or more errors were reported (and any number of warnings)',
            $stdError,
        );
        $tester->haveAFileLikeThis('native.stylish.txt');
    }

    public function lintStylishStdOutputTask(AcceptanceTester $tester): void
    {
        $id = __METHOD__;
        $tester->runRoboTask(
            $id,
            ESLintRoboFile::class,
            'lint:stylish-std-output'
        );

        $exitCode = $tester->getRoboTaskExitCode($id);
        $stdOutput = $tester->getRoboTaskStdOutput($id);
        $stdError = $tester->getRoboTaskStdError($id);

        $tester->assertSame(2, $exitCode);
        $tester->assertStringContainsString(file_get_contents("{$this->expectedDir}/native.stylish.txt"), $stdOutput);
        $tester->assertStringContainsString('One or more errors were reported (and any number of warnings)', $stdError);
    }

    public function lintInputTaskCommandOnlyFalse(AcceptanceTester $i): void
    {
        $this->lintInput($i, ['lint:input']);
    }

    public function lintInputTaskCommandOnlyTrue(AcceptanceTester $i): void
    {
        $this->lintInput($i, ['lint:input', '--command-only']);
    }

    protected function lintInput(AcceptanceTester $tester, array $argsAndOptions = [])
    {
        $id = implode(' ', $argsAndOptions);
        $tester->runRoboTask(
            $id,
            ESLintRoboFile::class,
            ...$argsAndOptions,
        );

        $exitCode = $tester->getRoboTaskExitCode($id);
        $stdError = $tester->getRoboTaskStdError($id);

        $tester->assertSame(2, $exitCode);
        $tester->assertStringContainsString('One or more errors were reported (and any number of warnings)', $stdError);
        $tester->haveAFileLikeThis('extra.summary.txt');
        $tester->haveAFileLikeThis('extra.verbose.txt');
    }
}
