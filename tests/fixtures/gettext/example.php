<?php

// Test noop(s) with dynamic variable interpolation

noop("Hello noop 1");
noop("Hello noop 2");
noop("Hello noop 3");

foreach (['noop 1', 'noop 2', 'noop 3'] as $noop) {
    echo t("Hello {$noop}");
    echo "\n";
}

// Test regular gettext without and with interpolation

echo t("Hello t");
echo "\n";

echo t("Hello t %s", 'interpolation');
echo "\n";

echo t('Hello t %name%', [
  '%name%' => 'complex interpolation'
]);
echo "\n";

// Test plural gettext without and with interpolation
// Some languages have many plurals
// Careful ! 0 is usually plural in languages, it reflects on the tests

foreach ([0, 1, 8] as $quantity) {
    echo n("Hello singular n", "Hello plural n", $quantity);
    echo "\n";

    echo n('Hello singular n %s', 'Hello plural n %s', $quantity, 'interpolation');
    echo "\n";

    echo n('Hello singular n %name1%', 'Hello plural n %name2%', $quantity, [
      '%name1%' => 'complex interpolation singular',
      '%name2%' => 'complex interpolation plural'
    ]);
    echo "\n";
}

// Test context gettext without and with interpolation

echo p("p context", "Hello p");
echo "\n";

echo p("p context", "Hello p %s", "interpolation");
echo "\n";

echo p("p context", "Hello p %name%", [
    '%name%' => 'complex interpolation'
]);
echo "\n";

// Test plural and context gettext without and with interpolation

foreach ([0, 1, 8] as $quantity) {
    echo np("np context", "Hello singular np", "Hello plural np", $quantity);
    echo "\n";

    echo np("np context", "Hello singular np %s", "Hello plural np %s", $quantity, 'interpolation');
    echo "\n";

    echo np("np context", 'Hello singular np %name1%', 'Hello plural np %name2%', $quantity, [
      '%name1%' => 'complex interpolation singular',
      '%name2%' => 'complex interpolation plural'
    ]);
    echo "\n";
}
