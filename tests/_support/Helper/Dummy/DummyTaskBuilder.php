<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Helper\Dummy;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\TaskIO;
use Robo\Contract\BuilderAwareInterface;
use Robo\State\StateAwareTrait;
use Robo\TaskAccessor;
use Sweetchuck\Robo\ESLint\ESLintTaskLoader;

class DummyTaskBuilder implements
    BuilderAwareInterface,
    ContainerAwareInterface,
    OutputAwareInterface
{
    use TaskAccessor;
    use ContainerAwareTrait;
    use StateAwareTrait;
    use TaskIO;

    use ESLintTaskLoader {
        taskESLintRunFiles as public;
        taskESLintRunInput as public;
    }

    public function collectionBuilder(): CollectionBuilder
    {
        return CollectionBuilder::create($this->getContainer(), null);
    }
}
