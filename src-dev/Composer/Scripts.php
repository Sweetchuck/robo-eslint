<?php

namespace Sweetchuck\Robo\ESLint\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;
use Sweetchuck\GitHooks\Composer\Scripts as GitHooks;

class Scripts
{
    /**
     * @var \Composer\Script\Event
     */
    protected static $event;

    /**
     * @var \Closure
     */
    protected static $processCallbackWrapper;

    public static function postInstallCmd(Event $event): bool
    {
        $return = [];

        if ($event->isDevMode()) {
            static::init($event);

            $return[] = GitHooks::deploy($event);
            $return[] = static::npmInstall($event);
        }

        return count(array_keys($return, false, true)) === 0;
    }

    public static function postUpdateCmd(Event $event): bool
    {
        $return = [];

        if ($event->isDevMode()) {
            static::init($event);

            $return[] = GitHooks::deploy($event);
        }

        return count(array_keys($return, false, true)) === 0;
    }

    public static function npmInstall(Event $event): bool
    {
        $return = true;

        if ($event->isDevMode()) {
            static::init($event);

            $cmdPattern = 'cd %s && npm install';
            $cmdArgs = [
                escapeshellarg('tests/_data'),
            ];

            $process = new Process(vsprintf($cmdPattern, $cmdArgs));
            $exitCode = $process->run(static::$processCallbackWrapper);

            $return = !$exitCode;
        }

        return $return;
    }

    protected static function init(Event $event)
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
