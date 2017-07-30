<?php

namespace Sweetchuck\Robo\ESLint\Test\Helper\RoboFiles;

use League\Container\ContainerInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Tasks;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\Reporter\SummaryReporter;
use Sweetchuck\LintReport\Reporter\VerboseReporter;
use Sweetchuck\Robo\ESLint\ESLintTaskLoader;

class ESLintRoboFile extends Tasks implements ConfigAwareInterface
{
    use ESLintTaskLoader;
    use ConfigAwareTrait;

    /**
     * @var string
     */
    protected $reportsDir = 'actual';

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        BaseReporter::lintReportConfigureContainer($this->container);

        return $this;
    }

    /**
     * @return \Sweetchuck\Robo\ESLint\Task\ESLintRunFiles|\Robo\Collection\CollectionBuilder
     */
    public function lintStylishStdOutput()
    {
        return $this->taskESLintRunFiles()
            ->setFiles(['samples/'])
            ->setFormat('stylish');
    }

    /**
     * @return \Sweetchuck\Robo\ESLint\Task\ESLintRunFiles|\Robo\Collection\CollectionBuilder
     */
    public function lintStylishFile()
    {
        return $this->taskESLintRunFiles()
            ->setFiles(['samples/'])
            ->setFormat('stylish')
            ->setOutputFile("{$this->reportsDir}/native.stylish.txt");
    }

    /**
     * @return \Sweetchuck\Robo\ESLint\Task\ESLintRunFiles|\Robo\Collection\CollectionBuilder
     */
    public function lintAllInOne()
    {
        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.summary.txt");

        return $this->taskESLintRunFiles()
            ->setFiles(['samples/'])
            ->setFormat('json')
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile);
    }

    /**
     * @return \Sweetchuck\Robo\ESLint\Task\ESLintRunInput|\Robo\Collection\CollectionBuilder
     */
    public function lintInput(
        $options = [
            'command-only' => false,
        ]
    ) {
        $fixturesDir = 'samples';
        $reportsDir = 'actual';

        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$reportsDir/extra.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$reportsDir/extra.summary.txt");

        $files = [
            'invalid-01.js' => [
                'fileName' => "$fixturesDir/invalid-01.js",
                'command' => "cat $fixturesDir/invalid-01.js",
                'content' => null,
            ],
        ];

        if (!$options['command-only']) {
            $files['invalid-01.js']['content'] = file_get_contents($files['invalid-01.js']['fileName']);
        }

        return $this->taskESLintRunInput()
            ->setFormat('json')
            ->setFiles($files)
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile);
    }
}
