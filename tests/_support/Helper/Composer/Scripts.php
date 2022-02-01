<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint\Tests\Helper\Composer;

use Composer\Script\Event as ComposerEvent;
use Symfony\Component\Process\Process;

class Scripts
{
    protected static ?ComposerEvent $event = null;

    protected static \Closure $processCallbackWrapper;

    public static function postInstallCmd(ComposerEvent $event): bool
    {
        $return = [];

        if ($event->isDevMode()) {
            static::init($event);

            $return[] = static::yarnInstall($event);
        }

        return count(array_keys($return, false, true)) === 0;
    }

    public static function postUpdateCmd(ComposerEvent $event): bool
    {
        $return = [];

        if ($event->isDevMode()) {
            static::init($event);
        }

        return count(array_keys($return, false, true)) === 0;
    }

    public static function yarnInstall(ComposerEvent $event): bool
    {
        $return = true;

        if ($event->isDevMode()) {
            static::init($event);

            $cmdPattern = 'cd %s && yarn install';
            $cmdArgs = [
                escapeshellarg('tests/_data'),
            ];

            $process = new Process(
                [
                    'bash',
                    '-c',
                    vsprintf($cmdPattern, $cmdArgs),
                ],
            );
            $exitCode = $process->run(static::$processCallbackWrapper);

            $return = !$exitCode;
        }

        return $return;
    }

    protected static function init(ComposerEvent $event)
    {
        if (static::$event) {
            return;
        }

        static::$event = $event;
        static::$processCallbackWrapper = function (string $type, string $text) {
            static::processCallback($type, $text);
        };
    }

    protected static function processCallback(string $type, string $text)
    {
        if ($type === Process::OUT) {
            static::$event->getIO()->write($text);
        } else {
            static::$event->getIO()->writeError($text);
        }
    }
}
