<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\LintReportWrapper;

use Sweetchuck\LintReport\ReportWrapperInterface;

class ReportWrapper implements ReportWrapperInterface
{
    /**
     * @var string[]
     */
    protected static array $severityMap = [
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

    protected array $report = [];

    protected ?int $numOfErrors = null;

    protected ?int $numOfWarnings = null;

    public function __construct(array $report = null)
    {
        if ($report !== null) {
            $this->setReport($report);
        }
    }

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

    public function numOfErrors(): int
    {
        $this->initNumOfAny();

        return $this->numOfErrors;
    }

    public function numOfWarnings(): int
    {
        $this->initNumOfAny();

        return $this->numOfWarnings;
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
