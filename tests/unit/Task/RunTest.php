<?php

use Cheppers\Robo\ESLint\Task\Run as ESLintTask;
use Codeception\Util\Stub;
use Robo\Robo;

/**
 * Class RunTest.
 */
// @codingStandardsIgnoreStart
class RunTest extends \Codeception\Test\Unit
{
    // @codingStandardsIgnoreEnd

    use \Cheppers\Robo\ESLint\Task\LoadTasks;
    use \Robo\TaskAccessor;
    use \Robo\Common\BuilderAwareTrait;

    /**
     * @var \League\Container\Container
     */
    protected $container = null;

    // @codingStandardsIgnoreStart
    protected function _before()
    {
        // @codingStandardsIgnoreEnd
        $this->container = new \League\Container\Container();
        Robo::setContainer($this->container);
        Robo::configureContainer($this->container);
    }

    /**
     * @return \League\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return array
     */
    public function casesBuildCommand()
    {
        return [
            'basic' => [
                'node_modules/.bin/eslint',
                [],
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
                ['ext' => ''],
                [],
            ],
            'ext-string' => [
                "node_modules/.bin/eslint --ext 'js'",
                ['ext' => 'js'],
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
                ['rulesDir' => ''],
                [],
            ],
            'rulesDir-string' => [
                "node_modules/.bin/eslint --rulesdir 'my-dir'",
                ['rulesDir' => 'my-dir'],
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
            'maxWarnings-false' => [
                'node_modules/.bin/eslint',
                ['maxWarnings' => false],
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
            'paths-empty' => [
                "node_modules/.bin/eslint",
                ['paths' => []],
                [],
            ],
            'paths-string' => [
                "node_modules/.bin/eslint -- 'foo'",
                ['paths' => 'foo'],
                [],
            ],
            'paths-vector' => [
                "node_modules/.bin/eslint -- 'foo' 'bar' 'baz'",
                ['paths' => ['foo', 'bar', 'baz']],
                [],
            ],
            'paths-assoc' => [
                "node_modules/.bin/eslint -- 'a' 'd'",
                [
                    'paths' => [
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
                "node_modules/.bin/eslint --max-warnings '1' --output-file 'of' --color --no-inline-config -- 'a' 'b'",
                [
                    'maxWarnings' => 1,
                    'outputFile' => 'of',
                    'color' => true,
                    'noInlineConfig' => true,
                    'paths' => ['a', 'b'],
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider casesBuildCommand
     *
     * @param string $expected
     * @param array $options
     * @param array $paths
     */
    public function testBuildCommand($expected, array $options, array $paths)
    {
        $eslint = new ESLintTask($options, $paths);
        static::assertEquals($expected, $eslint->buildCommand());
    }

    public function testExitCodeConstants()
    {
        static::assertEquals(0, ESLintTask::EXIT_CODE_OK);
        static::assertEquals(1, ESLintTask::EXIT_CODE_ERROR);
    }

    /**
     * @return array
     */
    public function casesGetTaskExitCode()
    {
        return [
            'never-ok' => [
                ESLintTask::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                ],
                ESLintTask::EXIT_CODE_OK,
            ],
            'never-error' => [
                ESLintTask::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                ],
                ESLintTask::EXIT_CODE_ERROR,
            ],
            'warning-ok' => [
                ESLintTask::EXIT_CODE_OK,
                [
                    'failOn' => 'warning',
                ],
                ESLintTask::EXIT_CODE_OK,
            ],
            'warning-error' => [
                ESLintTask::EXIT_CODE_ERROR,
                [
                    'failOn' => 'warning',
                ],
                ESLintTask::EXIT_CODE_ERROR,
            ],
            'error-ok' => [
                ESLintTask::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                ],
                ESLintTask::EXIT_CODE_OK,
            ],
            'error-error' => [
                ESLintTask::EXIT_CODE_ERROR,
                [
                    'failOn' => 'error',
                ],
                ESLintTask::EXIT_CODE_ERROR,
            ],
        ];
    }

    /**
     * @dataProvider casesGetTaskExitCode
     *
     * @param int $expected
     * @param array $options
     * @param int $exit_code
     */
    public function testGetTaskExitCode($expected, $options, $exit_code)
    {
        /** @var ESLintTask $ESLintTask */
        $ESLintTask = Stub::construct(
            ESLintTask::class,
            [$options, []],
            ['exitCode' => $exit_code]
        );

        static::assertEquals($expected, $ESLintTask->getTaskExitCode());
    }

    /**
     * @return array
     */
    public function casesRun()
    {
        return [
            'without asset jar' => [
                0,
                'my-dummy-output',
                false,
            ],
            'with_asset_jar-success' => [
                0,
                [],
                true,
            ],
            'with_asset_jar-fail' => [
                1,
                ['file-01.ts' => []],
                true,
            ],
        ];
    }

    /**
     * This way cannot be tested those cases when the lint process failed.
     *
     * @dataProvider casesRun
     *
     * @param int $exitCode
     * @param string $stdOutput
     * @param bool $withJar
     */
    public function testRun($exitCode, $stdOutput, $withJar)
    {
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['ESLintRun', 'report']],
            'format' => 'json',
        ];

        /** @var ESLintTask $task */
        $task = Stub::construct(
            ESLintTask::class,
            [$options, []],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        $output = new \Helper\Dummy\Output();
        \Helper\Dummy\Process::$exitCode = $exitCode;
        \Helper\Dummy\Process::$stdOutput = $withJar ? json_encode($stdOutput) : $stdOutput;

        //$task->setConfig(Robo::config());
        $task->setLogger($this->container->get('logger'));
        $task->setOutput($output);
        $asset_jar = null;
        if ($withJar) {
            $asset_jar = new \Cheppers\AssetJar\AssetJar();
            $task->setAssetJar($asset_jar);
        }

        $result = $task->run();

        static::assertEquals($exitCode, $result->getExitCode(), 'Exit code');
        static::assertEquals(
            $options['workingDirectory'],
            \Helper\Dummy\Process::$instance->getWorkingDirectory(),
            'Working directory'
        );

        if ($withJar) {
            static::assertEquals(
                $stdOutput,
                $asset_jar->getValue(['ESLintRun', 'report']),
                'Output equals'
            );
        } else {
            static::assertContains(
                $stdOutput,
                $output->output,
                'Output contains'
            );
        }
    }

    public function testRunFailed()
    {
        $exit_code = 1;
        $std_output = '{"foo": "bar"}';
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['ESLintRun', 'report']],
            'format' => 'json',
        ];

        /** @var ESLintTask $task */
        $task = Stub::construct(
            ESLintTask::class,
            [$options, []],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        \Helper\Dummy\Process::$exitCode = $exit_code;
        \Helper\Dummy\Process::$stdOutput = $std_output;

        $task->setConfig(Robo::config());
        $task->setLogger($this->container->get('logger'));
        $asset_jar = new \Cheppers\AssetJar\AssetJar();
        $task->setAssetJar($asset_jar);

        $result = $task->run();

        static::assertEquals($exit_code, $result->getExitCode());
        static::assertEquals(
            $options['workingDirectory'],
            \Helper\Dummy\Process::$instance->getWorkingDirectory()
        );

        static::assertEquals(['foo' => 'bar'], $asset_jar->getValue(['ESLintRun', 'report']));
    }
}
