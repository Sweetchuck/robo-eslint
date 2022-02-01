<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Unit\Task;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Sweetchuck\Robo\ESLint\Task\ESLintRunInput;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Robo\Robo;
use Symfony\Component\Console\Output\OutputInterface;

class ESLintRunInputTest extends Unit
{
    protected static function getMethod(string $name): \ReflectionMethod
    {
        $class = new \ReflectionClass(ESLintRunInput::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @var \Sweetchuck\Robo\ESLint\Tests\UnitTester
     */
    protected $tester;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        DummyProcess::reset();
    }

    public function testGetSetStdinFilename(): void
    {
        $task = new ESLintRunInput();
        $this->tester->assertSame('', $task->getStdinFilename());

        $task = new ESLintRunInput(['stdinFilename' => 'a.js']);
        $this->tester->assertSame('a.js', $task->getStdinFilename());

        $task->setStdinFilename('b.js');
        $this->tester->assertSame('b.js', $task->getStdinFilename());
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

        $this->tester->assertSame($expected, $task->getCommand());
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
                ],
                [
                    'l1' => [
                        'l2' => ['c.js', 'd.js'],
                    ],
                ],
            ],
        ];
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
                        'lintStdOutput' => json_encode([$files['warning.js']]),
                        'report' => [$files['warning.js']],
                    ],
                    'w2.js' => [
                        'lintExitCode' => 1,
                        'lintStdOutput' => json_encode([$files['warning.js']]),
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

        $outputConfig = [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
            'colors' => false,
        ];
        $mainStdOutput = new DummyOutput($outputConfig);

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

        $this->tester->assertSame($expected['exitCode'], $result->getExitCode());

        /** @var \Sweetchuck\LintReport\ReportWrapperInterface $reportWrapper */
        $reportWrapper = $result['report'];
        $this->tester->assertSame($expected['report'], $reportWrapper->getReport());
    }
}
