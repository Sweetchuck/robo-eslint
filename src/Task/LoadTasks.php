<?php

namespace Cheppers\Robo\ESLint\Task;

/**
 * Class LoadTasks.
 *
 * @package Cheppers\Robo\ESLint\Task
 */
trait LoadTasks
{
    /**
     * Wrapper for eslint.
     *
     * @param array $options
     *   Key-value pairs of options.
     * @param string[] $paths
     *   File paths.
     *
     * @return \Cheppers\Robo\ESLint\Task\Run
     *   A lint runner task instance.
     */
    protected function taskESLintRun(array $options = [], array $paths = [])
    {
        return $this->task(Run::class, $options, $paths);
    }
}
