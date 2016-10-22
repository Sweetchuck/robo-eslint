<?php

namespace Cheppers\Robo\ESLint;

use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

/**
 * Class LoadTasks.
 *
 * @package Cheppers\Robo\ESLint\Task
 */
trait LoadESLintTasks
{
    /**
     * @param array $options
     * @param array $paths
     *
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunFiles
     *   A lint runner task instance.
     */
    protected function taskESLintRunFiles(array $options = [], array $paths = [])
    {
        /** @var \Cheppers\Robo\ESLint\Task\ESLintRunFiles $task */
        $task = $this->task(Task\ESLintRunFiles::class, $options, $paths);
        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        if ($this instanceof OutputAwareInterface) {
            $task->setOutput($this->output());
        }

        return $task;
    }

    /**
     * @param array $options
     * @param array $paths
     *
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunInput
     */
    protected function taskESLintRunInput(array $options = [], array $paths = [])
    {
        /** @var \Cheppers\Robo\ESLint\Task\ESLintRunInput $task */
        $task = $this->task(Task\ESLintRunInput::class, $options, $paths);
        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        if ($this instanceof OutputAwareInterface) {
            $task->setOutput($this->output());
        }

        return $task;
    }
}
