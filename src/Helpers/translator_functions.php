<?php

###
# Inspired by https://github.com/oscarotero/Gettext/blob/master/src/translator_functions.php
###

/**
 * Noop, marks the string for translation but returns it unchanged.
 */
if (!function_exists('noop')) {
    function noop($original)
    {
        return $original;
    }
}

if (!function_exists('noop_')) {
    function noop_($original)
    {
        return $original;
    }
}

if (!function_exists('noop__')) {
    function noop__($original)
    {
        return $original;
    }
}

/**
 * Returns the translation of a string without interpolation.
 */
if (!function_exists('_')) {
    function _($original)
    {
        return gettext($original);
    }
}

/**
 * Returns the translation of a string with interpolation.
 */
if (!function_exists('i_')) {
    function i_($original)
    {
        $text = gettext($original);

        if (func_num_args() === 1) {
            return $text;
        }

        $args = array_slice(func_get_args(), 1);

        return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
    }
}

if (!function_exists('i__')) {
    function i__($original)
    {
        $text = gettext($original);

        if (func_num_args() === 1) {
            return $text;
        }

        $args = array_slice(func_get_args(), 1);

        return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
    }
}

/**
 * Returns the singular/plural translation of a string without interpolation.
 */
if (!function_exists('n_')) {
    function n_($original, $plural, $value)
    {
        return ngettext($original, $plural, $value);
    }
}

/**
 * Returns the singular/plural translation of a string with interpolation.
 */
if (!function_exists('n__')) {
    function n__($original, $plural, $value)
    {
        $text = ngettext($original, $plural, $value);

        if (func_num_args() === 3) {
            return $text;
        }

        $args = array_slice(func_get_args(), 3);

        return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
    }
}

/**
 * Returns the translation of a string in a specific context without interpolation.
 * (pgettext is not created by GetText, we need to add it manually using dcgettext)
 */
if (!function_exists('pgettext')) {
    function pgettext($msg_ctxt, $msgid) {
        $msg_ctxt_id = "{$msg_ctxt}\004{$msgid}";
        $translation = dcgettext('app', $msg_ctxt_id, LC_MESSAGES);

        if ($translation == $msg_ctxt_id) {
            return $msgid;
        }
        else {
            return $translation;
        }
    }
}

/**
 * Returns the translation of a string in a specific context without interpolation.
 */
if (!function_exists('p_')) {
    function p_($context, $original)
    {
        return pgettext($context, $original);
    }
}

/**
 * Returns the translation of a string in a specific context with interpolation
 */
if (!function_exists('p__')) {
    function p__($context, $original)
    {
        $text = pgettext($context, $original);

        if (func_num_args() === 2) {
            return $text;
        }

        $args = array_slice(func_get_args(), 2);

        return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
    }
}

/**
 * Returns the singular/plural translation of a string in a specific context without interpolation.
 * (npgettext is not created by GetText, we need to add it manually using dcnpgettext)
 */
if (!function_exists('npgettext')) {
    function npgettext($msg_ctxt, $msgid, $msgid_plural, $n) {
        $msg_ctxt_id = "{$msg_ctxt}\004{$msgid}";
        $translation = dcngettext('app', $msg_ctxt_id, $msgid_plural, $n, LC_MESSAGES);

        if ($translation == $msg_ctxt_id || $translation == $msgid_plural) {
            return $n == 1 ? $msgid : $msgid_plural;
        }
        else {
            return $translation;
        }
    }
}

/**
 * Returns the singular/plural translation of a string in a specific context without interpolation
 */
if (!function_exists('np_')) {
    function np_($context, $original, $plural, $value)
    {
        return npgettext($context, $original, $plural, $value);
    }
}

/**
 * Returns the singular/plural translation of a string in a specific context with interpolation
 */
if (!function_exists('np__')) {
    function np__($context, $original, $plural, $value)
    {
        $text = npgettext($context, $original, $plural, $value);

        if (func_num_args() === 4) {
            return $text;
        }

        $args = array_slice(func_get_args(), 4);

        return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
    }
}
