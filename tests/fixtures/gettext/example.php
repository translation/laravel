<?php

// Test noop(s) with dynamic variable interpolation

noop__("Hello noop__ 1");
noop__("Hello noop__ 2");
noop__("Hello noop__ 3");

foreach (['noop__ 1', 'noop__ 2', 'noop__ 3'] as $noop) {
    echo t__("Hello {$noop}");
    echo "\n";
}

// Test regular gettext without and with interpolation

echo t__("Hello t__");
echo "\n";

echo t__("Hello t__ %s", 'interpolation');
echo "\n";

echo t__('Hello t__ %name%', [
  '%name%' => 'complex interpolation'
]);
echo "\n";

// Test plural gettext without and with interpolation
// Some languages have many plurals
// Careful ! 0 is usually plural in languages, it reflects on the tests

foreach ([0, 1, 8] as $quantity) {
    echo n__("Hello singular n__", "Hello plural n__", $quantity);
    echo "\n";

    echo n__('Hello singular n__ %s', 'Hello plural n__ %s', $quantity, 'interpolation');
    echo "\n";

    echo n__('Hello singular n__ %name1%', 'Hello plural n__ %name2%', $quantity, [
      '%name1%' => 'complex interpolation singular',
      '%name2%' => 'complex interpolation plural'
    ]);
    echo "\n";
}

// Test context gettext without and with interpolation

echo p__("p__ context", "Hello p__");
echo "\n";

echo p__("p__ context", "Hello p__ %s", "interpolation");
echo "\n";

echo p__("p__ context", "Hello p__ %name%", [
    '%name%' => 'complex interpolation'
]);
echo "\n";

// Test plural and context gettext without and with interpolation

foreach ([0, 1, 8] as $quantity) {
    echo np__("np__ context", "Hello singular np__", "Hello plural np__", $quantity);
    echo "\n";

    echo np__("np__ context", "Hello singular np__ %s", "Hello plural np__ %s", $quantity, 'interpolation');
    echo "\n";

    echo np__("np__ context", 'Hello singular np__ %name1%', 'Hello plural np__ %name2%', $quantity, [
      '%name1%' => 'complex interpolation singular',
      '%name2%' => 'complex interpolation plural'
    ]);
    echo "\n";
}
