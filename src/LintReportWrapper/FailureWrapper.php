<?php

namespace Cheppers\Robo\ESLint\LintReportWrapper;

use Cheppers\LintReport\FailureWrapperInterface;
use Cheppers\LintReport\ReportWrapperInterface;

/**
 * Class FileWrapper.
 *
 * @package Cheppers\LintReport\Wrapper\ESLint
 */
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
     * @return string
     */
    public function severity()
    {
        return ReportWrapper::severity($this->failure['severity']);
    }

    /**
     * @return string
     */
    public function source()
    {
        return $this->failure['ruleId'];
    }

    /**
     * @return int
     */
    public function line()
    {
        return $this->failure['line'];
    }

    /**
     * @return int
     */
    public function column()
    {
        return $this->failure['column'];
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->failure['message'];
    }
}
