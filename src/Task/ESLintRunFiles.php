<?php

namespace Cheppers\Robo\ESLint\Task;

use Cheppers\Robo\ESLint\LintReportWrapper\ReportWrapper;
use Robo\Contract\CommandInterface;
use Robo\Result;
use Symfony\Component\Process\Process;

class ESLintRunFiles extends ESLintRun implements CommandInterface
{
}
