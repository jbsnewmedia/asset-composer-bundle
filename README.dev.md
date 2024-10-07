# composer install

when `PHP Fatal error:  Allowed memory size` then

    COMPOSER_MEMORY_LIMIT=-1 composer install

# phpstan.neon

    includes:
        - phpstan-global.neon
    parameters:
        editorUrl: 'phpstorm://open?file=%relFile&line=%line'
        editorUrlTitle: '{{editorPath}}/%%relFile%%:%%line%%'

{{editorPath}}, PhpStorm project dir, right click on project dir, `Copy Path/Reference` > `Absolute Path` and paste it there.
