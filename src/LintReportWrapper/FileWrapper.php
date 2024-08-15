<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\LintReportWrapper;

use Sweetchuck\LintReport\FileWrapperInterface;
use Sweetchuck\LintReport\ReportWrapperInterface;

class FileWrapper implements FileWrapperInterface
{

    /**
     * @phpstan-var robo-eslint-lint-report-file-full
     */
    protected array $item = [
        'filePath' => '',
        'errorCount' => 0,
        'warningCount' => 0,
        'messages' => [],
    ];

    /**
     * @phpstan-var robo-eslint-lint-report-stats
     */
    public array $stats = [];

    /**
     * @phpstan-param robo-eslint-lint-report-file $file
     */
    public function __construct(array $file)
    {
        $this->item = $file + [
            'filePath' => '',
            'errorCount' => 0,
            'warningCount' => 0,
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
            // @phpstan-ignore-next-line
            yield new FailureWrapper($message);
        }
    }

    /**
     * @phpstan-return robo-eslint-lint-report-stats-full
     */
    public function stats(): array
    {
        if (!$this->stats) {
            $this->stats = [
                'severity' => '',
                'has' => array_fill_keys(ReportWrapper::severityMap(), false),
                'source' => [],
            ];

            $globalSeverity = 0;
            foreach ($this->item['messages'] as $message) {
                if ($globalSeverity < $message['severity']) {
                    $globalSeverity = $message['severity'];
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

            $this->stats['severity'] = ReportWrapper::severity($globalSeverity);
        }

        // @phpstan-ignore-next-line
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
