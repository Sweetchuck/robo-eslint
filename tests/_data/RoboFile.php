<?php

use Cheppers\AssetJar\AssetJar;
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
    use \Cheppers\Robo\ESLint\LoadESLintTasks;
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
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunFiles
     */
    public function lintStylishStdOutput()
    {
        return $this->taskESLintRunFiles()
            ->setFiles(['samples/'])
            ->setFormat('stylish');
    }

    /**
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunFiles
     */
    public function lintStylishFile()
    {
        return $this->taskESLintRunFiles()
            ->setFiles(['samples/'])
            ->setFormat('stylish')
            ->setOutputFile("{$this->reportsDir}/native.stylish.txt");
    }

    /**
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunFiles
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
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunInput
     */
    public function lintInputWithoutJar(
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

    /**
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunInput
     */
    public function lintInputWithJar(
        $options = [
            'command-only' => false,
        ]
    ) {
        $task = $this->lintInputWithoutJar($options);
        $assetJar = new AssetJar([
            'l1' => [
                'l2' => $task->getFiles(),
            ],
        ]);

        return $task
            ->setFiles([])
            ->setAssetJar($assetJar)
            ->setAssetJarMap('files', ['l1', 'l2']);
    }
}
