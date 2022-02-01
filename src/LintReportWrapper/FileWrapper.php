<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\LintReportWrapper;

use Sweetchuck\LintReport\FileWrapperInterface;
use Sweetchuck\LintReport\ReportWrapperInterface;

class FileWrapper implements FileWrapperInterface
{
    protected array $item = [];

    public array $stats = [];

    public function __construct(array $file)
    {
        $this->item = $file + [
            'filePath' => '',
            'errorCount' => '',
            'warningCount' => '',
            'messages' => [],
        ];
    }

    public function filePath(): string
    {
        return $this->item['filePath'];
    }

    public function numOfErrors(): int
    {
        return $this->item['errorCount'];
    }

    public function numOfWarnings(): int
    {
        return $this->item['warningCount'];
    }

    /**
     * {@inheritdoc}
     */
    public function yieldFailures()
    {
        foreach ($this->item['messages'] as $message) {
            yield new FailureWrapper($message);
        }
    }

    public function stats(): array
    {
        if (!$this->stats) {
            $this->stats = [
                'severity' => 0,
                'has' => array_fill_keys(ReportWrapper::severityMap(), false),
                'source' => [],
            ];
            foreach ($this->item['messages'] as $message) {
                if ($this->stats['severity'] < $message['severity']) {
                    $this->stats['severity'] = $message['severity'];
                }

                $severity = ReportWrapper::severity($message['severity']);
                $this->stats['has'][$severity] = true;

                $this->stats['source'] += [
                    $message['ruleId'] => [
                        'severity' => $severity,
                        'count' => 0,
                    ],
                ];
                $this->stats['source'][$message['ruleId']]['count']++;
            }

            $this->stats['severity'] = ReportWrapper::severity($this->stats['severity']);
        }

        return $this->stats;
    }

    public function highestSeverity(): string
    {
        if ($this->numOfErrors()) {
            return ReportWrapperInterface::SEVERITY_ERROR;
        }

        if ($this->numOfWarnings()) {
            return ReportWrapperInterface::SEVERITY_WARNING;
        }

        return ReportWrapperInterface::SEVERITY_OK;
    }
}
