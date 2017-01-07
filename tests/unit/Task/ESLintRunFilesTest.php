<?php

namespace Cheppers\Robo\ESLint\Test\Unit;

use Cheppers\Robo\ESLint\Task\ESLintRunFiles;
use Codeception\Util\Stub;
use Helper\Dummy\Output as DummyOutput;
use Helper\Dummy\Process as DummyProcess;
use Robo\Robo;

class ESLintRunFilesTest extends \Codeception\Test\Unit
{
    protected static function getMethod(string $name): \ReflectionMethod
    {
        $class = new \ReflectionClass(ESLintRunFiles::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        DummyProcess::reset();
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
        $task = new ESLintRunFiles($options);

        $this->tester->assertEquals($expectedDirect, $task->getOutputFile());
        $this->tester->assertEquals($expectedReal, $task->getRealOutputFile());
    }

    public function testGetSetLintReporters(): void
    {
        $task = new ESLintRunFiles([
            'lintReporters' => [
                'aKey' => 'aValue',
            ],
        ]);

        $task
            ->addLintReporter('bKey', 'bValue')
            ->addLintReporter('cKey', 'cValue')
            ->removeLintReporter('bKey');

        $this->tester->assertEquals(
            [
                'aKey' => 'aValue',
                'cKey' => 'cValue',
            ],
            $task->getLintReporters()
        );
    }

    public function casesGetCommand(): array
    {
        return [
            'basic' => [
                'node_modules/.bin/eslint',
                [],
                [],
            ],
            'eslintExecutable' => [
                "something/else --config 'foo'",
                [
                    'eslintExecutable' => 'something/else',
                    'configFile' => 'foo'
                ],
                [],
            ],
            'configFile-empty' => [
                'node_modules/.bin/eslint',
                ['configFile' => ''],
                [],
            ],
            'configFile-string' => [
                "node_modules/.bin/eslint --config 'foo'",
                ['configFile' => 'foo'],
                [],
            ],
            'noESLintRc-false' => [
                'node_modules/.bin/eslint',
                ['noESLintRc' => false],
                [],
            ],
            'noESLintRc-true' => [
                'node_modules/.bin/eslint --no-eslintrc',
                ['noESLintRc' => true],
                [],
            ],
            'ext-empty' => [
                'node_modules/.bin/eslint',
                ['ext' => []],
                [],
            ],
            'ext-vector-1' => [
                "node_modules/.bin/eslint --ext '.a'",
                ['ext' => ['.a']],
                [],
            ],
            'ext-vector-multi' => [
                "node_modules/.bin/eslint --ext '.a,.b'",
                ['ext' => ['.a', '.b']],
                [],
            ],
            'ext-assoc' => [
                "node_modules/.bin/eslint --ext '.b,.d'",
                [
                    'ext' => [
                        '.a' => false,
                        '.b' => true,
                        '.c' => false,
                        '.d' => true,
                    ],
                ],
                [],
            ],
            'cache-false' => [
                'node_modules/.bin/eslint',
                ['cache' => false],
                [],
            ],
            'cache-true' => [
                'node_modules/.bin/eslint --cache',
                ['cache' => true],
                [],
            ],
            'cacheLocation-empty' => [
                'node_modules/.bin/eslint',
                ['cacheLocation' => ''],
                [],
            ],
            'cacheLocation-string' => [
                "node_modules/.bin/eslint --cache-location 'my-dir'",
                ['cacheLocation' => 'my-dir'],
                [],
            ],
            'rulesDir-empty' => [
                'node_modules/.bin/eslint',
                ['rulesDir' => []],
                [],
            ],
            'rulesDir-vector' => [
                "node_modules/.bin/eslint --rulesdir 'my-dir-1' --rulesdir 'my-dir-2'",
                ['rulesDir' => ['my-dir-1', 'my-dir-2']],
                [],
            ],
            'rulesDir-assoc' => [
                "node_modules/.bin/eslint --rulesdir 'my-dir-1' --rulesdir 'my-dir-3'",
                [
                    'rulesDir' => [
                        'my-dir-1' => true,
                        'my-dir-2' => false,
                        'my-dir-3' => true,
                    ],
                ],
                [],
            ],
            'ignorePath-empty' => [
                'node_modules/.bin/eslint',
                ['ignorePath' => ''],
                [],
            ],
            'ignorePath-string' => [
                "node_modules/.bin/eslint --ignore-path 'my-dir'",
                ['ignorePath' => 'my-dir'],
                [],
            ],
            'noIgnore-false' => [
                'node_modules/.bin/eslint',
                ['noIgnore' => false],
                [],
            ],
            'notIgnore-true' => [
                'node_modules/.bin/eslint --no-ignore',
                ['noIgnore' => true],
                [],
            ],
            'ignorePattern-empty' => [
                'node_modules/.bin/eslint',
                ['ignorePattern' => ''],
                [],
            ],
            'ignorePattern-string' => [
                "node_modules/.bin/eslint --ignore-pattern 'my-dir'",
                ['ignorePattern' => 'my-dir'],
                [],
            ],
            'quiet-false' => [
                'node_modules/.bin/eslint',
                ['quiet' => false],
                [],
            ],
            'quiet-true' => [
                'node_modules/.bin/eslint --quiet',
                ['quiet' => true],
                [],
            ],
            'maxWarnings-negative' => [
                "node_modules/.bin/eslint --max-warnings '-1'",
                ['maxWarnings' => -1],
                [],
            ],
            'maxWarnings-zero' => [
                "node_modules/.bin/eslint --max-warnings '0'",
                ['maxWarnings' => 0],
                [],
            ],
            'maxWarnings-positive' => [
                "node_modules/.bin/eslint --max-warnings '1'",
                ['maxWarnings' => 1],
                [],
            ],
            'maxWarnings-null' => [
                'node_modules/.bin/eslint',
                ['maxWarnings' => null],
                [],
            ],
            'format-empty' => [
                "node_modules/.bin/eslint",
                ['format' => ''],
                [],
            ],
            'format-string' => [
                "node_modules/.bin/eslint --format 'foo'",
                ['format' => 'foo'],
                [],
            ],
            'outputFile-empty' => [
                'node_modules/.bin/eslint',
                ['outputFile' => ''],
                [],
            ],
            'outputFile-string' => [
                "node_modules/.bin/eslint --output-file 'my-file'",
                ['outputFile' => 'my-file'],
                [],
            ],
            'color-false' => [
                'node_modules/.bin/eslint --no-color',
                ['color' => false],
                [],
            ],
            'color-null' => [
                'node_modules/.bin/eslint',
                ['color' => null],
                [],
            ],
            'color-true' => [
                'node_modules/.bin/eslint --color',
                ['color' => true],
                [],
            ],
            'noInlineConfig-false' => [
                'node_modules/.bin/eslint',
                ['noInlineConfig' => false],
                [],
            ],
            'noInlineConfig-true' => [
                "node_modules/.bin/eslint --no-inline-config",
                ['noInlineConfig' => true],
                [],
            ],
            'files-empty' => [
                "node_modules/.bin/eslint",
                ['files' => []],
                [],
            ],
            'files-vector' => [
                "node_modules/.bin/eslint -- 'foo' 'bar' 'baz'",
                ['files' => ['foo', 'bar', 'baz']],
                [],
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
                [],
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
                [],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options, array $paths): void
    {
        $eslint = new ESLintRunFiles($options, $paths);
        $this->tester->assertEquals($expected, $eslint->getCommand());
    }

    public function testExitCodeConstants(): void
    {
        $this->tester->assertEquals(0, ESLintRunFiles::EXIT_CODE_OK);
        $this->tester->assertEquals(1, ESLintRunFiles::EXIT_CODE_WARNING);
        $this->tester->assertEquals(2, ESLintRunFiles::EXIT_CODE_ERROR);
        $this->tester->assertEquals(3, ESLintRunFiles::EXIT_CODE_INVALID);
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
        /** @var ESLintRunFiles $task */
        $task = Stub::construct(
            ESLintRunFiles::class,
            [['failOn' => $failOn]],
            ['lintExitCode' => $lintExitCode]
        );

        $this->tester->assertEquals(
            $expected,
            static::getMethod('getTaskExitCode')->invokeArgs($task, [$numOfErrors, $numOfWarnings])
        );
    }

    public function casesRun(): array
    {
        return [
            'withoutJar - success' => [
                0,
                [],
                false,
            ],
            'withoutJar - warning' => [
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
                false,
            ],
            'withoutJar - error' => [
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
                false,
            ],
            'withJar - success' => [
                0,
                [],
                true,
            ],
            'withJar - warning' => [
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
                true,
            ],
            'withJar - error' => [
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
                true,
            ],
        ];
    }

    /**
     * This way cannot be tested those cases when the lint process failed.
     *
     * @dataProvider casesRun
     */
    public function testRun(int $expectedExitCode, array $expectedReport, bool $withJar): void
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $mainStdOutput = new DummyOutput();

        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['ESLintRun', 'report']],
            'format' => 'json',
            'failOn' => 'warning',
        ];

        /** @var ESLintRunFiles $task */
        $task = Stub::construct(
            ESLintRunFiles::class,
            [$options, []],
            [
                'processClass' => DummyProcess::class,
            ]
        );

        $processIndex = count(DummyProcess::$instances);
        DummyProcess::$prophecy[$processIndex] = [
            'exitCode' => $expectedExitCode,
            'stdOutput' => json_encode($expectedReport),
        ];

        $task->setLogger($container->get('logger'));
        $task->setOutput($mainStdOutput);

        $assetJar = null;
        if ($withJar) {
            $assetJar = new \Cheppers\AssetJar\AssetJar();
            $task->setAssetJar($assetJar);
        }

        $result = $task->run();

        $this->tester->assertEquals($expectedExitCode, $result->getExitCode(), 'Exit code');
        $this->tester->assertEquals(
            $options['workingDirectory'],
            DummyProcess::$instances[$processIndex]->getWorkingDirectory(),
            'Working directory'
        );

        if ($withJar) {
            /** @var \Cheppers\Robo\ESLint\LintReportWrapper\ReportWrapper $reportWrapper */
            $reportWrapper = $assetJar->getValue(['ESLintRun', 'report']);
            $this->tester->assertEquals(
                $expectedReport,
                $reportWrapper->getReport(),
                'Output equals with jar'
            );
        } else {
            $this->tester->assertEquals(
                $expectedReport,
                json_decode($mainStdOutput->output, true),
                'Output equals without jar'
            );
        }
    }

    public function testRunFailed(): void
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

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
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['ESLintRun', 'report']],
            'format' => 'json',
            'failOn' => 'warning',
        ];

        /** @var ESLintRunFiles $task */
        $task = Stub::construct(
            ESLintRunFiles::class,
            [$options, []],
            [
                'processClass' => DummyProcess::class,
            ]
        );

        $processIndex = count(DummyProcess::$instances);
        DummyProcess::$prophecy[$processIndex] = [
            'exitCode' => $exitCode,
            'stdOutput' => $reportJson,
        ];

        $task->setLogger($container->get('logger'));
        $assetJar = new \Cheppers\AssetJar\AssetJar();
        $task->setAssetJar($assetJar);

        $result = $task->run();

        $this->tester->assertEquals($exitCode, $result->getExitCode());
        $this->tester->assertEquals(
            $options['workingDirectory'],
            DummyProcess::$instances[$processIndex]->getWorkingDirectory()
        );

        /** @var \Cheppers\Robo\ESLint\LintReportWrapper\ReportWrapper $reportWrapper */
        $reportWrapper = $assetJar->getValue(['ESLintRun', 'report']);
        $this->tester->assertEquals($report, $reportWrapper->getReport());
    }
}
