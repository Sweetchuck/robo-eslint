<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\LintReportWrapper;

use Sweetchuck\LintReport\FailureWrapperInterface;

class FailureWrapper implements FailureWrapperInterface
{
    protected array $failure = [];

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
    public function severity(): string
    {
        return ReportWrapper::severity($this->failure['severity']);
    }

    /**
     * {@inheritdoc}
     */
    public function source(): string
    {
        return $this->failure['ruleId'];
    }

    /**
     * {@inheritdoc}
     */
    public function line(): int
    {
        return $this->failure['line'];
    }

    /**
     * {@inheritdoc}
     */
    public function column(): int
    {
        return $this->failure['column'];
    }

    /**
     * {@inheritdoc}
     */
    public function message(): string
    {
        return $this->failure['message'];
    }
}
