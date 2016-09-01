<?php

use \PHPUnit_Framework_Assert as Assert;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * @return $this
     */
    public function clearTheReportsDir()
    {
        $reportsDir = "tests/_data/reports";
        if (is_dir($reportsDir)) {
            $finder = new \Symfony\Component\Finder\Finder();
            $finder->in($reportsDir);
            foreach ($finder->files() as $file) {
                unlink($file->getPathname());
            }
        }

        return $this;
    }

    /**
     * @param string $taskName
     *
     * @return $this
     */
    public function runRoboTask($taskName)
    {
        $cmd = sprintf(
            'cd tests/_data && ../../bin/robo %s',
            escapeshellarg($taskName)
        );

        return $this->runShellCommand($cmd);
    }

    /**
     * @param string $fileName
     *
     * @return $this
     */
    public function seeAValidJsonFile($fileName)
    {
        $fileNameFull = "tests/_data/$fileName";
        Assert::assertTrue(
            file_exists($fileNameFull),
            "File exists: '$fileNameFull'"
        );

        Assert::assertNotNull(
            json_decode(file_get_contents($fileNameFull)),
            "JSON file is valid: '$fileNameFull'"
        );

        return $this;
    }
}
