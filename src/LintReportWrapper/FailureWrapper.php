<?php

namespace Cheppers\Robo\ESLint\LintReportWrapper;

use Cheppers\LintReport\FailureWrapperInterface;

class FailureWrapper implements FailureWrapperInterface
{
    /**
     * @var array
     */
    protected $failure = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $failure)
    {
        // @todo Validate.
        $this->failure = $failure + [
            'ruleId' => '',
            'severity' => 0,
            'message' => '',
            'line' => 0,
            'column' => 0,
            'nodeType' => '',
            'source' => '',
            'fix' => [
                'range' => [0, 0],
                'text' => '',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function severity()
    {
        return ReportWrapper::severity($this->failure['severity']);
    }

    /**
     * {@inheritdoc}
     */
    public function source()
    {
        return $this->failure['ruleId'];
    }

    /**
     * {@inheritdoc}
     */
    public function line()
    {
        return $this->failure['line'];
    }

    /**
     * {@inheritdoc}
     */
    public function column()
    {
        return $this->failure['column'];
    }

    /**
     * {@inheritdoc}
     */
    public function message()
    {
        return $this->failure['message'];
    }
}
