<?php

namespace Sweetchuck\Robo\ESLint;

use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

trait ESLintTaskLoader
{
    /**
     * @return \Sweetchuck\Robo\ESLint\Task\ESLintRunFiles
     *   A lint runner task instance.
     */
    protected function taskESLintRunFiles(array $options = [])
    {
        /** @var \Sweetchuck\Robo\ESLint\Task\ESLintRunFiles $task */
        $task = $this->task(Task\ESLintRunFiles::class, $options);
        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        if ($this instanceof OutputAwareInterface) {
            $task->setOutput($this->output());
        }

        return $task;
    }

    /**
     * @return \Sweetchuck\Robo\ESLint\Task\ESLintRunInput
     */
    protected function taskESLintRunInput(array $options = [])
    {
        /** @var \Sweetchuck\Robo\ESLint\Task\ESLintRunInput $task */
        $task = $this->task(Task\ESLintRunInput::class, $options);
        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        if ($this instanceof OutputAwareInterface) {
            $task->setOutput($this->output());
        }

        return $task;
    }
}
