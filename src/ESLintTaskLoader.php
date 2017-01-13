<?php

namespace Cheppers\Robo\ESLint;

use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

trait ESLintTaskLoader
{
    /**
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunFiles
     *   A lint runner task instance.
     */
    protected function taskESLintRunFiles(array $options = [])
    {
        /** @var \Cheppers\Robo\ESLint\Task\ESLintRunFiles $task */
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
     * @return \Cheppers\Robo\ESLint\Task\ESLintRunInput
     */
    protected function taskESLintRunInput(array $options = [])
    {
        /** @var \Cheppers\Robo\ESLint\Task\ESLintRunInput $task */
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
