<?php

$config = require __DIR__.'/vendor/contentful/core/scripts/php-cs-fixer.php';

return $config(
    'the-example-app',
    true,
    ['config', 'src', 'public', 'tests']
);
