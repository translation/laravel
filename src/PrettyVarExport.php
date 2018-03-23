<?php

namespace Tio\Laravel;

class PrettyVarExport
{
    // code from https://gist.github.com/lithrel/a224edb1ed2975992c73
    public function call($var, array $opts = [])
    {
        $opts = array_merge(['indent' => '', 'tab' => '    ', 'array-align' => false], $opts);
        switch (gettype($var)) {
            case 'array':
                $r = [];
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $lengths = array_map('strlen', array_map('trim', array_keys($var)));
                $maxLength = ($opts['array-align'] && count($lengths) > 0) ? max($lengths) + 2 : 0;
                foreach ($var as $key => $value) {
                    $key = str_replace("'' . \"\\0\" . '*' . \"\\0\" . ", "", $this->call($key));
                    $r[] = $opts['indent'] . $opts['tab']
                        . ($indexed ? '' : str_pad($key, $maxLength) . ' => ')
                        . $this->call($value, array_merge($opts, ['indent' => $opts['indent'] . $opts['tab']]));
                }
                return "[\n" . implode(",\n", $r) . "\n" . $opts['indent'] . "]";
            case 'boolean':
                return $var ? 'true' : 'false';
            case 'NULL':
                return 'null';
            default:
                return var_export($var, true);
        }
    }
}
