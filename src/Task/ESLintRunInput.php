<?php

namespace Cheppers\Robo\ESLint\Task;

use Cheppers\Robo\ESLint\Utils;

/**
 * @package Cheppers\Robo\ESLint\Task
 */
class ESLintRunInput extends ESLintRun
{
    //region Properties
    /**
     * {@inheritdoc}
     */
    protected $addFilesToCliCommand = false;

    /**
     * @var array
     */
    protected $currentFile = [
        'fileName' => '',
        'content' => '',
    ];
    //endregion

    //region Option - stdinFilename
    /**
     * @var string|null
     */
    protected $stdinFilename = null;

    /**
     * @return mixed|null
     */
    public function getStdinFilename()
    {
        return $this->stdinFilename;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setStdinFilename($value)
    {
        $this->stdinFilename = $value;

        return $this;
    }
    //endregion

    /**
     * {@inheritdoc}
     */
    public function __construct(array $options = [], array $files = [])
    {
        parent::__construct($options, $files);

        $this->flagOptions['stdin'] = 'stdin';
        $this->simpleOptions['stdinFilename'] = 'stdin-filename';
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'stdinFilename':
                    $this->setStdinFilename($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runHeader()
    {
        $files = $this->filterEnabled($this->getFiles());
        $this->printTaskInfo(
            'ESLint: lint {count} files from StdInput',
            [
                'count' => count($files),
            ]
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runLint()
    {
        $reports = [];
        $files = $this->getJarValueOrLocal('files');
        $backupFailOn = $this->getFailOn();

        $this->setFailOn('never');
        foreach ($files as $fileName => $file) {
            if (!is_array($file)) {
                $file = [
                    'fileName' => $fileName,
                    'content' => $file,
                ];
            }

            $this->currentFile = $file;

            $this->setStdinFilename($fileName);
            $lintExitCode = $this->lintExitCode;
            parent::runLint();
            $this->lintExitCode = max($lintExitCode, $this->lintExitCode);

            if ($this->report) {
                $reports[] = $this->report;
            }
        }
        $this->setFailOn($backupFailOn);

        $this->report = Utils::mergeReports($reports);
        $this->reportRaw = json_encode($this->report);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand()
    {
        if ($this->currentFile['content'] === null) {
            // @todo Handle the different working directories.
            $echo = $this->currentFile['command'];
        } else {
            $echo = sprintf('echo -n %s', escapeshellarg($this->currentFile['content']));
        }

        return $echo . ' | ' . parent::getCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function buildCommandOptions()
    {
        return [
            'stdin' => true,
            'stdinFilename' => $this->currentFile['fileName'] ?: $this->getStdinFilename(),
        ] + parent::buildCommandOptions();
    }

    /**
     * @param string $itemName
     *
     * @return mixed|null
     */
    protected function getJarValueOrLocal($itemName)
    {
        $map = $this->getAssetJarMap($itemName);
        if ($map) {
            $value = $this->getAssetJarValue($itemName, $keyExists);
            if ($keyExists) {
                return $value;
            }
        }

        switch ($itemName) {
            case 'files':
                return $this->getFiles();
        }

        return null;
    }
}
