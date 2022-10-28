<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Unit;

use Sweetchuck\Robo\ESLint\Tests\UnitTester;
use Sweetchuck\Robo\ESLint\Utils;
use Codeception\Test\Unit;

class UtilsTest extends Unit
{
    protected UnitTester $tester;

    public function casesMergeReports(): array
    {
        $a = [
            [
                'filePath' => 'a.js',
                'messages' => [
                    [
                        'ruleId' => 'no-undef',
                        'severity' => 2,
                        'message' => "'a' is not defined.",
                        'line' => 2,
                        'column' => 1,
                        'nodeType' => 'Identifier',
                        'source' => 'a = 5;'
                    ],
                ],
                'errorCount' => 1,
                'warningCount' => 0,
            ],
        ];
        $b = [
            [
                'filePath' => 'b.js',
                'messages' => [
                    [
                        'ruleId' => 'no-undef',
                        'severity' => 2,
                        'message' => "'a' is not defined.",
                        'line' => 2,
                        'column' => 1,
                        'nodeType' => 'Identifier',
                        'source' => 'a = 5;'
                    ],
                ],
                'errorCount' => 1,
                'warningCount' => 0,
            ],
        ];

        return [
            'empty' => [[], []],
            'one' => [$a, [$a]],
            'two' => [[$a[0], $b[0]], [$a, $b]],
        ];
    }

    /**
     * @dataProvider casesMergeReports
     */
    public function testMergeReports(array $expected, array $reports): void
    {
        $this->tester->assertSame($expected, Utils::mergeReports($reports));

        if (count($reports) > 1) {
            $this->tester->assertSame(
                $expected,
                call_user_func_array(Utils::class . '::mergeReports', $reports),
            );
        }
    }
}
