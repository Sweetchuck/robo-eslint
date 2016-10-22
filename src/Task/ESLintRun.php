<?php

namespace Cheppers\Robo\ESLint\Task;

use Cheppers\AssetJar\AssetJarAware;
use Cheppers\AssetJar\AssetJarAwareInterface;
use Cheppers\LintReport\ReporterInterface;
use Cheppers\Robo\ESLint\LintReportWrapper\ReportWrapper;
use Cheppers\Robo\ESLint\Utils;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Process\Process;

/**
 * @package Cheppers\Robo\ESLint\Task
 */
abstract class ESLintRun extends BaseTask implements
    AssetJarAwareInterface,
    ContainerAwareInterface,
    OutputAwareInterface
{

    use AssetJarAware;
    use ContainerAwareTrait;
    use IO;

    /**
     * Exit code: No lints were found.
     */
    const EXIT_CODE_OK = 0;

    /**
     * One or more warnings were reported (and no errors).
     */
    const EXIT_CODE_WARNING = 1;

    /**
     * One or more errors were reported (and any number of warnings).
     */
    const EXIT_CODE_ERROR = 2;

    const EXIT_CODE_INVALID = 3;

    const EXIT_CODE_UNKNOWN = 4;

    /**
     * @todo Some kind of dependency injection would be awesome.
     *
     * @var string
     */
    protected $processClass = Process::class;

    /**
     * @var \Robo\Result|null
     */
    protected $taskResult = null;

    /**
     * @var int
     */
    protected $lintExitCode = 0;

    /**
     * @var string
     */
    protected $lintStdOutput = '';

    /**
     * @var bool
     */
    protected $isLintStdOutputPublic = true;

    /**
     * @var string
     */
    protected $reportRaw = '';

    /**
     * Exit code and error message mapping.
     *
     * @var string
     */
    protected $exitMessages = [
        0 => 'No lints were found',
        1 => 'One or more warnings were reported (and no errors)',
        2 => 'One or more errors were reported (and any number of warnings)',
    ];

    /**
     * @var bool
     */
    protected $addFilesToCliCommand = true;

    /**
     * @var string
     */
    protected $lintOutput = '';

    /**
     * @var array
     */
    protected $report = [];

    /**
     * @var \Cheppers\LintReport\ReportWrapperInterface
     */
    protected $reportWrapper = null;

    protected $triStateOptions = [
        'color' => 'color',
    ];

    protected $flagOptions = [
        'cache' => 'cache',
        'noESLintRc' => 'no-eslintrc',
        'noIgnore' => 'no-ignore',
        'noInlineConfig' => 'no-inline-config',
        'quiet' => 'quiet',
    ];

    protected $simpleOptions = [
        'cacheLocation' => 'cache-location',
        'configFile' => 'config',
        'ext' => 'ext',
        'format' => 'format',
        'ignorePath' => 'ignore-path',
        'ignorePattern' => 'ignore-pattern',
        'maxWarnings' => 'max-warnings',
        'outputFile' => 'output-file',
        'rulesDir' => 'rulesdir',
    ];

    protected $listOptions = [];

    //region Options - Not supported
    /**
     * @todo
     *
     * @var mixed
     */
    protected $env = null;

    /**
     * @todo
     *
     * @var mixed
     */
    protected $global = null;

    /**
     * @todo
     *
     * @var mixed
     */
    protected $parser = null;

    /**
     * @todo
     *
     * @var mixed
     */
    protected $parserOptions = null;

    /**
     * @todo
     *
     * @var mixed
     */
    protected $plugin = null;

    /**
     * @todo
     *
     * @var mixed
     */
    protected $rule = null;
    //endregion

    //region Options

    //region Option - cache
    /**
     * Only check changed files - default: false.
     *
     * @var bool
     */
    protected $cache = false;

    /**
     * @return bool
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setCache($value)
    {
        $this->cache = $value;

        return $this;
    }
    //endregion

    //region Option - cacheLocation
    /**
     * Path to the cache file or directory.
     *
     * @var string
     */
    protected $cacheLocation = '';

    /**
     * @return string
     */
    public function getCacheLocation()
    {
        return $this->cacheLocation;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setCacheLocation($value)
    {
        $this->cacheLocation = $value;

        return $this;
    }
    //endregion

    //region Option - color
    /**
     * @var bool|null
     */
    protected $color = null;

    /**
     * @return bool|null
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param bool|null $value
     *
     * @return $this
     */
    public function setColor($value)
    {
        $this->color = $value;

        return $this;
    }
    //endregion

    //region Option - configFile
    /**
     * The location of the configuration file.
     *
     * @var string
     */
    protected $configFile = null;

    /**
     * @return string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Specify which configuration file you want to use.
     *
     * @param string $path
     *   File path.
     *
     * @return $this
     */
    public function setConfigFile($path)
    {
        $this->configFile = $path;

        return $this;
    }
    //endregion

    //region Option - eslintExecutable
    /**
     * @var string
     */
    protected $eslintExecutable = 'node_modules/.bin/eslint';

    /**
     * @return string
     */
    public function getEslintExecutable()
    {
        return $this->eslintExecutable;
    }

    /**
     * Set path to the "eslint" executable.
     *
     * @param string $value
     *   Path to the "eslint" executable.
     *
     * @return $this
     */
    public function setEslintExecutable($value)
    {
        $this->eslintExecutable = $value;

        return $this;
    }
    //endregion

    //region Option - ext
    /**
     * Specify JavaScript file extensions.
     *
     * @var string
     */
    protected $ext = '';

    /**
     * @return string
     */
    public function getExt()
    {
        return $this->ext;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setExt($value)
    {
        $this->ext = $value;

        return $this;
    }
    //endregion

    //region Option - failOn
    /**
     * Severity level.
     *
     * @var string
     */
    protected $failOn = 'error';

    /**
     * @return string
     */
    public function getFailOn()
    {
        return $this->failOn;
    }

    /**
     * Fail if there is a lint with warning severity.
     *
     * @param string $value
     *   Allowed values are: never, warning, error.
     *
     * @return $this
     */
    public function setFailOn($value)
    {
        $this->failOn = $value;

        return $this;
    }
    //endregion

    //region Option - format
    /**
     * @var string
     */
    protected $format = '';

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Specify how to display lints.
     *
     * @param string $value
     *   Formatter identifier.
     *
     * @return $this
     */
    public function setFormat($value)
    {
        $this->format = $value;

        return $this;
    }
    //endregion

    //region Option - ignorePath
    /**
     * @var string
     */
    protected $ignorePath = '';

    /**
     * @return string
     */
    public function getIgnorePath()
    {
        return $this->ignorePath;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setIgnorePath($value)
    {
        $this->ignorePath = $value;

        return $this;
    }
    //endregion

    //region Option - ignorePattern
    /**
     * @var string|null
     */
    protected $ignorePattern = null;

    /**
     *
     *
     * @return bool
     */
    public function getIgnorePattern()
    {
        return $this->ignorePattern;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setIgnorePattern($value)
    {
        $this->ignorePattern = $value;

        return $this;
    }
    //endregion

    //region Option - lintReporters
    /**
     * @var ReporterInterface[]
     */
    protected $lintReporters = [];

    /**
     * @return ReporterInterface[]
     */
    public function getLintReporters()
    {
        return $this->lintReporters;
    }

    /**
     * @param array $lintReporters
     *
     * @return $this
     */
    public function setLintReporters(array $lintReporters)
    {
        $this->lintReporters = $lintReporters;

        return $this;
    }

    /**
     * @param string $id
     * @param string|ReporterInterface $lintReporter
     *
     * @return $this
     */
    public function addLintReporter($id, $lintReporter = null)
    {
        $this->lintReporters[$id] = $lintReporter;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function removeLintReporter($id)
    {
        unset($this->lintReporters[$id]);

        return $this;
    }
    //endregion

    //region Option - maxWarnings
    /**
     * @var int|null
     */
    protected $maxWarnings = null;

    /**
     * @return int|null
     */
    public function getMaxWarnings()
    {
        return $this->maxWarnings;
    }

    /**
     * @param int|null $value
     *
     * @return $this
     */
    public function setMaxWarnings($value)
    {
        $this->maxWarnings = $value;

        return $this;
    }
    //endregion

    //region Option - noEslintRc
    /**
     * @var bool
     */
    protected $noESLintRc = false;

    /**
     * @return bool
     */
    public function isEslintRcDisabled()
    {
        return $this->noESLintRc;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setNoESLintRc($value)
    {
        $this->noESLintRc = $value;

        return $this;
    }
    //endregion

    //region Option - noIgnore
    /**
     * @var bool
     */
    protected $noIgnore = false;

    /**
     * @return bool
     */
    public function getNoIgnore()
    {
        return $this->noIgnore;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setNoIgnore($value)
    {
        $this->noIgnore = $value;

        return $this;
    }
    //endregion

    //region Option - noInlineConfig
    /**
     * @var bool
     */
    protected $noInlineConfig = false;

    /**
     * @return bool
     */
    public function getNoInlineConfig()
    {
        return $this->noInlineConfig;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setNoInlineConfig($value)
    {
        $this->noInlineConfig = $value;

        return $this;
    }
    //endregion

    //region Option - files
    /**
     * Files to check.
     *
     * @var array
     */
    protected $files = [];

    /**
     * @return array
     */
    public function getFiles()
    {
        return (array) $this->files;
    }

    /**
     * File files to lint.
     *
     * @param string|string[]|bool[] $files
     *   Key-value pair of file names and boolean.
     * @param bool $include
     *   Exclude or include the files in $files.
     *
     * @return $this
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }
    //endregion

    //region Option - outputFile
    /**
     * @var string
     */
    protected $outputFile = '';

    /**
     * @return string
     */
    public function getOutputFile()
    {
        return $this->outputFile;
    }

    /**
     * @return string
     */
    public function getRealOutputFile()
    {
        $outputFile = $this->getOutputFile();
        if (!$outputFile) {
            return '';
        }

        if (Utils::isAbsolutePath($outputFile)) {
            return $outputFile;
        }

        $wd = $this->getWorkingDirectory() ?: '.';

        return "$wd/$outputFile";
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setOutputFile($value)
    {
        $this->outputFile = $value;

        return $this;
    }
    //endregion

    //region Option - quiet
    /**
     * @var bool
     */
    protected $quiet = false;

    /**
     * @return bool
     */
    public function isQuiet()
    {
        return $this->quiet;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setQuiet($value)
    {
        $this->quiet = $value;

        return $this;
    }
    //endregion

    //region Option - rulesDir
    /**
     *  An additional rules directory, for user-created rules.
     *
     * @var string
     */
    protected $rulesDir = '';

    /**
     * @return string
     */
    public function getRulesDir()
    {
        return $this->rulesDir;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setRulesDir($value)
    {
        $this->rulesDir = $value;

        return $this;
    }
    //endregion

    //region Option - workingDirectory
    /**
     * Directory to step in before run the `eslint`.
     *
     * @var string
     */
    protected $workingDirectory = '';

    /**
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }

    /**
     * Set the current working directory.
     *
     * @param string $value
     *   Directory path.
     *
     * @return $this
     */
    public function setWorkingDirectory($value)
    {
        $this->workingDirectory = $value;

        return $this;
    }
    //endregion

    //endregion

    /**
     * @param array $options
     *   Key-value pairs of options.
     * @param array $files
     *   File files.
     */
    public function __construct(array $options = [], array $files = [])
    {
        $this->setOptions($options);
        if ($files) {
            $this->setFiles($files);
        }
    }

    /**
     * All in one configuration.
     *
     * @param array $options
     *   Options.
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'eslintExecutable':
                    $this->setEslintExecutable($value);
                    break;

                case 'assetJar':
                    $this->setAssetJar($value);
                    break;

                case 'assetJarMapping':
                    $this->setAssetJarMapping($value);
                    break;

                case 'workingDirectory':
                    $this->setWorkingDirectory($value);
                    break;

                case 'failOn':
                    $this->setFailOn($value);
                    break;

                case 'lintReporters':
                    $this->setLintReporters($value);
                    break;

                case 'configFile':
                    $this->setConfigFile($value);
                    break;

                case 'noESLintRc':
                    $this->setNoESLintRc($value);
                    break;

                case 'ext':
                    $this->setExt($value);
                    break;

                case 'cache':
                    $this->setCache($value);
                    break;

                case 'cacheLocation':
                    $this->setCacheLocation($value);
                    break;

                case 'rulesDir':
                    $this->setRulesDir($value);
                    break;

                case 'ignorePath':
                    $this->setIgnorePath($value);
                    break;

                case 'noIgnore':
                    $this->setNoIgnore($value);
                    break;

                case 'ignorePattern':
                    $this->setIgnorePattern($value);
                    break;

                case 'quiet':
                    $this->setQuiet($value);
                    break;

                case 'maxWarnings':
                    $this->setMaxWarnings($value);
                    break;

                case 'outputFile':
                    $this->setOutputFile($value);
                    break;

                case 'format':
                    $this->setFormat($value);
                    break;

                case 'color':
                    $this->setColor($value);
                    break;

                case 'noInlineConfig':
                    $this->setNoInlineConfig($value);
                    break;

                case 'files':
                    $this->setFiles($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        return $this
            ->runHeader()
            ->runLint()
            ->runReleaseLintReports()
            ->runReleaseAssets()
            ->runReturn();
    }

    /**
     * @return $this
     */
    protected function runHeader()
    {
        $this->printTaskInfo(
            'ESLint task runs: <info>{command}</info>',
            [
                'command' => $this->getCommand(),
            ]
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runLint()
    {
        $this->reportRaw = '';
        $this->isLintStdOutputPublic = true;
        $this->report = [];
        $this->reportWrapper = null;
        $this->lintExitCode = static::EXIT_CODE_OK;

        $command = $this->getCommand();

        /** @var \Symfony\Component\Process\Process $process */
        $process = new $this->processClass($command);
        if ($this->workingDirectory) {
            $process->setWorkingDirectory($this->workingDirectory);
        }

        $this->lintExitCode = $process->run();
        $this->lintStdOutput = $process->getOutput();

        if ($this->isLintSuccess()) {
            if ($this->getFormat() === 'json') {
                $outputFile = $this->getRealOutputFile();
                if ($outputFile) {
                    if (is_readable($outputFile)) {
                        $this->reportRaw = file_get_contents($outputFile);
                    }
                } else {
                    $this->reportRaw = $this->lintStdOutput;
                }
            } else {
                $backupFormat = $this->getFormat();
                $backupOutputFile = $this->getOutputFile();

                $this->setFormat('json');
                $this->setOutputFile(null);
                $process = new $this->processClass($this->getCommand());
                $process->run();
                $this->reportRaw = $process->getOutput();

                $this->setFormat($backupFormat);
                $this->setOutputFile($backupOutputFile);
            }
        }

        if ($this->reportRaw) {
            // @todo Pray for a valid JSON output.
            $this->report = (array) json_decode($this->reportRaw, true);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function runReleaseLintReports()
    {
        if (!$this->isLintSuccess()) {
            // @todo Print the StdError as well.
            $this->output()->write($this->lintStdOutput);

            return $this;
        }

        $lintReporters = $this->initLintReporters();
        if (!($this->getFormat() === 'json' && $lintReporters)) {
            $this->output()->write($this->lintStdOutput);
        }

        $this->reportWrapper = new ReportWrapper($this->report);
        foreach ($this->initLintReporters() as $lintReporter) {
            $lintReporter
                ->setReportWrapper($this->reportWrapper)
                ->generate();
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function runReleaseAssets()
    {
        if ($this->hasAssetJar() && $this->isLintSuccess()) {
            if ($this->getAssetJarMap('report')) {
                $this->setAssetJarValue('report', $this->reportWrapper);
            }

            if ($this->getAssetJarMap('workingDirectory')) {
                $this->setAssetJarValue('workingDirectory', $this->getWorkingDirectory());
            }
        }

        return $this;
    }

    /**
     * @return \Robo\Result
     */
    protected function runReturn()
    {
        if ($this->taskResult) {
            return $this->taskResult;
        }

        if ($this->lintExitCode && !$this->reportRaw) {
            $exitCode = static::EXIT_CODE_UNKNOWN;
        } else {
            $exitCode = $this->getTaskExitCode(
                $this->reportWrapper->numOfErrors(),
                $this->reportWrapper->numOfWarnings()
            );
        }

        return new Result(
            $this,
            $exitCode,
            $this->getExitMessage($exitCode),
            [
                'report' => $this->reportWrapper,
                'workingDirectory' => $this->getWorkingDirectory(),
            ]
        );
    }

    /**
     * Build the CLI command based on the configuration.
     *
     * @return string
     *   CLI command to execute.
     */
    public function getCommand()
    {
        $cmdPattern = '%s';
        $cmdArgs = [
            escapeshellcmd($this->getEslintExecutable()),
        ];

        $options = $this->buildCommandOptions();

        foreach ($this->triStateOptions as $optionName => $optionCli) {
            if (isset($options[$optionName])) {
                $cmdPattern .= $options[$optionName] ? " --{$optionCli}" : " --no-{$optionCli}";
            }
        }

        foreach ($this->flagOptions as $optionName => $optionCli) {
            if (!empty($options[$optionName])) {
                $cmdPattern .= " --{$optionCli}";
            }
        }

        foreach ($this->simpleOptions as $optionName => $optionCli) {
            if (isset($options[$optionName])
                && ($options[$optionName] === 0 || $options[$optionName] === '0' || $options[$optionName])
            ) {
                $cmdPattern .= " --{$optionCli} %s";
                $cmdArgs[] = escapeshellarg($options[$optionName]);
            }
        }

        foreach ($this->listOptions as $optionName => $optionCli) {
            if (!empty($options[$optionName])) {
                $items = $this->filterEnabled($options[$optionName]);
                if ($items) {
                    $cmdPattern .= " --{$optionCli} %s";
                    $cmdArgs[] = escapeshellarg(implode(',', $items));
                }
            }
        }

        if ($this->addFilesToCliCommand) {
            $files = $this->filterEnabled($this->getFiles());
            if ($files) {
                $cmdPattern .= ' --' . str_repeat(' %s', count($files));
                foreach ($files as $file) {
                    $cmdArgs[] = escapeshellarg($file);
                }
            }
        }

        return vsprintf($cmdPattern, $cmdArgs);
    }

    protected function buildCommandOptions()
    {
        return [
            'configFile' => $this->getConfigFile(),
            'noESLintRc' => $this->isEslintRcDisabled(),
            'ext' => $this->getExt(),
            'cache' => $this->getCache(),
            'cacheLocation' => $this->getCacheLocation(),
            'rulesDir' => $this->getRulesDir(),
            'ignorePath' => $this->getIgnorePath(),
            'noIgnore' => $this->getNoIgnore(),
            'ignorePattern' => $this->getIgnorePattern(),
            'quiet' => $this->isQuiet(),
            'maxWarnings' => $this->getMaxWarnings(),
            'outputFile' => $this->getOutputFile(),
            'format' => $this->getFormat(),
            'color' => $this->getColor(),
            'noInlineConfig' => $this->getNoInlineConfig(),
        ];
    }

    /**
     * Get the exit code regarding the failOn settings.
     *
     * @param int $numOfErrors
     * @param int $numOfWarnings
     *
     * @return int
     *   Exit code.
     */
    protected function getTaskExitCode($numOfErrors, $numOfWarnings)
    {
        if ($this->isLintSuccess()) {
            switch ($this->getFailOn()) {
                case 'never':
                    return static::EXIT_CODE_OK;

                case 'warning':
                    if ($numOfErrors) {
                        return static::EXIT_CODE_ERROR;
                    }

                    return $numOfWarnings ? static::EXIT_CODE_WARNING : static::EXIT_CODE_OK;

                case 'error':
                    return $numOfErrors ? static::EXIT_CODE_ERROR : static::EXIT_CODE_OK;
            }
        }

        return $this->lintExitCode;
    }

    /**
     * @param array $items
     *
     * @return array
     */
    protected function filterEnabled(array $items)
    {
        return gettype(reset($items)) === 'boolean' ? array_keys($items, true, true) : $items;
    }

    /**
     * Returns true if the lint ran successfully.
     *
     * Returns true even if there was any code style error or warning.
     *
     * @return bool
     */
    protected function isLintSuccess()
    {
        return in_array($this->lintExitCode, $this->lintSuccessExitCodes());
    }

    /**
     * @return int[]
     */
    protected function lintSuccessExitCodes()
    {
        return [
            static::EXIT_CODE_OK,
            static::EXIT_CODE_WARNING,
            static::EXIT_CODE_ERROR,
        ];
    }

    /**
     * @param int $exitCode
     *
     * @return string|false
     */
    protected function getExitMessage($exitCode)
    {
        if (isset($this->exitMessages[$exitCode])) {
            return $this->exitMessages[$exitCode];
        }

        return false;
    }

    /**
     * @return ReporterInterface[]
     */
    protected function initLintReporters()
    {
        $lintReporters = [];
        $c = $this->getContainer();
        foreach ($this->getLintReporters() as $id => $lintReporter) {
            if ($lintReporter === false) {
                continue;
            }

            if (!$lintReporter) {
                $lintReporter = $c->get($id);
            } elseif (is_string($lintReporter)) {
                $lintReporter = $c->get($lintReporter);
            }

            if ($lintReporter instanceof ReporterInterface) {
                $lintReporters[$id] = $lintReporter;
                if (!$lintReporter->getDestination()) {
                    $lintReporter
                        ->setFilePathStyle('relative')
                        ->setDestination($this->output());
                }
            }
        }

        return $lintReporters;
    }
}
