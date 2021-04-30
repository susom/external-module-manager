<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit33064e2f1884764b926687ea3845c4ec
{
    public static $prefixLengthsPsr4 = array(
        'F' =>
            array(
                'Firebase\\JWT\\' => 13,
            ),
    );

    public static $prefixDirsPsr4 = array(
        'Firebase\\JWT\\' =>
            array(
                0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
            ),
    );

    public static $classMap = array(
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit33064e2f1884764b926687ea3845c4ec::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit33064e2f1884764b926687ea3845c4ec::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit33064e2f1884764b926687ea3845c4ec::$classMap;

        }, null, ClassLoader::class);
    }
}
