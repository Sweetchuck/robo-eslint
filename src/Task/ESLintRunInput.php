<?php

namespace Sweetchuck\Robo\ESLint\Task;

use Sweetchuck\Robo\ESLint\Utils;

class ESLintRunInput extends ESLintRun
{
    // region Properties
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
    // endregion

    // region Option - stdinFilename.
    /**
     * @var string
     */
    protected $stdinFilename = '';

    public function getStdinFilename(): string
    {
        return $this->stdinFilename;
    }

    /**
     * @return $this
     */
    public function setStdinFilename(string $path)
    {
        $this->stdinFilename = $path;

        return $this;
    }
    // endregion

    /**
     * {@inheritdoc}
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

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
        $files = $this->getFiles();
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
    public function getCommand(): string
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
    protected function getCommandOptions(): array
    {
        return [
            'stdin' => true,
            'stdinFilename' => $this->currentFile['fileName'] ?: $this->getStdinFilename(),
        ] + parent::getCommandOptions();
    }
}
