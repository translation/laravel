<?php

###
# Inspired by https://github.com/oscarotero/Gettext/blob/master/src/translator_functions.php
###

use Armandsar\LaravelTranslationio\TranslationIO;

/**
 * Returns the translation of a string.
 *
 * @param string $original
 *
 * @return string
 */
function t__($original)
{
    $text = TranslationIO::$current->gettext($original);

    if (func_num_args() === 1) {
        return $text;
    }

    $args = array_slice(func_get_args(), 1);

    return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
}

/**
 * Noop, marks the string for translation but returns it unchanged.
 *
 * @param string $original
 *
 * @return string
 */
function noop__($original)
{
    return $original;
}

/**
 * Returns the singular/plural translation of a string.
 *
 * @param string $original
 * @param string $plural
 * @param string $value
 *
 * @return string
 */
function n__($original, $plural, $value)
{
    $text = TranslationIO::$current->ngettext($original, $plural, $value);

    if (func_num_args() === 3) {
        return $text;
    }

    $args = array_slice(func_get_args(), 3);

    return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
}

/**
 * Returns the translation of a string in a specific context.
 *
 * @param string $context
 * @param string $original
 *
 * @return string
 */
function p__($context, $original)
{
    $text = TranslationIO::$current->pgettext($context, $original);

    if (func_num_args() === 2) {
        return $text;
    }

    $args = array_slice(func_get_args(), 2);

    return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
}

/**
 * Returns the singular/plural translation of a string in a specific context.
 *
 * @param string $context
 * @param string $original
 * @param string $plural
 * @param string $value
 *
 * @return string
 */
function np__($context, $original, $plural, $value)
{
    $text = TranslationIO::$current->npgettext($context, $original, $plural, $value);

    if (func_num_args() === 4) {
        return $text;
    }

    $args = array_slice(func_get_args(), 4);

    return is_array($args[0]) ? strtr($text, $args[0]) : vsprintf($text, $args);
}
