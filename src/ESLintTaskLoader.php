<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint;

use League\Container\ContainerAwareInterface;
use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;

trait ESLintTaskLoader
{
    /**
     * @return \Sweetchuck\Robo\ESLint\Task\ESLintRunFiles|\Robo\Collection\CollectionBuilder
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
     * @return \Sweetchuck\Robo\ESLint\Task\ESLintRunInput|\Robo\Collection\CollectionBuilder
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
