<?php

use Cheppers\LintReport\Reporter\BaseReporter;
use Cheppers\LintReport\Reporter\SummaryReporter;
use Cheppers\LintReport\Reporter\VerboseReporter;
use League\Container\ContainerInterface;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class RoboFile.
 */
// @codingStandardsIgnoreStart
class RoboFile extends \Robo\Tasks implements ConfigAwareInterface
{
    // @codingStandardsIgnoreEnd
    use \Cheppers\Robo\ESLint\Task\LoadTasks;
    use \Robo\Common\ConfigAwareTrait;

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
     * @return \Cheppers\Robo\ESLint\Task\Run
     */
    public function lintStylishStdOutput()
    {
        return $this->taskESLintRun()
            ->paths(['samples/'])
            ->format('stylish');
    }

    /**
     * @return \Cheppers\Robo\ESLint\Task\Run
     */
    public function lintStylishFile()
    {
        return $this->taskESLintRun()
            ->paths(['samples/'])
            ->format('stylish')
            ->outputFile("{$this->reportsDir}/native.stylish.txt");
    }

    /**
     * @return \Cheppers\Robo\ESLint\Task\Run
     */
    public function lintAllInOne()
    {
        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.summary.txt");

        return $this->taskESLintRun()
            ->paths(['samples/'])
            ->format('json')
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile);
    }
}
