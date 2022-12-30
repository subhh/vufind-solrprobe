<?php

spl_autoload_register(
    function (string $class) {
        if (strpos($class, 'VuFind') === 0) {
            $module = explode('\\', $class)[0];
            $filename = sprintf('%s/vendor/vufind/vufind/module/%s/src/%s.php', __DIR__, $module, str_replace('\\', '/', $class));
            if (file_exists($filename)) {
                require $filename;
            }
        }
    }
);
