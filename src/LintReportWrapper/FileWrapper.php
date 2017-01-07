<?php

namespace Cheppers\Robo\ESLint\LintReportWrapper;

use Cheppers\LintReport\FileWrapperInterface;
use Cheppers\LintReport\ReportWrapperInterface;

class FileWrapper implements FileWrapperInterface
{
    /**
     * @var array
     */
    protected $item = [];

    /**
     * @var array
     */
    public $stats = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $file)
    {
        $this->item = $file + [
            'filePath' => '',
            'errorCount' => '',
            'warningCount' => '',
            'messages' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function filePath()
    {
        return $this->item['filePath'];
    }

    /**
     * {@inheritdoc}
     */
    public function numOfErrors()
    {
        return $this->item['errorCount'];
    }

    /**
     * {@inheritdoc}
     */
    public function numOfWarnings()
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

    /**
     * {@inheritdoc}
     */
    public function stats()
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


    /**
     * @return string
     */
    public function highestSeverity()
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
