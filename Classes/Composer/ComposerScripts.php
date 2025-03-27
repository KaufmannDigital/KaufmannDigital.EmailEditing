<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Composer;


use Composer\Installer\PackageEvent;
use Composer\Script\Event;

class ComposerScripts
{

    public static function postPackageUpdateAndInstall()
    {
        \Neos\Flow\var_dump("die");die();
    }

}