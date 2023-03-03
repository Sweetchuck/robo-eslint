<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Unit\Task;

use Robo\Collection\CollectionBuilder;
use Sweetchuck\Robo\ESLint\Task\ESLintRunInput;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;

/**
 * @covers \Sweetchuck\Robo\ESLint\Task\ESLintRunInput
 * @covers \Sweetchuck\Robo\ESLint\Task\ESLintRun
 * @covers \Sweetchuck\Robo\ESLint\ESLintTaskLoader
 *
 * @property \Sweetchuck\Robo\ESLint\Task\ESLintRunInput $task
 */
class ESLintRunInputTest extends TaskTestBase
{
    protected function initTaskCreate(): CollectionBuilder
    {
        return $this->taskBuilder->taskESLintRunInput();
    }

    public function testGetSetStdinFilename(): void
    {
        $this->tester->assertSame('', $this->task->getStdinFilename());

        $this->initTask();
        $this->task->setOptions(['stdinFilename' => 'a.js']);
        $this->tester->assertSame('a.js', $this->task->getStdinFilename());

        $this->initTask();
        $this->task->setStdinFilename('b.js');
        $this->tester->assertSame('b.js', $this->task->getStdinFilename());
    }

    public function casesGetCommand(): array
    {
        return [
            'file' => [
                "echo -n 'var a = 42;' | eslint --stdin --stdin-filename 'a.js'",
                [
                    'stdinFilename' => 'a.js',
                    'files' => [
                        'c.js' => [
                            'content' => 'var a = 42;',
                        ],
                    ],
                ],
            ],
            'content' => [
                "echo -n 'var a = 42;' | eslint --stdin --stdin-filename 'c.js'",
                [
                    'files' => [
                        'c.js' => [
                            'fileName' => 'c.js',
                            'content' => 'var a = 42;',
                        ],
                    ],
                ],
                [],
            ],
            'command' => [
                "git show :c.js | eslint --stdin --stdin-filename 'c.js'",
                [
                    'files' => [
                        'c.js' => [
                            'fileName' => 'c.js',
                            'content' => null,
                            'command' => 'git show :c.js',
                        ],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options): void
    {
        $files = $options['files'] ?? [];
        $this->initTask([
            'currentFile' => reset($files) ?: [],
        ]);
        $options += [
            'eslintExecutable' => 'eslint',
        ];
        $this->task->setOptions($options);
        $this->tester->assertSame($expected, $this->task->getCommand());
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
    public function testRun(array $expected, array $options, array $files): void
    {
        $processIndex = count(DummyProcess::$instances);
        foreach ($files as $file) {
            DummyProcess::$prophecy[$processIndex] = [
                'exitCode' => $file['lintExitCode'],
                'stdOutput' => $file['lintStdOutput'],
            ];

            $processIndex++;
        }

        $this->task->setOptions($options);
        $result = $this->task->run();

        $this->tester->assertSame($expected['exitCode'], $result->getExitCode());

        /** @var \Sweetchuck\LintReport\ReportWrapperInterface $reportWrapper */
        $reportWrapper = $result['report'];
        $this->tester->assertSame($expected['report'], $reportWrapper->getReport());
    }
}
