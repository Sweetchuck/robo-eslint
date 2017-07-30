<?php

namespace Sweetchuck\Robo\ESLint\LintReportWrapper;

use Sweetchuck\LintReport\ReportWrapperInterface;

class ReportWrapper implements ReportWrapperInterface
{
    /**
     * @var string[]
     */
    protected static $severityMap = [
        0 => ReportWrapperInterface::SEVERITY_OK,
        1 => ReportWrapperInterface::SEVERITY_WARNING,
        2 => ReportWrapperInterface::SEVERITY_ERROR,
    ];

    /**
     * @return string[]
     */
    public static function severityMap(): array
    {
        return static::$severityMap;
    }

    public static function severity(int $severity): string
    {
        return static::$severityMap[$severity];
    }

    /**
     * @var array
     */
    protected $report = [];

    /**
     * @var int|null
     */
    protected $numOfErrors = null;

    /**
     * @var int|null
     */
    protected $numOfWarnings = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $report = null)
    {
        if ($report !== null) {
            $this->setReport($report);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getReport(): array
    {
        return $this->report;
    }

    /**
     * {@inheritdoc}
     */
    public function setReport(array $report)
    {
        $this->report = $report;
        $this->numOfErrors = null;
        $this->numOfWarnings = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function countFiles(): int
    {
        return count($this->report);
    }

    /**
     * {@inheritdoc}
     */
    public function yieldFiles()
    {
        foreach ($this->getReport() as $item) {
            yield $item['filePath'] => new FileWrapper($item);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function numOfErrors(): int
    {
        $this->initNumOfAny();

        return $this->numOfErrors;
    }

    /**
     * {@inheritdoc}
     */
    public function numOfWarnings(): int
    {
        $this->initNumOfAny();

        return $this->numOfWarnings;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * @return $this
     */
    protected function initNumOfAny()
    {
        if ($this->numOfErrors === null) {
            $this->numOfErrors = 0;
            $this->numOfWarnings = 0;
            foreach ($this->getReport() as $file) {
                $this->numOfErrors += $file['errorCount'];
                $this->numOfWarnings += $file['warningCount'];
            }
        }

        return $this;
    }
}
