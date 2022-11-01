<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\ESLint;

class Utils
{
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

    /**
     * Converts rule definition into a CLI option.
     *
     * In the examples the values of the --rule options are YAML strings,
     * but the JSON also works.
     *
     * @param array<mixed>|string $rule
     *
     * @link https://eslint.org/docs/latest/user-guide/command-line-interface#--rule
     */
    public static function ruleToOptionValue(string $id, $rule): string
    {
        return json_encode([$id => $rule]);
    }

    public static function getGlobalVariablesAsCliOptions(iterable $globalVariables): array
    {
        $vars = [];
        foreach ($globalVariables as $name => $status) {
            if ($status === false) {
                continue;
            }

            $key = $name . ($status ? ':true' : '');
            $vars[$key] = true;
        }

        return $vars;
    }

    /**
     * The array key is the relevant value and the array value will be a boolean.
     *
     * @param string[]|bool[] $items
     *   Items.
     * @param bool $defaultValue
     *   Default value.
     *
     * @return bool[]
     *   Key is the relevant value, the value is a boolean.
     *
     * @phpstan-return array<string, bool>
     */
    public static function createIncludeList(array $items, bool $defaultValue): array
    {
        $item = reset($items);

        return is_bool($item) ?
            $items
            : array_fill_keys($items, $defaultValue);
    }

    /**
     * @param array<string>|array<string, null|bool> $items
     *
     * @return array<string, null|bool>
     */
    public static function createTriStateList(array $items, ?bool $defaultValue): array
    {
        $item = reset($items);

        return $item === null || is_bool($item) ?
            $items
            : array_fill_keys($items, $defaultValue);
    }
}
