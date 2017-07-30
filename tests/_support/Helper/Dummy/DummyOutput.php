<?php

namespace Sweetchuck\Robo\ESLint\Test\Helper\Dummy;

use Symfony\Component\Console\Output\Output;

class DummyOutput extends Output
{

    /**
     * @var string
     */
    public $output = '';

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        $this->output .= $message . ($newline ? "\n" : '');
    }
}
