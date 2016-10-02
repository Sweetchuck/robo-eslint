<?php

namespace Cheppers\Robo\ESLint\Task;

use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

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
        /** @var \Cheppers\Robo\ESLint\Task\Run $task */
        $task = $this->task(Run::class, $options, $paths);
        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        if ($this instanceof OutputAwareInterface) {
            $task->setOutput($this->output());
        }

        return $task;
    }
}
