<?php

namespace Cheppers\Robo\ESLint\Task;

use Cheppers\AssetJar\AssetJarAware;
use Cheppers\AssetJar\AssetJarAwareInterface;
use Cheppers\Robo\ESLint\LintReportWrapper\ReportWrapper;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\BuilderAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Process\Process;

/**
 * Class Run.
 *
 * Assert mapping:
 *   - report: Parsed JSON lint report.
 *
 * @package Cheppers\Robo\ESLint\Task
 */
class Run extends BaseTask implements
    AssetJarAwareInterface,
    ContainerAwareInterface,
    BuilderAwareInterface,
    OutputAwareInterface
{

    use AssetJarAware;
    use ContainerAwareTrait;
    use BuilderAwareTrait;
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

    /**
     * @todo Some kind of dependency injection would be awesome.
     *
     * @var string
     */
    protected $processClass = Process::class;

    /**
     * @var string
     */
    protected $eslintExecutable = 'node_modules/.bin/eslint';

    /**
     * Directory to step in before run the `eslint`.
     *
     * @var string
     */
    protected $workingDirectory = '';

    /**
     * Severity level.
     *
     * @var bool
     */
    protected $failOn = 'error';

    /**
     * @var \Cheppers\LintReport\ReporterInterface[]
     */
    protected $lintReporters = [];

    /**
     * The location of the configuration file.
     *
     * @var string
     */
    protected $configFile = null;

    /**
     * @var bool
     */
    protected $noESLintRc = false;

    /**
     * @todo
     *
     * @var mixed
     */
    protected $env = null;

    /**
     * Specify JavaScript file extensions.
     *
     * @var string
     */
    protected $ext = '';

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
     * Only check changed files - default: false.
     *
     * @var bool
     */
    protected $cache = false;

    /**
     * Path to the cache file or directory.
     *
     * @var string
     */
    protected $cacheLocation = '';

    /**
     *  An additional rules directory, for user-created rules.
     *
     * @var string
     */
    protected $rulesDir = '';

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

    /**
     * @var string
     */
    protected $ignorePath = '';

    /**
     * @var bool
     */
    protected $noIgnore = false;

    /**
     * @var bool
     */
    protected $ignorePattern = false;

    /**
     * @var bool
     */
    protected $quiet = false;

    /**
     * @var int|null
     */
    protected $maxWarnings = null;

    /**
     * @var string
     */
    protected $outputFile = '';

    /**
     * @var string
     */
    protected $format = '';

    /**
     * @var bool|null
     */
    protected $color = null;

    /**
     * @var bool
     */
    protected $noInlineConfig = false;

    /**
     * TypeScript files to check.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Process exit code.
     *
     * @var int
     */
    protected $exitCode = 0;

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
     * TaskTsLintRun constructor.
     *
     * @param array $options
     *   Key-value pairs of options.
     * @param array $paths
     *   File paths.
     */
    public function __construct(array $options = [], array $paths = [])
    {
        $this->options($options);
        $this->paths($paths);
    }

    /**
     * All in one configuration.
     *
     * @param array $options
     *   Options.
     *
     * @return $this
     */
    public function options(array $options)
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'eslintExecutable':
                    $this->eslintExecutable($value);
                    break;

                case 'assetJarMapping':
                    $this->setAssetJarMapping($value);
                    break;

                case 'workingDirectory':
                    $this->workingDirectory($value);
                    break;

                case 'failOn':
                    $this->failOn($value);
                    break;

                case 'lintReporters':
                    $this->setLintReporters($value);
                    break;

                case 'configFile':
                    $this->configFile($value);
                    break;

                case 'noESLintRc':
                    $this->noESLintRc($value);
                    break;

                case 'ext':
                    $this->ext($value);
                    break;

                case 'cache':
                    $this->cache($value);
                    break;

                case 'cacheLocation':
                    $this->cacheLocation($value);
                    break;

                case 'rulesDir':
                    $this->rulesDir($value);
                    break;

                case 'ignorePath':
                    $this->ignorePath($value);
                    break;

                case 'noIgnore':
                    $this->noIgnore($value);
                    break;

                case 'ignorePattern':
                    $this->ignorePattern($value);
                    break;

                case 'quiet':
                    $this->quiet($value);
                    break;

                case 'maxWarnings':
                    $this->maxWarnings($value);
                    break;

                case 'outputFile':
                    $this->outputFile($value);
                    break;

                case 'format':
                    $this->format($value);
                    break;

                case 'color':
                    $this->color($value);
                    break;

                case 'noInlineConfig':
                    $this->noInlineConfig($value);
                    break;

                case 'paths':
                    $this->paths($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * Set path to the "eslint" executable.
     *
     * @param string $value
     *   Path to the "eslint" executable.
     *
     * @return $this
     */
    public function eslintExecutable($value)
    {
        $this->eslintExecutable = $value;

        return $this;
    }

    /**
     * Set the current working directory.
     *
     * @param string $value
     *   Directory path.
     *
     * @return $this
     */
    public function workingDirectory($value)
    {
        $this->workingDirectory = $value;

        return $this;
    }

    /**
     * Fail if there is a lint with warning severity.
     *
     * @param string $value
     *   Allowed values are: never, warning, error.
     *
     * @return $this
     */
    public function failOn($value)
    {
        $this->failOn = $value;

        return $this;
    }

    /**
     * @return \Cheppers\LintReport\ReporterInterface[]
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
     * @param string|\Cheppers\LintReport\ReporterInterface $lintReporter
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

    /**
     * Specify which configuration file you want to use.
     *
     * @param string $path
     *   File path.
     *
     * @return $this
     */
    public function configFile($path)
    {
        $this->configFile = $path;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function noESLintRc($value)
    {
        $this->noESLintRc = $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function ext($value)
    {
        $this->ext = $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function cache($value)
    {
        $this->cache = $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function cacheLocation($value)
    {
        $this->cacheLocation = $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function rulesDir($value)
    {
        $this->rulesDir = $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function ignorePath($value)
    {
        $this->ignorePath = $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function noIgnore($value)
    {
        $this->noIgnore = $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function ignorePattern($value)
    {
        $this->ignorePattern = $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function quiet($value)
    {
        $this->quiet = $value;

        return $this;
    }

    /**
     * @param int|null $value
     *
     * @return $this
     */
    public function maxWarnings($value)
    {
        $this->maxWarnings = $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function outputFile($value)
    {
        $this->outputFile = $value;

        return $this;
    }

    /**
     * Specify how to display lints.
     *
     * @param string $value
     *   Formatter identifier.
     *
     * @return $this
     */
    public function format($value)
    {
        $this->format = $value;

        return $this;
    }

    /**
     * @param bool|null $value
     *
     * @return $this
     */
    public function color($value)
    {
        $this->color = $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function noInlineConfig($value)
    {
        $this->noInlineConfig = $value;

        return $this;
    }

    /**
     * File paths to lint.
     *
     * @param string|string[]|bool[] $paths
     *   Key-value pair of file names and boolean.
     * @param bool $include
     *   Exclude or include the files in $paths.
     *
     * @return $this
     */
    public function paths($paths, $include = true)
    {
        $this->paths = $this->createIncludeList($paths, $include) + $this->paths;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $command = $this->buildCommand();

        $this->printTaskInfo(sprintf('ESLint task runs: <info>%s</info>', $command));

        $lintReporters = $this->initLintReporters();
        if ($lintReporters && $this->format !== 'json') {
            return new Result(
                $this,
                static::EXIT_CODE_INVALID,
                'Extra lint reporters can be used only if the output format is "json".'
            );
        }

        /** @var Process $process */
        $process = new $this->processClass($command);
        if ($this->workingDirectory) {
            $process->setWorkingDirectory($this->workingDirectory);
        }

        $this->startTimer();
        $this->exitCode = $process->run();
        $this->stopTimer();

        $numOfErrors = $this->exitCode;
        $numOfWarnings = 0;
        if ($this->isLintSuccess()) {
            $originalOutput = $process->getOutput();
            if ($this->format === 'json') {
                $jsonOutput = ($this->outputFile ? file_get_contents($this->outputFile) : $originalOutput);
                $reportWrapper = new ReportWrapper(json_decode($jsonOutput, true));

                $numOfErrors = $reportWrapper->numOfErrors();
                $numOfWarnings = $reportWrapper->numOfWarnings();

                if ($this->isReportHasToBePutBackIntoJar()) {
                    $this->setAssetJarValue('report', $reportWrapper);
                }

                foreach ($lintReporters as $lintReporter) {
                    $lintReporter
                        ->setReportWrapper($reportWrapper)
                        ->generate();
                }
            }

            if (!$lintReporters) {
                $this->output()->write($originalOutput);
            }
        }

        $exitCode = $this->getTaskExitCode($numOfErrors, $numOfWarnings);

        return new Result(
            $this,
            $exitCode,
            $this->getExitMessage($exitCode) ?: $process->getErrorOutput(),
            [
                'time' => $this->getExecutionTime(),
            ]
        );
    }

    /**
     * Build the CLI command based on the configuration.
     *
     * @return string
     *   CLI command to execute.
     */
    public function buildCommand()
    {
        $cmd_pattern = '%s';
        $cmd_args = [
            escapeshellcmd($this->eslintExecutable),
        ];

        if ($this->configFile) {
            $cmd_pattern .= ' --config %s';
            $cmd_args[] = escapeshellarg($this->configFile);
        }

        if ($this->noESLintRc) {
            $cmd_pattern .= ' --no-eslintrc';
        }

        if ($this->ext) {
            $cmd_pattern .= ' --ext %s';
            $cmd_args[] = escapeshellarg($this->ext);
        }

        if ($this->cache) {
            $cmd_pattern .= ' --cache';
        }

        if ($this->cacheLocation) {
            $cmd_pattern .= ' --cache-location %s';
            $cmd_args[] = escapeshellarg($this->cacheLocation);
        }

        if ($this->rulesDir) {
            $cmd_pattern .= ' --rulesdir %s';
            $cmd_args[] = escapeshellarg($this->rulesDir);
        }

        if ($this->ignorePath) {
            $cmd_pattern .= ' --ignore-path %s';
            $cmd_args[] = escapeshellarg($this->ignorePath);
        }

        if ($this->noIgnore) {
            $cmd_pattern .= ' --no-ignore';
        }

        if ($this->ignorePattern) {
            $cmd_pattern .= ' --ignore-pattern %s';
            $cmd_args[] = escapeshellarg($this->ignorePattern);
        }

        if ($this->quiet) {
            $cmd_pattern .= ' --quiet';
        }

        if ($this->maxWarnings !== null && $this->maxWarnings !== false) {
            $cmd_pattern .= ' --max-warnings %s';
            $cmd_args[] = escapeshellarg($this->maxWarnings);
        }

        if ($this->outputFile) {
            $cmd_pattern .= ' --output-file %s';
            $cmd_args[] = escapeshellarg($this->outputFile);
        }

        if ($this->format) {
            $cmd_pattern .= ' --format %s';
            $cmd_args[] = escapeshellarg($this->format);
        }

        if ($this->color) {
            $cmd_pattern .= ' --color';
        } elseif ($this->color !== null) {
            $cmd_pattern .= ' --no-color';
        }

        if ($this->noInlineConfig) {
            $cmd_pattern .= ' --no-inline-config';
        }

        $paths = array_keys($this->paths, true, true);
        if ($paths) {
            $cmd_pattern .= ' --' . str_repeat(' %s', count($paths));
            foreach ($paths as $path) {
                $cmd_args[] = escapeshellarg($path);
            }
        }

        return vsprintf($cmd_pattern, $cmd_args);
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
            switch ($this->failOn) {
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

        return $this->exitCode;
    }

    /**
     * @return bool
     */
    protected function isReportHasToBePutBackIntoJar()
    {
        return (
            $this->hasAssetJar()
            && $this->getAssetJarMap('report')
            && $this->isLintSuccess()
        );
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
        return in_array($this->exitCode, $this->lintSuccessExitCodes());
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
     * @return \Cheppers\LintReport\ReporterInterface[]
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

            if ($lintReporter instanceof \Cheppers\LintReport\ReporterInterface) {
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

    /**
     * The array key is the relevant value and the array value will be a boolean.
     *
     * @param string|string[]|bool[] $items
     *   Items.
     * @param bool $include
     *   Default value.
     *
     * @return bool[]
     *   Key is the relevant value, the value is a boolean.
     */
    protected function createIncludeList($items, $include)
    {
        if (!is_array($items)) {
            $items = [$items => $include];
        }

        $item = reset($items);
        if (gettype($item) !== 'boolean') {
            $items = array_fill_keys($items, $include);
        }

        return $items;
    }
}
