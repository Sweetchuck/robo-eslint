<?php

namespace Sweetchuck\Robo\ESLint\Tests\Unit;

use Sweetchuck\AssetJar\AssetJar;
use Sweetchuck\Robo\ESLint\Task\ESLintRunInput;
use Codeception\Util\Stub;
use Helper\Dummy\Output as DummyOutput;
use Helper\Dummy\Process as DummyProcess;
use Robo\Robo;
use UnitTester;

class ESLintRunInputTest extends \Codeception\Test\Unit
{
    protected static function getMethod(string $name): \ReflectionMethod
    {
        $class = new \ReflectionClass(ESLintRunInput::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @var UnitTester
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

    public function testGetSetStdinFilename(): void
    {
        $task = new ESLintRunInput();
        $this->tester->assertEquals(null, $task->getStdinFilename());

        $task = new ESLintRunInput(['stdinFilename' => 'a.js']);
        $this->tester->assertEquals('a.js', $task->getStdinFilename());

        $task->setStdinFilename('b.js');
        $this->tester->assertEquals('b.js', $task->getStdinFilename());
    }

    public function casesGetCommand(): array
    {
        return [
            'empty' => [
                "echo -n '' | eslint --stdin",
                [],
            ],
            'file' => [
                "echo -n '' | eslint --stdin --stdin-filename 'a.js'",
                [
                    'stdinFilename' => 'a.js',
                ],
            ],
            'content' => [
                "echo -n 'var a = 42;' | eslint --stdin --stdin-filename 'c.js'",
                [],
                [
                    'currentFile' => [
                        'fileName' => 'c.js',
                        'content' => 'var a = 42;',
                    ],
                ]
            ],
            'command' => [
                "git show :c.js | eslint --stdin --stdin-filename 'c.js'",
                [],
                [
                    'currentFile' => [
                        'fileName' => 'c.js',
                        'content' => null,
                        'command' => 'git show :c.js',
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options, array $properties = []): void
    {
        $options += ['eslintExecutable' => 'eslint'];
        /** @var \Sweetchuck\Robo\ESLint\Task\ESLintRunInput $task */
        $task = Stub::construct(
            ESLintRunInput::class,
            [$options, []],
            $properties
        );

        $this->tester->assertEquals($expected, $task->getCommand());
    }

    public function casesGetJarValueOrLocal(): array
    {
        return [
            'without jar' => [
                ['a.js', 'b.js'],
                'files',
                ['files' => ['a.js', 'b.js']],
                [],
            ],
            'with jar' => [
                ['c.js', 'd.js'],
                'files',
                [
                    'files' => ['a.js', 'b.js'],
                    'assetJarMapping' => ['files' => ['l1', 'l2']],
                ],
                [
                    'l1' => [
                        'l2' => ['c.js', 'd.js'],
                    ],
                ],
            ],
            'non-exists' => [
                null,
                'non-exists',
                [
                    'files' => ['a.js', 'b.js'],
                    'assetJarMapping' => ['files' => ['l1', 'l2']],
                ],
                [
                    'l1' => [
                        'l2' => ['c.js', 'd.js'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetJarValueOrLocal
     */
    public function testGetJarValueOrLocal(
        $expected,
        string $itemName,
        array $options,
        array $jarValue
    ): void {
        /** @var \Sweetchuck\Robo\ESLint\Task\ESLintRunInput $task */
        $task = Stub::construct(
            ESLintRunInput::class,
            [$options],
            []
        );
        $task->setAssetJar(new AssetJar($jarValue));

        $this->tester->assertEquals(
            $expected,
            static::getMethod('getJarValueOrLocal')->invoke($task, $itemName)
        );
    }

    public function casesRun(): array
    {
        $files = [
            'empty.js' => [],
            'warning.js' => [
                'filePath' => 'warning.js',
                'messages' => [
                    [
                        'ruleId' => 'no-undef',
                        'severity' => 1,
                        'message' => "'a' is not defined.",
                        'line' => 2,
                        'column' => 1,
                        'nodeType' => 'Identifier',
                        'source' => 'a = 5;'
                    ],
                ],
                'errorCount' => 0,
                'warningCount' => 1,
            ],
            'error.js' => [
                'filePath' => 'error.js',
                'messages' => [
                    [
                        'ruleId' => 'no-undef',
                        'severity' => 2,
                        'message' => "'a' is not defined.",
                        'line' => 2,
                        'column' => 1,
                        'nodeType' => 'Identifier',
                        'source' => 'a = 5;'
                    ],
                ],
                'errorCount' => 1,
                'warningCount' => 0,
            ],
        ];

        return [
            'empty' => [
                [
                    'exitCode' => 0,
                    'report' => [],
                    'files' => [],
                ],
                [
                    'format' => 'json',
                    'failOn' => 'warning',
                ],
                [],
            ],
            'w0 never' => [
                [
                    'exitCode' => 0,
                    'report' => [$files['warning.js'], $files['warning.js']],
                ],
                [
                    'format' => 'json',
                    'failOn' => 'never',
                    'files' => [
                        'w1.js' => '',
                        'w2.js' => '',
                    ],
                ],
                [
                    'w1.js' => [
                        'lintExitCode' => 1,
                        'lintStdOutput' => json_encode([$files['warning.js']], true),
                        'report' => [$files['warning.js']],
                    ],
                    'w2.js' => [
                        'lintExitCode' => 1,
                        'lintStdOutput' => json_encode([$files['warning.js']], true),
                        'report' => [$files['warning.js']],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRun
     */
    public function testRun(array $expected, array $options, array $files, array $properties = []): void
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $mainStdOutput = new DummyOutput();

        $properties += ['processClass' => DummyProcess::class];

        /** @var \Sweetchuck\Robo\ESLint\Task\ESLintRunInput $task */
        $task = Stub::construct(
            ESLintRunInput::class,
            [$options, []],
            $properties
        );

        $processIndex = count(DummyProcess::$instances);
        foreach ($files as $file) {
            DummyProcess::$prophecy[$processIndex] = [
                'exitCode' => $file['lintExitCode'],
                'stdOutput' => $file['lintStdOutput'],
            ];

            $processIndex++;
        }

        $task->setLogger($container->get('logger'));
        $task->setOutput($mainStdOutput);

        $result = $task->run();

        $this->tester->assertEquals($expected['exitCode'], $result->getExitCode());

        /** @var \Sweetchuck\LintReport\ReportWrapperInterface $reportWrapper */
        $reportWrapper = $result['report'];
        $this->tester->assertEquals($expected['report'], $reportWrapper->getReport());
    }
}
