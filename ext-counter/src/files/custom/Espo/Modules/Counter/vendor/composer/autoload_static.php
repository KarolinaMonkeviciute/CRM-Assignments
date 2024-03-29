<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit20564eba068e249add4b0f6be854be8e
{
    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Espo\\ApiClient\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Espo\\ApiClient\\' => 
        array (
            0 => __DIR__ . '/..' . '/espocrm/php-espo-api-client/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit20564eba068e249add4b0f6be854be8e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit20564eba068e249add4b0f6be854be8e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit20564eba068e249add4b0f6be854be8e::$classMap;

        }, null, ClassLoader::class);
    }
}
