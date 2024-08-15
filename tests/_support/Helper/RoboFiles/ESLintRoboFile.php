<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Helper\RoboFiles;

use League\Container\Container as LeagueContainer;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\TaskInterface;
use Robo\Tasks;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\Reporter\SummaryReporter;
use Sweetchuck\LintReport\Reporter\VerboseReporter;
use Sweetchuck\Robo\ESLint\ESLintTaskLoader;

class ESLintRoboFile extends Tasks implements ConfigAwareInterface
{
    use ESLintTaskLoader;
    use ConfigAwareTrait;

    protected string $workingDirectory = './tests/_data';

    protected string $reportsDir = './tests/_data/actual';

    /**
     * {@inheritdoc}
     */
    protected function output()
    {
        return $this->getContainer()->get('output');
    }

    /**
     * @hook pre-command @initLintReporters
     */
    public function initLintReporters(): void
    {
        $container = $this->getContainer();
        if (!($container instanceof LeagueContainer)) {
            return;
        }

        foreach (BaseReporter::getServices() as $name => $class) {
            if ($container->has($name)) {
                continue;
            }

            $container
                ->add($name, $class)
                ->setShared(false);
        }
    }

    /**
     * @command lint:stylish:std-output
     *
     * @initLintReporters
     */
    public function cmdLintStylishStdOutputExecute(): TaskInterface
    {
        return $this
            ->taskESLintRunFiles()
            ->setWorkingDirectory($this->workingDirectory)
            ->setFiles(['samples/'])
            ->setFormat('stylish');
    }

    /**
     * @command lint:stylish:file
     *
     * @initLintReporters
     */
    public function cmdLintStylishFileExecute(): TaskInterface
    {
        return $this
            ->taskESLintRunFiles()
            ->setWorkingDirectory($this->workingDirectory)
            ->setFiles(['./samples/'])
            ->setFormat('stylish')
            ->setOutputFile('./actual/native.stylish.txt');
    }

    /**
     * @command lint:all-in-one
     *
     * @initLintReporters
     */
    public function cmdLintAllInOneExecute(): TaskInterface
    {
        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.summary.txt");

        $task = $this
            ->taskESLintRunFiles()
            ->setWorkingDirectory($this->workingDirectory)
            ->setFiles(['samples/'])
            ->setFormat('json')
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile);
        $task->setOutput($this->output());

        return $task;
    }

    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @command lint:input
     *
     * @initLintReporters
     */
    public function cmdLintInputExecute(
        $options = [
            'command-only' => false,
        ]
    ): TaskInterface {
        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.summary.txt");

        $files = [
            'invalid-01.js' => [
                'fileName' => 'samples/invalid-01.js',
                'command' => sprintf('cat %s', 'samples/invalid-01.js'),
                'content' => null,
            ],
        ];

        if (!$options['command-only']) {
            $files['invalid-01.js']['content'] = (string) file_get_contents(
                $this->workingDirectory . '/' . $files['invalid-01.js']['fileName'],
            );
        }

        return $this
            ->taskESLintRunInput()
            ->setWorkingDirectory($this->workingDirectory)
            ->setFormat('json')
            ->setFiles($files)
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile);
    }
}
