<?php

namespace Cheppers\Robo\ESLint;

class Utils
{
    /**
     * @param string $path
     *
     * @return bool
     */
    public static function isAbsolutePath($path)
    {
        return strpos($path, DIRECTORY_SEPARATOR) === 0;
    }

    /**
     * @param array $reports
     *
     * @return array
     */
    public static function mergeReports(array $reports)
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
