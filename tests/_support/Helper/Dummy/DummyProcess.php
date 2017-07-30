<?php

namespace Sweetchuck\Robo\ESLint\Test\Helper\Dummy;

use Symfony\Component\Process\Process;

class DummyProcess extends Process
{

    /**
     * @var array
     */
    public static $prophecy = [];

    /**
     * @var static[]
     */
    public static $instances = null;

    public static function reset()
    {
        static::$prophecy = [];
        static::$instances = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $commandline,
        $cwd = null,
        array $env = null,
        $input = null,
        $timeout = 60,
        array $options = []
    ) {
        parent::__construct($commandline, $cwd, $env, $input, $timeout, $options);

        static::$instances[] = $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run($callback = null)
    {
        $index = array_search($this, static::$instances);

        return static::$prophecy[$index]['exitCode'];
    }

    /**
     * {@inheritdoc}
     */
    public function getExitCode()
    {
        $index = array_search($this, static::$instances);

        return static::$prophecy[$index]['exitCode'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOutput()
    {
        $index = array_search($this, static::$instances);

        return static::$prophecy[$index]['stdOutput'];
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorOutput()
    {
        $index = array_search($this, static::$instances);

        return static::$prophecy[$index]['stdError'];
    }
}
