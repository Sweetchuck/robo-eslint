<?php


use Cheppers\AssetJar\AssetJar;
use Cheppers\Robo\ESLint\Task\ESLintRunInput;
use Codeception\Util\Stub;

// @codingStandardsIgnoreStart
class ESLintRunInputTest extends \Codeception\Test\Unit
// @codingStandardsIgnoreEnd
{
    /**
     * @param string $name
     *
     * @return ReflectionMethod
     */
    protected static function getMethod($name)
    {
        $class = new ReflectionClass(ESLintRunInput::class);
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

        \Helper\Dummy\Process::reset();
    }

    public function testGetSetStdinFilename()
    {
        $task = new ESLintRunInput();
        $this->tester->assertEquals(null, $task->getStdinFilename());

        $task = new ESLintRunInput(['stdinFilename' => 'a.js']);
        $this->tester->assertEquals('a.js', $task->getStdinFilename());

        $task->setStdinFilename('b.js');
        $this->tester->assertEquals('b.js', $task->getStdinFilename());
    }

    /**
     * @return array
     */
    public function casesGetCommand()
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
     *
     * @param string $expected
     * @param array $options
     * @param array $properties
     */
    public function testGetCommand($expected, array $options, array $properties = [])
    {
        $options += ['eslintExecutable' => 'eslint'];
        /** @var \Cheppers\Robo\ESLint\Task\ESLintRunInput $task */
        $task = Stub::construct(
            ESLintRunInput::class,
            [$options, []],
            $properties
        );

        $this->tester->assertEquals($expected, $task->getCommand());
    }

    /**
     * @return array
     */
    public function casesGetJarValueOrLocal()
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
     *
     * @param mixed $expected
     * @param string $itemName
     * @param array $options
     * @param array $jarValue
     */
    public function testGetJarValueOrLocal($expected, $itemName, array $options, array $jarValue)
    {
        /** @var \Cheppers\Robo\ESLint\Task\ESLintRunInput $task */
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

    /**
     * @return array
     */
    public function casesRun()
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
     *
     * @param array $expected
     * @param array $options
     * @param array $properties
     */
    public function testRun(array $expected, array $options, array $files, array $properties = [])
    {
        $container = \Robo\Robo::createDefaultContainer();
        \Robo\Robo::setContainer($container);

        $mainStdOutput = new \Helper\Dummy\Output();

        $properties += ['processClass' => \Helper\Dummy\Process::class];

        /** @var \Cheppers\Robo\ESLint\Task\ESLintRunInput $task */
        $task = Stub::construct(
            ESLintRunInput::class,
            [$options, []],
            $properties
        );

        $processIndex = count(\Helper\Dummy\Process::$instances);
        foreach ($files as $file) {
            \Helper\Dummy\Process::$prophecy[$processIndex] = [
                'exitCode' => $file['lintExitCode'],
                'stdOutput' => $file['lintStdOutput'],
            ];

            $processIndex++;
        }

        $task->setLogger($container->get('logger'));
        $task->setOutput($mainStdOutput);

        $result = $task->run();

        $this->tester->assertEquals($expected['exitCode'], $result->getExitCode());

        /** @var \Cheppers\LintReport\ReportWrapperInterface $reportWrapper */
        $reportWrapper = $result['report'];
        $this->tester->assertEquals($expected['report'], $reportWrapper->getReport());
    }
}
