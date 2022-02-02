<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Task;

use Sweetchuck\LintReport\ReporterInterface;
use Sweetchuck\LintReport\ReportWrapperInterface;
use Sweetchuck\Robo\ESLint\LintReportWrapper\ReportWrapper;
use Sweetchuck\Robo\ESLint\Utils;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Process\Process;

abstract class ESLintRun extends BaseTask implements
    ContainerAwareInterface,
    OutputAwareInterface
{

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
     */
    protected string $processClass = Process::class;

    protected ?Result $taskResult = null;

    protected int $lintExitCode = 0;

    protected string $lintStdOutput = '';

    protected bool $isLintStdOutputPublic = true;

    protected string $reportRaw = '';

    /**
     * Exit code and error message mapping.
     *
     * @var string[]
     */
    protected array $exitMessages = [
        0 => 'No lints were found',
        1 => 'One or more warnings were reported (and no errors)',
        2 => 'One or more errors were reported (and any number of warnings)',
    ];

    protected bool $addFilesToCliCommand = true;

    protected string $machineReadableFormat = 'json';

    protected string $lintOutput = '';

    protected array $report = [];

    protected ?ReportWrapperInterface $reportWrapper = null;

    protected array $assets = [
        'report' => null,
    ];

    protected array $triStateOptions = [
        'color' => 'color',
    ];

    protected array $flagOptions = [
        'cache' => 'cache',
        'noESLintRc' => 'no-eslintrc',
        'noIgnore' => 'no-ignore',
        'noInlineConfig' => 'no-inline-config',
        'quiet' => 'quiet',
    ];

    protected array $simpleOptions = [
        'cacheLocation' => 'cache-location',
        'configFile' => 'config',
        'format' => 'format',
        'ignorePath' => 'ignore-path',
        'ignorePattern' => 'ignore-pattern',
        'maxWarnings' => 'max-warnings',
        'outputFile' => 'output-file',
    ];

    protected array $listOptions = [
        'ext' => 'ext',
    ];

    protected array $multiOptions = [
        'rulesDir' => 'rulesdir',
    ];

    // region Options - Not supported.
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
    // endregion

    // region Options.

    // region Option - cache.
    /**
     * Only check changed files - default: false.
     */
    protected bool $cache = false;

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
    // endregion

    // region Option - cacheLocation.
    /**
     * Path to the cache file or directory.
     */
    protected string $cacheLocation = '';

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
    // endregion

    // region Option - color.
    protected ?bool $color = null;

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
    // endregion

    // region Option - configFile.
    protected string $configFile = '';

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
    // endregion

    // region Option - assetNamePrefix.
    protected string $assetNamePrefix = '';

    public function getAssetNamePrefix(): string
    {
        return $this->assetNamePrefix;
    }

    /**
     * @return $this
     */
    public function setAssetNamePrefix(string $value)
    {
        $this->assetNamePrefix = $value;

        return $this;
    }
    // endregion

    // region Option - eslintExecutable.
    protected string $eslintExecutable = 'node_modules/.bin/eslint';

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
    // endregion

    // region Option - ext.
    /**
     * Specify JavaScript file extensions.
     */
    protected array $ext = [];

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
    // endregion

    // region Option - failOn.
    /**
     * Severity level.
     */
    protected string $failOn = 'error';

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
    public function setFailOn(string $severity)
    {
        $this->failOn = $severity;

        return $this;
    }
    // endregion

    // region Option - format
    protected string $format = '';

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Specify how to display lints.
     *
     * @return $this
     */
    public function setFormat(string $value)
    {
        $this->format = $value;

        return $this;
    }
    // endregion

    // region Option - ignorePath.
    protected string $ignorePath = '';

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
    // endregion

    // region Option - ignorePattern.
    protected ?string $ignorePattern = null;

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
    // endregion

    // region Option - lintReporters.
    /**
     * @var \Sweetchuck\LintReport\ReporterInterface[]
     */
    protected array $lintReporters = [];

    /**
     * @return \Sweetchuck\LintReport\ReporterInterface[]
     */
    public function getLintReporters(): array
    {
        return $this->lintReporters;
    }

    /**
     * @param $lintReporters \Sweetchuck\LintReport\ReporterInterface[]
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
    // endregion

    // region Option - maxWarnings.
    protected ?int $maxWarnings = null;

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
    // endregion

    // region Option - noEslintRc.
    protected bool $noESLintRc = false;

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
    // endregion

    // region Option - noIgnore.
    protected bool $noIgnore = false;

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
    // endregion

    // region Option - noInlineConfig.
    protected bool $noInlineConfig = false;

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
    // endregion

    // region Option - files.
    /**
     * Files to check.
     */
    protected array $files = [];

    public function getFiles(): array
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
    // endregion

    // region Option - outputFile.
    protected string $outputFile = '';

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
    // endregion

    // region Option - quiet.
    protected bool $quiet = false;

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
    // endregion

    // region Option - rulesDir.
    /**
     *  An additional rules directory, for user-created rules.
     *
     * @var bool[]
     */
    protected array $rulesDir = [];

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
    // endregion

    // region Option - workingDirectory.
    /**
     * Directory to step in before run the `eslint`.
     */
    protected string $workingDirectory = '';

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
    // endregion

    // endregion

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

                case 'assetNamePrefix':
                    $this->setAssetNamePrefix($value);
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
     * @return $this
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
        $process = new $this->processClass(
            [
                'bash',
                '-c',
                $command,
            ],
            $this->getWorkingDirectory() ?: '.',
        );

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
                /** @var \Symfony\Component\Process\Process $process */
                $process = new $this->processClass(
                    [
                        'bash',
                        '-c',
                        $this->getCommand(),
                    ],
                    $this->getWorkingDirectory() ?: '.',
                );
                //$process->setWorkingDirectory($this->getWorkingDirectory() ?: '.');
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
        if ($this->isLintSuccess()) {
            $this->assets['report'] = $this->reportWrapper;
        }

        return $this;
    }

    protected function runReturn(): Result
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
            $this->getAssetsWithPrefixedNames()
        );
    }

    protected function getAssetsWithPrefixedNames(): array
    {
        $prefix = $this->getAssetNamePrefix();
        if (!$prefix) {
            return $this->assets;
        }

        $data = [];
        foreach ($this->assets as $key => $value) {
            $data["{$prefix}{$key}"] = $value;
        }

        return $data;
    }

    public function getCommand(): string
    {
        $cmdPattern = '';
        $cmdArgs = [];

        //if ($this->getWorkingDirectory()) {
        //    $cmdPattern .= 'cd %s && ';
        //    $cmdArgs[] = escapeshellarg($this->getWorkingDirectory());
        //}

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
                $cmdArgs[] = escapeshellarg((string) $options[$optionName]);
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
     * @return \Sweetchuck\LintReport\ReporterInterface[]
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
