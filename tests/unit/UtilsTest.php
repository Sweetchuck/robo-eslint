<?php

use Cheppers\Robo\ESLint\Utils;

// @codingStandardsIgnoreStart
class UtilsTest extends \Codeception\Test\Unit
// @codingStandardsIgnoreEnd
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function casesIsAbsolutePath()
    {
        return [
            'empty string' => [false, ''],
            'dot simple' => [false, '.'],
            'dot double' => [false, '..'],
            'relative' => [false, 'a'],
            'absolute' => [true, '/'],
        ];
    }

    /**
     * @dataProvider casesIsAbsolutePath
     *
     * @param bool $expected
     * @param string $path
     */
    public function testIsAbsolutePath($expected, $path)
    {
        $this->tester->assertEquals($expected, Utils::isAbsolutePath($path));
    }

    /**
     * @return array
     */
    public function casesMergeReports()
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
     *
     * @param array $expected
     * @param array $reports
     */
    public function testMergeReports(array $expected, array $reports)
    {
        $this->tester->assertEquals($expected, Utils::mergeReports($reports));

        if (count($reports) > 1) {
            $this->tester->assertEquals(
                $expected,
                call_user_func_array(Utils::class . '::mergeReports', $reports)
            );
        }
    }
}
