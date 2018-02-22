<?php

// Test noop(s) with dynamic variable interpolation

noop("Hello noop");
noop_("Hello noop_");
noop__("Hello noop__");

foreach (['noop', 'noop_', 'noop__'] as $noop) {
    echo _("Hello {$noop}");
    echo "\n";
}

// Test regular gettext without and with interpolation

echo gettext("Hello gettext");
echo "\n";

echo _("Hello _");
echo "\n";

echo i_("Hello i_ %s", 'interpolation');
echo "\n";

echo i__("Hello i__ %s", 'interpolation');
echo "\n";

echo i__('Hello i__ %name%', [
  '%name%' => 'complex interpolation'
]);
echo "\n";

// Test plural gettext without and with interpolation

echo ngettext("Hello singular ngettext", "Hello plural ngettext", 2);
echo "\n";

echo n_('Hello singular n_', 'Hello plural n_', 1);
echo "\n";

echo n__('Hello singular n__ %s', 'Hello plural n__ %s', 2, 'interpolation');
echo "\n";

echo n__('Hello singular n__ %name1%', 'Hello plural n__ %name2%', 2, [
  '%name1%' => 'complex interpolation singular',
  '%name2%' => 'complex interpolation plural'
]);
echo "\n";

// Test context gettext without and with interpolation

echo pgettext("pgettext context", "Hello pgettext");
echo "\n";

echo p_("p_ context", "Hello p_");
echo "\n";

echo p__("p__ context", "Hello p__");
echo "\n";

echo p__("p__ context", "Hello p__ %s", "complex interpolation");
echo "\n";

// Test plural and context gettext without and with interpolation

echo npgettext("npgettext context", "Hello npgettext singular", "Hello npgettext plural", 1);
echo "\n";

echo np_("np_ context", "Hello singular np_", "Hello plural np_", 1);
echo "\n";

echo np__("np__ context", "Hello singular np__", "Hello plural np__", 2);
echo "\n";

echo np__("np__ context", 'Hello singular np__ %name1%', 'Hello plural np__ %name2%', 2, [
  '%name1%' => 'complex interpolation singular',
  '%name2%' => 'complex interpolation plural'
]);
