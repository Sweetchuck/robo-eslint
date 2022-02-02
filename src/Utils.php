<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint;

class Utils
{
    /**
     * @todo Use webmozart/path-utils
     *
     * @deprecated Use webmozart/path-utils
     */
    public static function isAbsolutePath(string $path): bool
    {
        return strpos($path, DIRECTORY_SEPARATOR) === 0;
    }

    public static function mergeReports(array $reports): array
    {
        if (func_num_args() > 1) {
            $reports = func_get_args();
        }

        if (!$reports) {
            return [];
        }

        // @todo Support the same file in more than one report.
        return call_user_func_array('array_merge', $reports);
    }
}
