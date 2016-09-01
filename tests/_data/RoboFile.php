<?php
use Robo\Robo;

/**
 * Class RoboFile.
 */
class RoboFile extends \Robo\Tasks
    // @codingStandardsIgnoreEnd
{
    use \Cheppers\Robo\ESLint\Task\LoadTasks;

    /**
     * @return \Robo\Collection\CollectionBuilder
     */
    public function lintStylishStdout()
    {
        /** @var \Robo\Collection\CollectionBuilder $cb */
        $cb = $this->collectionBuilder();
        $cb->addTaskList([
            'eslint' => $this
                ->taskESLintRun()
                ->setOutput($this->output())
                ->format('stylish')
                ->paths(['samples/*']),
        ]);

        return $cb;
    }

    /**
     * @return \Robo\Collection\CollectionBuilder
     */
    public function lintJsonFile()
    {
        /** @var \Robo\Collection\CollectionBuilder $cb */
        $cb = $this->collectionBuilder();
        $cb->addTaskList([
            'eslint' => $this
                ->taskESLintRun()
                ->setOutput($this->output())
                ->format('json')
                ->outputFile('reports/eslint-samples.json')
                ->paths(['samples/*']),
        ]);

        return $cb;
    }
}
