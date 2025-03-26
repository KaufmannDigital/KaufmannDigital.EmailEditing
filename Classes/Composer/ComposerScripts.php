<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Composer;


use Composer\Installer\PackageEvent;
use Composer\Script\Event;

class ComposerScripts
{

    public static function postPackageInstall(PackageEvent $event)
    {
        $composer = $event->getComposer();

        $package = $composer->getRepositoryManager()->findPackage('spatie/mjml-php', '*');
        $packagePath = $composer->getInstallationManager()->getInstallPath($package);

        var_dump(shell_exec(sprintf('cd %s && npm install', $packagePath)));

    }

}