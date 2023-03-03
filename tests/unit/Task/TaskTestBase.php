<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Unit\Task;

use Codeception\Test\Unit;
use Codeception\Stub;
use League\Container\Container as LeagueContainer;
use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Config\Config as RoboConfig;
use Robo\Robo;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcessHelper;
use Sweetchuck\Robo\ESLint\Tests\Helper\Dummy\DummyTaskBuilder;
use Sweetchuck\Robo\ESLint\Tests\UnitTester;
use Symfony\Component\Console\Application as SymfonyApplication;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput;

abstract class TaskTestBase extends Unit
{
    protected ContainerInterface $container;

    protected RoboConfig $config;

    protected CollectionBuilder $builder;

    protected UnitTester $tester;

    /**
     * @var \Sweetchuck\Robo\ESLint\Task\ESLintRun
     */
    protected $task;

    protected DummyTaskBuilder $taskBuilder;

    /**
     * @inheritdoc
     */
    public function _before()
    {
        parent::_before();

        Robo::unsetContainer();
        DummyProcess::reset();

        $this->container = new LeagueContainer();
        $application = new SymfonyApplication('Sweetchuck - Robo ESLint', '2.0.0');
        $application->getHelperSet()->set(new DummyProcessHelper(), 'process');
        $this->config = new RoboConfig();
        $input = null;
        $output = new DummyOutput([
            'verbosity' => DummyOutput::VERBOSITY_DEBUG,
        ]);

        Robo::configureContainer($this->container, $application, $this->config, $input, $output);

        $this->builder = CollectionBuilder::create($this->container, null);
        $this->taskBuilder = new DummyTaskBuilder();
        $this->taskBuilder->setContainer($this->container);
        $this->taskBuilder->setBuilder($this->builder);
        $this->taskBuilder->setOutput($output);
        $this->taskBuilder->setLogger($this->container->get('logger'));

        $this->initTask();
    }

    protected function initTask(array $properties = []): static
    {
        $cb = $this->initTaskCreate();
        $task = $cb->getCollectionBuilderCurrentTask();

        $output = $this->container->get('output');

        $properties += [
            'processClass' => DummyProcess::class,
            'container' => $this->container,
        ];

        /** @var \Sweetchuck\Robo\ESLint\Task\ESLintRun $task */
        $task = Stub::copy($task, $properties);
        $this->task = Stub::copy(
            $cb,
            [
                'currentTask' => $task,
                'container' => $this->container,
            ],
        );

        $task->setOutput($output);
        $this->task->setOutput($output);
        $this->task->setContainer($this->container);

        return $this;
    }

    abstract protected function initTaskCreate(): CollectionBuilder;
}
