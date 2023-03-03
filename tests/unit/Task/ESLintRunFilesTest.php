<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Unit\Task;

use Codeception\Stub;
use PHPUnit\Framework\SkippedTestSuiteError;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\Robo\ESLint\Task\ESLintRunFiles;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;

/**
 * @covers \Sweetchuck\Robo\ESLint\Task\ESLintRunFiles
 * @covers \Sweetchuck\Robo\ESLint\Task\ESLintRun
 * @covers \Sweetchuck\Robo\ESLint\ESLintTaskLoader
 */
class ESLintRunFilesTest extends TaskTestBase
{
    protected static function getMethod(string $name): \ReflectionMethod
    {
        $class = new \ReflectionClass(ESLintRunFiles::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    protected function initTaskCreate(): CollectionBuilder
    {
        return $this->taskBuilder->taskESLintRunFiles();
    }

    public function casesGetSetOutputFile(): array
    {
        return [
            'empty' => [
                '',
                '',
                [],
            ],
            'wd empty relative' => [
                'a.js',
                './a.js',
                [
                    'outputFile' => 'a.js',
                ],
            ],
            'wd empty abs' => [
                '/a.js',
                '/a.js',
                [
                    'outputFile' => '/a.js',
                ],
            ],
            'wd foo relative' => [
                'a.js',
                'foo/a.js',
                [
                    'outputFile' => 'a.js',
                    'workingDirectory' => 'foo',
                ],
            ],
            'wd foo abs' => [
                '/abs.js',
                '/abs.js',
                [
                    'outputFile' => '/abs.js',
                    'workingDirectory' => 'foo',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetSetOutputFile
     */
    public function testGetSetOutputFile(string $expectedDirect, string $expectedReal, array $options): void
    {
        $this->task->setOptions($options);

        $this->tester->assertSame($expectedDirect, $this->task->getOutputFile());
        $this->tester->assertSame($expectedReal, $this->task->getRealOutputFile());
    }

    public function testGetSetLintReporters(): void
    {
        $this->task
            ->setOptions([
                'lintReporters' => [
                    'aKey' => 'aValue',
                ],
            ])
            ->addLintReporter('bKey', 'bValue')
            ->addLintReporter('cKey', 'cValue')
            ->removeLintReporter('bKey');

        $this->tester->assertSame(
            [
                'aKey' => 'aValue',
                'cKey' => 'cValue',
            ],
            $this->task->getLintReporters(),
        );
    }

    public function casesGetCommand(): array
    {
        return [
            'basic' => [
                'node_modules/.bin/eslint',
                [],
            ],
            'workingDirectory' => [
                // No need to "cd", because the Process receive the cwd.
                "node_modules/.bin/eslint",
                [
                    'workingDirectory' => 'my-dir',
                ],
            ],
            'eslintExecutable' => [
                "something/else --config 'foo'",
                [
                    'eslintExecutable' => 'something/else',
                    'configFile' => 'foo'
                ],
            ],
            'configFile-empty' => [
                'node_modules/.bin/eslint',
                ['configFile' => ''],
            ],
            'configFile-string' => [
                "node_modules/.bin/eslint --config 'foo'",
                ['configFile' => 'foo'],
            ],
            'noESLintRc-false' => [
                'node_modules/.bin/eslint',
                ['noESLintRc' => false],
                [],
            ],
            'noESLintRc-true' => [
                'node_modules/.bin/eslint --no-eslintrc',
                ['noESLintRc' => true],
            ],
            'env-empty' => [
                'node_modules/.bin/eslint',
                ['environments' => []],
            ],
            'env-vector-1' => [
                "node_modules/.bin/eslint --env 'browser'",
                ['environments' => ['browser']],
            ],
            'env-vector-multi' => [
                "node_modules/.bin/eslint --env 'browser,node'",
                ['environments' => ['browser', 'node']],
            ],
            'env-assoc' => [
                "node_modules/.bin/eslint --env 'browser,es6'",
                [
                    'environments' => [
                        'browser' => true,
                        'node' => false,
                        'es6' => true,
                    ],
                ],
            ],
            'extensions-empty' => [
                'node_modules/.bin/eslint',
                ['extensions' => []],
            ],
            'extensions-vector-1' => [
                "node_modules/.bin/eslint --ext '.a'",
                ['extensions' => ['.a']],
            ],
            'extensions-vector-multi' => [
                "node_modules/.bin/eslint --ext '.a,.b'",
                ['extensions' => ['.a', '.b']],
            ],
            'extensions-assoc' => [
                "node_modules/.bin/eslint --ext '.b,.d'",
                [
                    'extensions' => [
                        '.a' => false,
                        '.b' => true,
                        '.c' => false,
                        '.d' => true,
                    ],
                ],
            ],
            'globalVariables-empty' => [
                'node_modules/.bin/eslint',
                ['globalVariables' => []],
            ],
            'globalVariables-vector-1' => [
                "node_modules/.bin/eslint --global 'a'",
                ['globalVariables' => ['a']],
            ],
            'globalVariables-vector-multi' => [
                "node_modules/.bin/eslint --global 'a,b'",
                ['globalVariables' => ['a', 'b']],
            ],
            'globalVariables-assoc' => [
                "node_modules/.bin/eslint --global 'a,c:true'",
                [
                    'globalVariables' => [
                        'a' => null,
                        'b' => false,
                        'c' => true,
                    ],
                ],
            ],
            'parser-empty' => [
                'node_modules/.bin/eslint',
                ['cacheLocation' => ''],
            ],
            'parser-string' => [
                "node_modules/.bin/eslint --parser 'a'",
                ['parser' => 'a'],
            ],
            'parserOptions-empty' => [
                'node_modules/.bin/eslint',
                ['parserOptions' => []],
            ],
            'parserOptions-basic' => [
                'node_modules/.bin/eslint --parser-options \'{"a":"b"}\'',
                ['parserOptions' => ['a' => 'b']],
            ],
            'cache-false' => [
                'node_modules/.bin/eslint',
                ['cache' => false],
            ],
            'cache-true' => [
                'node_modules/.bin/eslint --cache',
                ['cache' => true],
            ],
            'cacheLocation-empty' => [
                'node_modules/.bin/eslint',
                ['cacheLocation' => ''],
            ],
            'cacheLocation-string' => [
                "node_modules/.bin/eslint --cache-location 'my-dir'",
                ['cacheLocation' => 'my-dir'],
            ],
            'cacheStrategy-empty' => [
                'node_modules/.bin/eslint',
                ['cacheStrategy' => ''],
            ],
            'cacheStrategy-string' => [
                "node_modules/.bin/eslint --cache-strategy 'content'",
                ['cacheStrategy' => 'content'],
            ],
            'noErrorOnUnmatchedPattern-false' => [
                "node_modules/.bin/eslint",
                ['noErrorOnUnmatchedPattern' => false],
            ],
            'noErrorOnUnmatchedPattern-true' => [
                "node_modules/.bin/eslint --no-error-on-unmatched-pattern",
                ['noErrorOnUnmatchedPattern' => true],
            ],
            'exitOnFatalError-true' => [
                "node_modules/.bin/eslint --exit-on-fatal-error",
                ['exitOnFatalError' => true],
            ],
            'exitOnFatalError-false' => [
                "node_modules/.bin/eslint",
                ['exitOnFatalError' => false],
            ],
            'rulesDir-empty' => [
                'node_modules/.bin/eslint',
                ['rulesDir' => null],
            ],
            'rulesDir-vector' => [
                "node_modules/.bin/eslint --rulesdir 'my-dir-1'",
                ['rulesDir' => 'my-dir-1'],
            ],
            'ignorePath-empty' => [
                'node_modules/.bin/eslint',
                ['ignorePath' => ''],
            ],
            'ignorePath-string' => [
                "node_modules/.bin/eslint --ignore-path 'my-dir'",
                ['ignorePath' => 'my-dir'],
            ],
            'noIgnore-false' => [
                'node_modules/.bin/eslint',
                ['noIgnore' => false],
            ],
            'notIgnore-true' => [
                'node_modules/.bin/eslint --no-ignore',
                ['noIgnore' => true],
            ],
            'ignorePattern-empty' => [
                'node_modules/.bin/eslint',
                ['ignorePattern' => ''],
            ],
            'ignorePattern-vector' => [
                "node_modules/.bin/eslint --ignore-pattern 'a' --ignore-pattern 'b'",
                ['ignorePatterns' => ['a', 'b']],
            ],
            'ignorePattern-assoc' => [
                "node_modules/.bin/eslint --ignore-pattern 'a' --ignore-pattern 'c'",
                [
                    'ignorePatterns' => [
                        'a' => true,
                        'b' => false,
                        'c' => true,
                    ],
                ],
            ],
            'quiet-false' => [
                'node_modules/.bin/eslint',
                ['quiet' => false],
            ],
            'quiet-true' => [
                'node_modules/.bin/eslint --quiet',
                ['quiet' => true],
            ],
            'maxWarnings-negative' => [
                "node_modules/.bin/eslint --max-warnings '-1'",
                ['maxWarnings' => -1],
            ],
            'maxWarnings-zero' => [
                "node_modules/.bin/eslint --max-warnings '0'",
                ['maxWarnings' => 0],
            ],
            'maxWarnings-positive' => [
                "node_modules/.bin/eslint --max-warnings '1'",
                ['maxWarnings' => 1],
            ],
            'maxWarnings-null' => [
                'node_modules/.bin/eslint',
                ['maxWarnings' => null],
            ],
            'format-empty' => [
                "node_modules/.bin/eslint",
                ['format' => ''],
            ],
            'format-string' => [
                "node_modules/.bin/eslint --format 'foo'",
                ['format' => 'foo'],
            ],
            'outputFile-empty' => [
                'node_modules/.bin/eslint',
                ['outputFile' => ''],
            ],
            'outputFile-string' => [
                "node_modules/.bin/eslint --output-file 'my-file'",
                ['outputFile' => 'my-file'],
            ],
            'color-false' => [
                'node_modules/.bin/eslint --no-color',
                ['color' => false],
            ],
            'color-null' => [
                'node_modules/.bin/eslint',
                ['color' => null],
            ],
            'color-true' => [
                'node_modules/.bin/eslint --color',
                ['color' => true],
            ],
            'noInlineConfig-false' => [
                'node_modules/.bin/eslint',
                ['noInlineConfig' => false],
            ],
            'noInlineConfig-true' => [
                "node_modules/.bin/eslint --no-inline-config",
                ['noInlineConfig' => true],
            ],
            'reportUnusedDisableDirectives-true' => [
                "node_modules/.bin/eslint --report-unused-disable-directives",
                ['reportUnusedDisableDirectives' => true],
            ],
            'reportUnusedDisableDirectives-false' => [
                "node_modules/.bin/eslint",
                ['reportUnusedDisableDirectives' => false],
            ],
            'files-empty' => [
                "node_modules/.bin/eslint",
                ['files' => []],
            ],
            'files-vector' => [
                "node_modules/.bin/eslint -- 'foo' 'bar' 'baz'",
                ['files' => ['foo', 'bar', 'baz']],
            ],
            'files-assoc' => [
                "node_modules/.bin/eslint -- 'a' 'd'",
                [
                    'files' => [
                        'a' => true,
                        'b' => null,
                        'c' => false,
                        'd' => true,
                        'e' => false,
                    ],
                ],
            ],
            'multiple' => [
                "node_modules/.bin/eslint --color --no-inline-config --max-warnings '1' --output-file 'of' -- 'a' 'b'",
                [
                    'maxWarnings' => 1,
                    'outputFile' => 'of',
                    'color' => true,
                    'noInlineConfig' => true,
                    'files' => ['a', 'b'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options): void
    {
        $this->task->setOptions($options);
        $this->tester->assertSame($expected, $this->task->getCommand());
    }

    public function testGetCommandOptionExtensions(): void
    {
        $this->task->setExtensions(['a', 'b']);
        $this->tester->assertSame(
            "node_modules/.bin/eslint --ext 'a,b'",
            $this->task->getCommand(),
        );

        $this->task->addExtension('c');
        $this->tester->assertSame(
            "node_modules/.bin/eslint --ext 'a,b,c'",
            $this->task->getCommand(),
        );

        $this->task->removeExtension('b');
        $this->tester->assertSame(
            "node_modules/.bin/eslint --ext 'a,c'",
            $this->task->getCommand(),
        );
    }

    public function testGetCommandOptionIgnorePatterns(): void
    {
        $this->task->setIgnorePatterns(['a', 'b']);
        $this->tester->assertSame(
            "node_modules/.bin/eslint --ignore-pattern 'a' --ignore-pattern 'b'",
            $this->task->getCommand(),
        );

        $this->task->addIgnorePattern('c');
        $this->tester->assertSame(
            "node_modules/.bin/eslint --ignore-pattern 'a' --ignore-pattern 'b' --ignore-pattern 'c'",
            $this->task->getCommand(),
        );

        $this->task->removeIgnorePattern('b');
        $this->tester->assertSame(
            "node_modules/.bin/eslint --ignore-pattern 'a' --ignore-pattern 'c'",
            $this->task->getCommand(),
        );

        $this->task->removeIgnorePatterns(['c']);
        $this->tester->assertSame(
            "node_modules/.bin/eslint --ignore-pattern 'a'",
            $this->task->getCommand(),
        );
    }

    public function testGetCommandOptionRules(): void
    {
        $rules = [
            'r1' => [
                'value' => [
                    'error',
                    'foo',
                ],
                'cli' => '{"r1":["error","foo"]}',
            ],
            'r2' => [
                'value' => [
                    'error',
                    'bar'
                ],
                'cli' => '{"r2":["error","bar"]}',
            ],
            'r3' => [
                'value' => 'warn',
                'cli' => '{"r3":["warn"]}',
            ],
        ];

        $this->task->setOptions([
            'rules' => [
                'r1' => $rules['r1']['value'],
                'r2' => $rules['r2']['value'],
                'r3' => $rules['r3']['value'],
            ],
        ]);
        $this->tester->assertSame(
            sprintf(
                'node_modules/.bin/eslint --rule %s --rule %s --rule %s',
                escapeshellarg($rules['r1']['cli']),
                escapeshellarg($rules['r2']['cli']),
                escapeshellarg($rules['r3']['cli']),
            ),
            $this->task->getCommand(),
        );

        $this->task->removeRules(['r2']);
        $this->tester->assertSame(
            sprintf(
                'node_modules/.bin/eslint --rule %s --rule %s',
                escapeshellarg($rules['r1']['cli']),
                escapeshellarg($rules['r3']['cli']),
            ),
            $this->task->getCommand(),
        );

        $this->task->removeRules([
            [
                'r3',
                'error',
            ],
        ]);
        $this->tester->assertSame(
            sprintf(
                'node_modules/.bin/eslint --rule %s',
                escapeshellarg($rules['r1']['cli']),
            ),
            $this->task->getCommand(),
        );
    }

    public function testExitCodeConstants(): void
    {
        $this->tester->assertSame(0, ESLintRunFiles::EXIT_CODE_OK);
        $this->tester->assertSame(1, ESLintRunFiles::EXIT_CODE_WARNING);
        $this->tester->assertSame(2, ESLintRunFiles::EXIT_CODE_ERROR);
        $this->tester->assertSame(3, ESLintRunFiles::EXIT_CODE_INVALID);
    }

    public function casesGetTaskExitCode(): array
    {
        $o = ESLintRunFiles::EXIT_CODE_OK;
        $w = ESLintRunFiles::EXIT_CODE_WARNING;
        $e = ESLintRunFiles::EXIT_CODE_ERROR;
        $u = 5;

        return [
            'never-000' => [$o, 'never', 0, 0, 0],
            'never-001' => [$o, 'never', 0, 0, 1],
            'never-002' => [$o, 'never', 0, 0, 2],
            'never-005' => [$u, 'never', 0, 0, 5],

            'never-010' => [$o, 'never', 0, 1, 0],
            'never-011' => [$o, 'never', 0, 1, 1],
            'never-012' => [$o, 'never', 0, 1, 2],
            'never-015' => [$u, 'never', 0, 1, 5],

            'never-100' => [$o, 'never', 1, 0, 0],
            'never-101' => [$o, 'never', 1, 0, 1],
            'never-102' => [$o, 'never', 1, 0, 2],
            'never-105' => [$u, 'never', 1, 0, 5],

            'never-110' => [$o, 'never', 1, 1, 0],
            'never-111' => [$o, 'never', 1, 1, 1],
            'never-112' => [$o, 'never', 1, 1, 2],
            'never-115' => [$u, 'never', 1, 1, 5],

            'warning-000' => [$o, 'warning', 0, 0, 0],
            'warning-001' => [$o, 'warning', 0, 0, 1],
            'warning-002' => [$o, 'warning', 0, 0, 2],
            'warning-005' => [$u, 'warning', 0, 0, 5],

            'warning-010' => [$w, 'warning', 0, 1, 0],
            'warning-011' => [$w, 'warning', 0, 1, 1],
            'warning-012' => [$w, 'warning', 0, 1, 2],
            'warning-015' => [$u, 'warning', 0, 1, 5],

            'warning-100' => [$e, 'warning', 1, 0, 0],
            'warning-101' => [$e, 'warning', 1, 0, 1],
            'warning-102' => [$e, 'warning', 1, 0, 2],
            'warning-105' => [$u, 'warning', 1, 0, 5],

            'warning-110' => [$e, 'warning', 1, 1, 0],
            'warning-111' => [$e, 'warning', 1, 1, 1],
            'warning-112' => [$e, 'warning', 1, 1, 2],
            'warning-115' => [$u, 'warning', 1, 1, 5],

            'error-000' => [$o, 'error', 0, 0, 0],
            'error-001' => [$o, 'error', 0, 0, 1],
            'error-002' => [$o, 'error', 0, 0, 2],
            'error-005' => [$u, 'error', 0, 0, 5],

            'error-010' => [$o, 'error', 0, 1, 0],
            'error-011' => [$o, 'error', 0, 1, 1],
            'error-012' => [$o, 'error', 0, 1, 2],
            'error-015' => [$u, 'error', 0, 1, 5],

            'error-100' => [$e, 'error', 1, 0, 0],
            'error-101' => [$e, 'error', 1, 0, 1],
            'error-102' => [$e, 'error', 1, 0, 2],
            'error-105' => [$u, 'error', 1, 0, 5],

            'error-110' => [$e, 'error', 1, 1, 0],
            'error-111' => [$e, 'error', 1, 1, 1],
            'error-112' => [$e, 'error', 1, 1, 2],
            'error-115' => [$u, 'error', 1, 1, 5],
        ];
    }

    /**
     * @dataProvider casesGetTaskExitCode
     */
    public function testGetTaskExitCode(
        int $expected,
        string $failOn,
        int $numOfErrors,
        int $numOfWarnings,
        int $lintExitCode
    ): void {
        /** @var \Sweetchuck\Robo\ESLint\Task\ESLintRunFiles $task */
        $task = Stub::construct(
            ESLintRunFiles::class,
            [['failOn' => $failOn]],
            ['lintExitCode' => $lintExitCode]
        );

        $this->tester->assertSame(
            $expected,
            static::getMethod('getTaskExitCode')->invokeArgs($task, [$numOfErrors, $numOfWarnings]),
        );
    }

    public function casesRunNormal(): array
    {
        return [
            'success' => [
                0,
                [],
            ],
            'warning' => [
                1,
                [
                    [
                        'filePath' => 'a.js',
                        'messages' => [
                            [
                                'ruleId' => 'r1',
                                'severity' => 1,
                                'message' => 'm1',
                                'column' => 'c1',
                                'nodeType' => 'nt1',
                                'source' => 's1',
                            ],
                        ],
                        'errorCount' => 0,
                        'warningCount' => 1,
                    ]
                ],
            ],
            'error' => [
                2,
                [
                    [
                        'filePath' => 'a.js',
                        'messages' => [
                            [
                                'ruleId' => 'r1',
                                'severity' => 2,
                                'message' => 'm1',
                                'column' => 'c1',
                                'nodeType' => 'nt1',
                                'source' => 's1',
                            ],
                        ],
                        'errorCount' => 1,
                        'warningCount' => 0,
                    ]
                ],
            ],
        ];
    }

    /**
     * This way cannot be tested those cases when the lint process failed.
     *
     * @dataProvider casesRunNormal
     */
    public function testRunNormal(int $expectedExitCode, array $expectedReport): void
    {
        DummyProcess::$prophecy[] = [
            'exitCode' => $expectedExitCode,
            'stdOutput' => json_encode($expectedReport),
            'stdError' => '',
        ];

        $options = [
            'workingDirectory' => 'my-working-dir',
            'format' => 'json',
            'failOn' => 'warning',
        ];
        $this->task->setOptions($options);

        $result = $this->task->run();

        $this->tester->assertSame(
            $expectedExitCode,
            $result->getExitCode(),
            'Exit code',
        );

        $assetNamePrefix = $options['assetNamePrefix'] ?? '';

        /** @var \Sweetchuck\LintReport\ReportWrapperInterface $reportWrapper */
        $reportWrapper = $result["{$assetNamePrefix}report"];
        $this->tester->assertSame(
            $expectedReport,
            $reportWrapper->getReport(),
            '$reportWrapper equals with jar',
        );

        /** @var \Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput $output */
        $output = $this->container->get('output');
        $stdOutput = $output->output;

        $this->tester->assertSame(
            $expectedReport,
            json_decode($stdOutput, true),
            'stdOutput same',
        );
    }

    public function testRunFailed(): void
    {
        $exitCode = 1;
        $report = [
            [
                'filePath' => 'a.js',
                'messages' => [
                    [
                        'ruleId' => 'r1',
                        'severity' => 1,
                        'message' => 'm1',
                        'column' => 'c1',
                        'nodeType' => 'nt1',
                        'source' => 's1',
                    ],
                ],
                'errorCount' => 0,
                'warningCount' => 1,
            ],
        ];
        $reportJson = json_encode($report);

        DummyProcess::$prophecy[] = [
            'exitCode' => $exitCode,
            'stdOutput' => $reportJson,
        ];

        $options = [
            'workingDirectory' => 'my-working-dir',
            'format' => 'json',
            'failOn' => 'warning',
        ];
        $this->task->setOptions($options);
        $result = $this->task->run();

        $this->tester->assertSame($exitCode, $result->getExitCode());

        $assetNamePrefix = $options['assetNamePrefix'] ?? '';

        /** @var \Sweetchuck\Robo\ESLint\LintReportWrapper\ReportWrapper $reportWrapper */
        $reportWrapper = $result["{$assetNamePrefix}report"];
        $this->tester->assertSame($report, $reportWrapper->getReport());
    }
}
