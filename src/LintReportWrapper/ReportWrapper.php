<?php

namespace Cheppers\Robo\ESLint\LintReportWrapper;

use Cheppers\LintReport\ReportWrapperInterface;

/**
 * Class ReportWrapper.
 *
 * @package Cheppers\LintReport\Wrapper\ESLint
 */
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
    public static function severityMap()
    {
        return static::$severityMap;
    }

    /**
     * @param int $severity
     *
     * @return string
     */
    public static function severity($severity)
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
     * @return array
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @param array $report
     *
     * @return $this
     */
    public function setReport($report)
    {
        $this->report = $report;
        $this->numOfErrors = null;
        $this->numOfWarnings = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function countFiles()
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
    public function numOfErrors()
    {
        $this->initNumOfAny();

        return $this->numOfErrors;
    }

    /**
     * {@inheritdoc}
     */
    public function numOfWarnings()
    {
        $this->initNumOfAny();

        return $this->numOfWarnings;
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
