<?php

namespace Cheppers\Robo\ESLint\Task;

use Cheppers\Robo\ESLint\LintReportWrapper\ReportWrapper;
use Robo\Contract\CommandInterface;
use Robo\Result;
use Symfony\Component\Process\Process;

/**
 * Class Run.
 *
 * Assert mapping:
 *   - report: Parsed JSON lint report.
 *
 * @package Cheppers\Robo\ESLint\Task
 */
class ESLintRunFiles extends ESLintRun implements CommandInterface
{
}
