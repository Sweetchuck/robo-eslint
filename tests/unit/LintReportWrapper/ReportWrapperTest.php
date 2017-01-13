<?php

namespace Cheppers\Robo\ESLint\Tests\Unit;

use Cheppers\Robo\ESLint\LintReportWrapper\ReportWrapper;

class ReportWrapperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function casesReports(): array
    {
        return [
            'ok:no-files' => [
                'expected' => [
                    'countFiles' => 0,
                    'numOfErrors' => 0,
                    'numOfWarnings' => 0,
                    'highestSeverity' => 'ok',
                ],
                'report' => [],
            ],
            'ok:one-file' => [
                'expected' => [
                    'countFiles' => 1,
                    'numOfErrors' => 0,
                    'numOfWarnings' => 0,
                    'highestSeverity' => 'ok',
                ],
                'report' => [
                    [
                        'filePath' => 'a.js',
                        'messages' => [],
                        'errorCount' => 0,
                        'warningCount' => 0,
                        '__highestSeverity' => 'ok',
                        '__stats' => [
                            'severity' => 'ok',
                            'has' => [
                                'ok' => false,
                                'warning' => false,
                                'error' => false,
                            ],
                            'source' => [],
                        ],
                    ],
                ],
            ],
            'warning' => [
                'expected' => [
                    'countFiles' => 1,
                    'numOfErrors' => 0,
                    'numOfWarnings' => 2,
                    'highestSeverity' => 'warning',
                ],
                'report' => [
                    [
                        'filePath' => 'a.js',
                        'messages' => [
                            [
                                'ruleId' => 'no-undef',
                                'severity' => 1,
                                'message' => '"a" is not defined.',
                                'line' => 2,
                                'column' => 1,
                                'nodeType' => 'Identifier',
                                'source' => 'a = 5;',
                            ],
                            [
                                'ruleId' => 'no-undef',
                                'severity' => 1,
                                'message' => '"a" is not defined.',
                                'line' => 3,
                                'column' => 1,
                                'nodeType' => 'Identifier',
                                'source' => 'a = 5;',
                            ],
                        ],
                        'errorCount' => 0,
                        'warningCount' => 2,
                        '__highestSeverity' => 'warning',
                        '__stats' => [
                            'severity' => 'warning',
                            'has' => [
                                'ok' => false,
                                'warning' => true,
                                'error' => false,
                            ],
                            'source' => [
                                'no-undef' => [
                                    'severity' => 'warning',
                                    'count' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'error' => [
                'expected' => [
                    'countFiles' => 1,
                    'numOfErrors' => 1,
                    'numOfWarnings' => 1,
                    'highestSeverity' => 'error',
                ],
                'report' => [
                    [
                        'filePath' => 'a.js',
                        'messages' => [
                            [
                                'ruleId' => 'my-error',
                                'severity' => 2,
                                'message' => '"a" is not defined.',
                                'line' => 2,
                                'column' => 1,
                                'nodeType' => 'Identifier',
                                'source' => 'a = 5;',
                            ],
                            [
                                'ruleId' => 'my-warning',
                                'severity' => 1,
                                'message' => '"a" is not defined.',
                                'line' => 3,
                                'column' => 1,
                                'nodeType' => 'Identifier',
                                'source' => 'a = 5;',
                            ],
                        ],
                        'errorCount' => 1,
                        'warningCount' => 1,
                        '__highestSeverity' => 'error',
                        '__stats' => [
                            'severity' => 'error',
                            'has' => [
                                'ok' => false,
                                'warning' => true,
                                'error' => true,
                            ],
                            'source' => [
                                'my-error' => [
                                    'severity' => 'error',
                                    'count' => 1,
                                ],
                                'my-warning' => [
                                    'severity' => 'warning',
                                    'count' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesReports
     */
    public function testAll(array $expected, array $report): void
    {
        $rw = new ReportWrapper($report);

        $this->tester->assertEquals($expected['countFiles'], $rw->countFiles());
        $this->tester->assertEquals($expected['numOfErrors'], $rw->numOfErrors());
        $this->tester->assertEquals($expected['numOfWarnings'], $rw->numOfWarnings());
        $this->tester->assertEquals($expected['highestSeverity'], $rw->highestSeverity());

        $sm = [
            0 => 'ok',
            1 => 'warning',
            2 => 'error',
        ];

        /**
         * @var string $filePath
         * @var \Cheppers\Robo\ESLint\LintReportWrapper\FileWrapper $fw
         */
        foreach ($rw->yieldFiles() as $filePath => $fw) {
            $file = array_shift($report);
            $this->tester->assertEquals($file['filePath'], $fw->filePath());
            $this->tester->assertEquals($file['errorCount'], $fw->numOfErrors());
            $this->tester->assertEquals($file['warningCount'], $fw->numOfWarnings());
            $this->tester->assertEquals($file['__highestSeverity'], $fw->highestSeverity());
            $this->tester->assertEquals($file['__stats'], $fw->stats());

            /**
             * @var int $i
             * @var \Cheppers\LintReport\FailureWrapperInterface $failureWrapper
             */
            foreach ($fw->yieldFailures() as $i => $failureWrapper) {
                $message = $file['messages'][$i];
                $this->tester->assertEquals($sm[$message['severity']], $failureWrapper->severity());
                $this->tester->assertEquals($message['ruleId'], $failureWrapper->source());
                $this->tester->assertEquals($message['line'], $failureWrapper->line());
                $this->tester->assertEquals($message['column'], $failureWrapper->column());
                $this->tester->assertEquals($message['message'], $failureWrapper->message());
            }
        }
    }
}
