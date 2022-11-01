<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Task;

use Robo\Contract\CommandInterface;
use Sweetchuck\LintReport\ReporterInterface;
use Sweetchuck\LintReport\ReportWrapperInterface;
use Sweetchuck\Robo\ESLint\LintReportWrapper\ReportWrapper;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Sweetchuck\Robo\ESLint\Utils;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

abstract class ESLintRun extends BaseTask implements
    CommandInterface,
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

    /**
     * In the CLI command the option will appear only once.
     *
     * Key: internal name.
     * Value: CLI option.
     *
     * @code
     * ::setFoo(true);
     * eslint --foo
     * @endcode
     *
     * @code
     * ::setFoo(false);
     * eslint --no-foo
     * @endcode
     *
     * @code
     * ::setFoo(null);
     * eslint
     * @endcode
     *
     * @var array<string, string>
     */
    protected array $triStateOptions = [
        'color' => 'color',
    ];

    /**
     * In the CLI command the option will appear only once.
     *
     * Key: internal name.
     * Value: CLI option.
     *
     * @code
     * ::setFoo(true);
     * eslint --foo
     * @endcode
     *
     * @code
     * ::setFoo(false);
     * eslint
     * @endcode
     *
     * @var array<string, string>
     */
    protected array $flagOptions = [
        'cache' => 'cache',
        'noESLintRc' => 'no-eslintrc',
        'noIgnore' => 'no-ignore',
        'noInlineConfig' => 'no-inline-config',
        'reportUnusedDisableDirectives' => 'report-unused-disable-directives',
        'quiet' => 'quiet',
        'noErrorOnUnmatchedPattern' => 'no-error-on-unmatched-pattern',
        'exitOnFatalError' => 'exit-on-fatal-error',
    ];

    /**
     * In the CLI command the option will appear only once.
     *
     * Key: internal name.
     * Value: CLI option.
     *
     * @code
     * ::setFoo('myValue');
     * eslint --foo 'myValue'
     * @endcode
     *
     * @var array<string, string>
     */
    protected array $simpleOptions = [
        'parser' => 'parser',
        'parserOptions' => 'parser-options',
        'resolvePluginsRelativeTo' => 'resolve-plugins-relative-to',
        'cacheLocation' => 'cache-location',
        'cacheStrategy' => 'cache-strategy',
        'configFile' => 'config',
        'format' => 'format',
        'ignorePath' => 'ignore-path',
        'maxWarnings' => 'max-warnings',
        'outputFile' => 'output-file',
        'rulesDir' => 'rulesdir',
    ];

    /**
     * In the CLI command the option will appear only once.
     *
     * Key: internal name.
     * Value: CLI option.
     *
     * @code
     * ::setFoo(['a', 'b']);
     * eslint --foo 'a,b'
     * @endcode
     *
     * @var array<string, string>
     */
    protected array $listOptions = [
        'environments' => 'env',
        'extensions' => 'ext',
        'globalVariables' => 'global',
        'plugins' => 'plugin',
    ];

    /**
     * In the CLI command the option will appear multiple times.
     *
     * Key: internal name.
     * Value: CLI option.
     *
     * @code
     * ::setFoo(['a', 'b']);
     * eslint --foo 'a' --foo 'b'
     * @endcode
     *
     * @var array<string, string>
     */
    protected array $multiOptions = [
        'ignorePatterns' => 'ignore-pattern',
    ];

    protected array $multiOptionsPrepared = [
        'rules' => 'rule',
    ];

    // region Options.

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

    // region Option - environments.
    protected array $environments = [];

    public function getEnvironments(): array
    {
        return $this->environments;
    }

    /**
     * @phpstan-param array<string, bool>|array<string> $environments
     *
     * @return $this
     */
    public function setEnvironments(array $environments, bool $include = true)
    {
        $this->environments = Utils::createIncludeList($environments, $include);

        return $this;
    }
    // endregion

    // region Option - extensions.
    /**
     * Specify JavaScript file extensions.
     */
    protected array $extensions = [];

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @return $this
     */
    public function setExtensions(array $extensions, bool $include = true)
    {
        $this->extensions = Utils::createIncludeList($extensions, $include);

        return $this;
    }

    /**
     * @return $this
     */
    public function addExtension(string $extension)
    {
        $this->extensions[$extension] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeExtension(string $extension)
    {
        unset($this->extensions[$extension]);

        return $this;
    }
    // endregion

    // region Option - globalVariables.
    /**
     * Values:
     *  - null: "key"
     *  - false: ignored
     *  - true: "key:true"
     *
     * @phpstan-var array<string, null|bool>
     */
    protected array $globalVariables = [];

    public function getGlobalVariables(): array
    {
        return $this->globalVariables;
    }

    /**
     * @return $this
     */
    public function setGlobalVariables(array $globalVariables)
    {
        $this->globalVariables = Utils::createTriStateList($globalVariables, null);

        return $this;
    }

    public function addGlobalVariable(string $name)
    {
        $this->globalVariables[$name] = null;

        return $this;
    }

    public function addGlobalVariables(array $names)
    {
        foreach (Utils::createTriStateList($names, null) as $name => $state) {
            $this->globalVariables[$name] = $state;
        }

        return $this;
    }

    public function removeGlobalVariable(string $name)
    {
        unset($this->globalVariables[$name]);

        return $this;
    }

    public function removeGlobalVariables(array $names)
    {
        foreach (Utils::createTriStateList($names, false) as $name => $state) {
            $this->globalVariables[$name] = $state;
        }

        return $this;
    }
    // endregion

    // region Option - parser.
    protected ?string $parser = null;

    public function getParser(): ?string
    {
        return $this->parser;
    }

    /**
     * @return $this
     */
    public function setParser(?string $parser)
    {
        $this->parser = $parser;

        return $this;
    }
    // endregion

    // region Option - parserOptions.
    protected array $parserOptions = [];

    public function getParserOptions(): array
    {
        return $this->parserOptions;
    }

    protected function getParserOptionsAsCliOptions(): string
    {
        $parserOptions = $this->getParserOptions();

        // NOTE: There is not enough documentation.
        // https://eslint.org/docs/latest/user-guide/command-line-interface#--parser-options
        return $parserOptions ?
            json_encode($parserOptions)
            : '';
    }

    /**
     * @return $this
     */
    public function setParserOptions(array $parserOptions)
    {
        $this->parserOptions = $parserOptions;

        return $this;
    }
    // endregion

    // region Option - resolvePluginsRelativeTo.
    protected ?string $resolvePluginsRelativeTo = null;

    public function getResolvePluginsRelativeTo(): ?string
    {
        return $this->resolvePluginsRelativeTo;
    }

    /**
     * @return $this
     */
    public function setResolvePluginsRelativeTo(?string $baseDir)
    {
        $this->resolvePluginsRelativeTo = $baseDir;

        return $this;
    }
    // endregion

    // region Option -  plugins.
    /**
     * @phpstan-var array<string, bool>
     */
    protected array $plugins = [];

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @return $this
     */
    public function setPlugins(array $plugins)
    {
        $this->plugins = Utils::createIncludeList($plugins, true);

        return $this;
    }

    /**
     * @return $this
     */
    public function addPlugin(string $name)
    {
        $this->plugins[$name] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function addPlugins(array $plugins)
    {
        foreach (Utils::createIncludeList($plugins, true) as $name => $state) {
            $this->plugins[$name] = $state;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function removePlugin(string $name)
    {
        $this->plugins[$name] = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function removePlugins(array $plugins)
    {
        foreach (Utils::createIncludeList($plugins, false) as $name => $state) {
            $this->plugins[$name] = $state;
        }

        return $this;
    }
    // endregion

    // region Option - rule.
    protected array $rules = [];

    public function getRules(): array
    {
        return $this->rules;
    }

    protected function getRulesAsCliOptions(): array
    {
        $rules = [];
        foreach ($this->getRules() as $id => $rule) {
            $rules[] = Utils::ruleToOptionValue($id, $rule);
        }

        return $rules;
    }

    public function setRules(array $rules)
    {
        $this->rules = [];
        $this->addRules($rules);

        return $this;
    }

    public function addRule(array $rule)
    {
        $id = array_shift($rule);
        $this->rules[$id] = $rule;

        return $this;
    }

    public function addRules(iterable $rules)
    {
        foreach ($rules as $id => $rule) {
            if (($rule[0] ?? null) !== $id) {
                settype($rule, 'array');
                array_unshift($rule, $id);
            }

            $this->addRule($rule);
        }
    }

    /**
     * @return $this
     */
    public function removeRule(string $id)
    {
        unset($this->rules[$id]);

        return $this;
    }

    public function removeRules(iterable $rules)
    {
        foreach ($rules as $id => $rule) {
            if (is_string($rule)) {
                $id = $rule;
            } elseif (is_numeric($id)) {
                $id = $rule[0] ?? '';
            }

            $this->removeRule($id);
        }

        return $this;
    }
    // endregion

    // region Option - rulesDir.
    /**
     *  An additional rules directory, for user-created rules.
     *
     * @var null|string
     */
    protected ?string $rulesDir = null;

    public function getRulesDir(): ?string
    {
        return $this->rulesDir;
    }

    /**
     * @return $this
     */
    public function setRulesDir(?string $path)
    {
        $this->rulesDir = $path;

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

    // region Option - ignorePatterns.
    protected ?array $ignorePatterns = [];

    public function getIgnorePatterns(): array
    {
        return $this->ignorePatterns;
    }

    /**
     * @return $this
     */
    public function setIgnorePatterns(array $value)
    {
        $this->ignorePatterns = Utils::createIncludeList($value, true);

        return $this;
    }

    public function addIgnorePattern(string $pattern)
    {
        $this->ignorePatterns[$pattern] = true;

        return $this;
    }

    public function addIgnorePatterns(array $patterns)
    {
        foreach (Utils::createIncludeList($patterns, true) as $pattern => $status) {
            $this->ignorePatterns[$pattern] = $status;
        }
    }

    /**
     * @return $this
     */
    public function removeIgnorePattern(string $pattern)
    {
        unset($this->ignorePatterns[$pattern]);

        return $this;
    }

    public function removeIgnorePatterns(array $patterns)
    {
        foreach (Utils::createIncludeList($patterns, false) as $pattern => $status) {
            if (!$status) {
                unset($this->ignorePatterns[$pattern]);
            }
        }

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

        if (Path::isAbsolute($outputFile)) {
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

    // region Option - reportUnusedDisableDirectives
    protected bool $reportUnusedDisableDirectives = false;

    public function getReportUnusedDisableDirectives(): bool
    {
        return $this->reportUnusedDisableDirectives;
    }

    /**
     * @return $this
     */
    public function setReportUnusedDisableDirectives(bool $value)
    {
        $this->reportUnusedDisableDirectives = $value;

        return $this;
    }
    // endregion

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

    // region Option - cacheStrategy
    /**
     * @phpstan-var null|robo-eslint-enum-cache-strategy
     */
    protected ?string $cacheStrategy = null;

    /**
     * @phpstan-return null|robo-eslint-enum-cache-strategy
     */
    public function getCacheStrategy(): ?string
    {
        return $this->cacheStrategy;
    }

    /**
     * @phpstan-param null|robo-eslint-enum-cache-strategy $cacheStrategy
     *
     * @return $this
     */
    public function setCacheStrategy(?string $cacheStrategy)
    {
        $this->cacheStrategy = $cacheStrategy;

        return $this;
    }
    // endregion

    // region Option - noErrorOnUnmatchedPattern
    protected bool $noErrorOnUnmatchedPattern = false;

    public function getNoErrorOnUnmatchedPattern(): bool
    {
        return $this->noErrorOnUnmatchedPattern;
    }

    /**
     * @return $this
     */
    public function setNoErrorOnUnmatchedPattern(
        bool $noErrorOnUnmatchedPattern
    ) {
        $this->noErrorOnUnmatchedPattern = $noErrorOnUnmatchedPattern;

        return $this;
    }
    // endregion

    // region Option - exitOnFatalError
    protected bool $exitOnFatalError = false;

    public function getExitOnFatalError(): bool
    {
        return $this->exitOnFatalError;
    }

    /**
     * @return $this
     */
    public function setExitOnFatalError(bool $exitOnFatalError)
    {
        $this->exitOnFatalError = $exitOnFatalError;

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
                case 'workingDirectory':
                    $this->setWorkingDirectory($value);
                    break;

                case 'eslintExecutable':
                    $this->setEslintExecutable($value);
                    break;

                case 'failOn':
                    $this->setFailOn($value);
                    break;

                case 'lintReporters':
                    $this->setLintReporters($value);
                    break;

                case 'assetNamePrefix':
                    $this->setAssetNamePrefix($value);
                    break;

                case 'noESLintRc':
                    $this->setNoESLintRc($value);
                    break;

                case 'configFile':
                    $this->setConfigFile($value);
                    break;

                case 'environments':
                    $this->setEnvironments($value);
                    break;

                case 'extensions':
                    $this->setExtensions($value);
                    break;

                case 'globalVariables':
                    $this->setGlobalVariables($value);
                    break;

                case 'parser':
                    $this->setParser($value);
                    break;

                case 'parserOptions':
                    $this->setParserOptions($value);
                    break;

                case 'resolvePluginsRelativeTo':
                    $this->setResolvePluginsRelativeTo($value);
                    break;

                case 'plugin':
                    $this->setPlugins($value);
                    break;

                case 'rules':
                    $this->setRules($value);
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

                case 'ignorePatterns':
                    $this->setIgnorePatterns($value);
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

                case 'reportUnusedDisableDirectives':
                    $this->setReportUnusedDisableDirectives($value);
                    break;

                case 'cache':
                    $this->setCache($value);
                    break;

                case 'cacheLocation':
                    $this->setCacheLocation($value);
                    break;

                case 'cacheStrategy':
                    $this->setCacheStrategy($value);
                    break;

                case 'noErrorOnUnmatchedPattern':
                    $this->setNoErrorOnUnmatchedPattern($value);
                    break;

                case 'exitOnFatalError':
                    $this->setExitOnFatalError($value);
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
                $this->reportWrapper->numOfWarnings(),
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

        foreach ($this->multiOptionsPrepared as $optionName => $optionCli) {
            if (!empty($options[$optionName])) {
                foreach ($options[$optionName] as $value) {
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
            'noESLintRc' => $this->isEslintRcDisabled(),
            'configFile' => $this->getConfigFile(),
            'environments' => $this->getEnvironments(),
            'extensions' => $this->getExtensions(),
            'globalVariables' => Utils::getGlobalVariablesAsCliOptions($this->getGlobalVariables()),
            'parser' => $this->getParser(),
            'parserOptions' => $this->getParserOptionsAsCliOptions(),
            'resolvePluginsRelativeTo' => $this->getResolvePluginsRelativeTo(),
            'plugins' => $this->getPlugins(),
            'rules' => $this->getRulesAsCliOptions(),
            'rulesDir' => $this->getRulesDir(),

            'ignorePath' => $this->getIgnorePath(),
            'noIgnore' => $this->getNoIgnore(),
            'ignorePatterns' => $this->getIgnorePatterns(),

            'quiet' => $this->isQuiet(),
            'maxWarnings' => $this->getMaxWarnings(),

            'outputFile' => $this->getOutputFile(),
            'format' => $this->getFormat(),
            'color' => $this->getColor(),

            'noInlineConfig' => $this->getNoInlineConfig(),
            'reportUnusedDisableDirectives' => $this->getReportUnusedDisableDirectives(),

            'cache' => $this->getCache(),
            'cacheLocation' => $this->getCacheLocation(),
            'cacheStrategy' => $this->getCacheStrategy(),

            'noErrorOnUnmatchedPattern' => $this->getNoErrorOnUnmatchedPattern(),
            'exitOnFatalError' => $this->getExitOnFatalError(),
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
}
