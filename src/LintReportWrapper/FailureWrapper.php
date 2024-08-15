<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\LintReportWrapper;

use Sweetchuck\LintReport\FailureWrapperInterface;

class FailureWrapper implements FailureWrapperInterface
{

    /**
     * @var array<string, mixed>
     */
    protected array $failure = [];

    /**
     * @param array<string, mixed> $failure
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

    public function severity(): string
    {
        return ReportWrapper::severity($this->failure['severity']);
    }

    public function source(): string
    {
        return $this->failure['ruleId'];
    }

    public function line(): int
    {
        return $this->failure['line'];
    }

    public function column(): int
    {
        return $this->failure['column'];
    }

    public function message(): string
    {
        return $this->failure['message'];
    }
}
