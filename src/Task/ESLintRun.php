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
    protected $machineReadableFormat = 'json';

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
        'format' => 'format',
        'ignorePath' => 'ignore-path',
        'ignorePattern' => 'ignore-pattern',
        'maxWarnings' => 'max-warnings',
        'outputFile' => 'output-file',
    ];

    protected $listOptions = [
        'ext' => 'ext',
    ];

    protected $multiOptions = [
        'rulesDir' => 'rulesdir',
    ];

    //region Options - Not supported.
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

    //region Options.

    //region Option - cache.
    /**
     * Only check changed files - default: false.
     *
     * @var bool
     */
    protected $cache = false;

    public function getCache(): bool
    {
        return $this->cache;
    }

    /**
     * @return $this
     */
    public function setCache(bool $value)
    {
        $this->cache = $value;

        return $this;
    }
    //endregion

    //region Option - cacheLocation.
    /**
     * Path to the cache file or directory.
     *
     * @var string
     */
    protected $cacheLocation = '';

    public function getCacheLocation(): string
    {
        return $this->cacheLocation;
    }

    /**
     * @return $this
     */
    public function setCacheLocation(string $value)
    {
        $this->cacheLocation = $value;

        return $this;
    }
    //endregion

    //region Option - color.
    /**
     * @var bool|null
     */
    protected $color = null;

    /**
     * @return bool|null
     */
    public function getColor(): ?bool
    {
        return $this->color;
    }

    /**
     * @return $this
     */
    public function setColor(?bool $value)
    {
        $this->color = $value;

        return $this;
    }
    //endregion

    //region Option - configFile.
    /**
     * @var string
     */
    protected $configFile = '';

    /**
     * The location of the configuration file.
     */
    public function getConfigFile(): string
    {
        return $this->configFile;
    }

    /**
     * Specify which configuration file you want to use.
     *
     * @return $this
     */
    public function setConfigFile(string $path)
    {
        $this->configFile = $path;

        return $this;
    }
    //endregion

    //region Option - eslintExecutable.
    /**
     * @var string
     */
    protected $eslintExecutable = 'node_modules/.bin/eslint';

    public function getEslintExecutable(): string
    {
        return $this->eslintExecutable;
    }

    /**
     * Set path to the "eslint" executable.
     *
     * @return $this
     */
    public function setEslintExecutable(string $path)
    {
        $this->eslintExecutable = $path;

        return $this;
    }
    //endregion

    //region Option - ext.
    /**
     * Specify JavaScript file extensions.
     *
     * @var array
     */
    protected $ext = [];

    public function getExt(): array
    {
        return $this->ext;
    }

    /**
     * @return $this
     */
    public function setExt(array $extensions, bool $include = true)
    {
        $this->ext = $this->createIncludeList($extensions, $include);

        return $this;
    }

    /**
     * @return $this
     */
    public function addExt(string $extension)
    {
        $this->ext[$extension] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeExt(string $extension)
    {
        unset($this->ext[$extension]);

        return $this;
    }
    //endregion

    //region Option - failOn.
    /**
     * Severity level.
     *
     * @var string
     */
    protected $failOn = 'error';

    public function getFailOn(): string
    {
        return $this->failOn;
    }

    /**
     * Fail if there is a lint with warning severity.
     *
     * @param string $severity
     *   Allowed values are: never, warning, error.
     *
     * @return $this
     */
    public function setFailOn($severity)
    {
        $this->failOn = $severity;

        return $this;
    }
    //endregion

    //region Option - format
    /**
     * @var string
     */
    protected $format = '';

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Specify how to display lints.
     *
     * @return $this
     */
    public function setFormat($value)
    {
        $this->format = $value;

        return $this;
    }
    //endregion

    //region Option - ignorePath.
    /**
     * @var string
     */
    protected $ignorePath = '';

    public function getIgnorePath(): string
    {
        return $this->ignorePath;
    }

    /**
     * @return $this
     */
    public function setIgnorePath(string $value)
    {
        $this->ignorePath = $value;

        return $this;
    }
    //endregion

    //region Option - ignorePattern.
    /**
     * @var null|string
     */
    protected $ignorePattern = null;

    public function getIgnorePattern(): ?string
    {
        return $this->ignorePattern;
    }

    /**
     * @return $this
     */
    public function setIgnorePattern(?string $value)
    {
        $this->ignorePattern = $value;

        return $this;
    }
    //endregion

    //region Option - lintReporters.
    /**
     * @var \Cheppers\LintReport\ReporterInterface[]
     */
    protected $lintReporters = [];

    /**
     * @return \Cheppers\LintReport\ReporterInterface[]
     */
    public function getLintReporters(): array
    {
        return $this->lintReporters;
    }

    /**
     * @param $lintReporters \Cheppers\LintReport\ReporterInterface[]
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
     * @param null|string|ReporterInterface $lintReporter
     *
     * @return $this
     */
    public function addLintReporter(string $id, $lintReporter = null)
    {
        $this->lintReporters[$id] = $lintReporter;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeLintReporter(string $id)
    {
        unset($this->lintReporters[$id]);

        return $this;
    }
    //endregion

    //region Option - maxWarnings.
    /**
     * @var null|int
     */
    protected $maxWarnings = null;

    public function getMaxWarnings(): ?int
    {
        return $this->maxWarnings;
    }

    /**
     * @return $this
     */
    public function setMaxWarnings(?int $value)
    {
        $this->maxWarnings = $value;

        return $this;
    }
    //endregion

    //region Option - noEslintRc.
    /**
     * @var bool
     */
    protected $noESLintRc = false;

    public function isEslintRcDisabled(): bool
    {
        return $this->noESLintRc;
    }

    /**
     * @return $this
     */
    public function setNoESLintRc(bool $value)
    {
        $this->noESLintRc = $value;

        return $this;
    }
    //endregion

    //region Option - noIgnore.
    /**
     * @var bool
     */
    protected $noIgnore = false;

    public function getNoIgnore(): bool
    {
        return $this->noIgnore;
    }

    /**
     * @return $this
     */
    public function setNoIgnore(bool $value)
    {
        $this->noIgnore = $value;

        return $this;
    }
    //endregion

    //region Option - noInlineConfig.
    /**
     * @var bool
     */
    protected $noInlineConfig = false;

    public function getNoInlineConfig(): bool
    {
        return $this->noInlineConfig;
    }

    /**
     * @return $this
     */
    public function setNoInlineConfig(bool $value)
    {
        $this->noInlineConfig = $value;

        return $this;
    }
    //endregion

    //region Option - files.
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
        return $this->files;
    }

    /**
     * File files to lint.
     *
     * @param string[]|bool[] $files
     *   Key-value pair of file names and boolean.
     *
     * @return $this
     */
    public function setFiles(array $files)
    {
        $this->files = $files;

        return $this;
    }
    //endregion

    //region Option - outputFile.
    /**
     * @var string
     */
    protected $outputFile = '';

    public function getOutputFile(): string
    {
        return $this->outputFile;
    }

    public function getRealOutputFile(): string
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
     * @return $this
     */
    public function setOutputFile(string $path)
    {
        $this->outputFile = $path;

        return $this;
    }
    //endregion

    //region Option - quiet.
    /**
     * @var bool
     */
    protected $quiet = false;

    public function isQuiet(): bool
    {
        return $this->quiet;
    }

    /**
     * @return $this
     */
    public function setQuiet(bool $value)
    {
        $this->quiet = $value;

        return $this;
    }
    //endregion

    //region Option - rulesDir.
    /**
     *  An additional rules directory, for user-created rules.
     *
     * @var bool[]
     */
    protected $rulesDir = [];

    public function getRulesDir(): array
    {
        return $this->rulesDir;
    }

    /**
     * @return $this
     */
    public function setRulesDir(array $paths)
    {
        $this->rulesDir = $this->createIncludeList($paths, true);

        return $this;
    }

    /**
     * @return $this
     */
    public function addRulesDir(string $path)
    {
        $this->rulesDir[$path] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeRulesDir(string $path)
    {
        unset($this->rulesDir[$path]);

        return $this;
    }
    //endregion

    //region Option - workingDirectory.
    /**
     * Directory to step in before run the `eslint`.
     *
     * @var string
     */
    protected $workingDirectory = '';

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * Set the current working directory.
     *
     * @return $this
     */
    public function setWorkingDirectory(string $path)
    {
        $this->workingDirectory = $path;

        return $this;
    }
    //endregion

    //endregion

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * All in one configuration.
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

        $this->lintExitCode = $process->run();
        $this->lintStdOutput = $process->getOutput();

        if ($this->isLintSuccess()) {
            if ($this->getFormat() === $this->machineReadableFormat) {
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

                $this->setFormat($this->machineReadableFormat);
                $this->setOutputFile('');
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
        if (!($this->getFormat() === $this->machineReadableFormat && $lintReporters)) {
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
            (string) $this->getExitMessage($exitCode),
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
    public function getCommand(): string
    {
        $cmdPattern = '';
        $cmdArgs = [];

        if ($this->getWorkingDirectory()) {
            $cmdPattern .= 'cd %s && ';
            $cmdArgs[] = escapeshellarg($this->getWorkingDirectory());
        }

        $cmdPattern .= '%s';
        $cmdArgs[] = escapeshellcmd($this->getEslintExecutable());

        $options = $this->getCommandOptions();

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

        foreach ($this->multiOptions as $optionName => $optionCli) {
            if (!empty($options[$optionName])) {
                foreach ($options[$optionName] as $value => $status) {
                    if (!$status || $value === false || $value === null) {
                        continue;
                    }

                    $cmdPattern .= " --{$optionCli} %s";
                    $cmdArgs[] = escapeshellarg($value);
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

    protected function getCommandOptions(): array
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
     */
    protected function getTaskExitCode(int $numOfErrors, int $numOfWarnings): int
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

    protected function filterEnabled(array $items): array
    {
        return gettype(reset($items)) === 'boolean' ? array_keys($items, true, true) : $items;
    }

    /**
     * Returns true if the lint ran successfully.
     *
     * Returns true even if there was any code style error or warning.
     */
    protected function isLintSuccess(): bool
    {
        return in_array($this->lintExitCode, $this->lintSuccessExitCodes());
    }

    /**
     * @return int[]
     */
    protected function lintSuccessExitCodes(): array
    {
        return [
            static::EXIT_CODE_OK,
            static::EXIT_CODE_WARNING,
            static::EXIT_CODE_ERROR,
        ];
    }

    protected function getExitMessage(int $exitCode): ?string
    {
        if (isset($this->exitMessages[$exitCode])) {
            return $this->exitMessages[$exitCode];
        }

        return null;
    }

    /**
     * @return \Cheppers\LintReport\ReporterInterface[]
     */
    protected function initLintReporters(): array
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

    /**
     * The array key is the relevant value and the array value will be a boolean.
     *
     * @param string[]|bool[] $items
     *   Items.
     * @param bool $include
     *   Default value.
     *
     * @return bool[]
     *   Key is the relevant value, the value is a boolean.
     */
    protected function createIncludeList(array $items, bool $include): array
    {
        $item = reset($items);
        if (gettype($item) !== 'boolean') {
            $items = array_fill_keys($items, $include);
        }

        return $items;
    }
}
