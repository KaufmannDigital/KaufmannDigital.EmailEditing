<?php
declare(strict_types=1);

namespace KaufmannDigital\EmailEditing\Composer;

use Composer\Installer\PackageEvent;

class ComposerScripts
{

    public static function mjmlNpmInitialisation(PackageEvent $event)
    {
        $package = $event->getComposer()->getRepositoryManager()->findPackage('spatie/mjml-php', '*');
        $packagePath = $event->getComposer()->getInstallationManager()->getInstallPath($package);
        $commandResult = shell_exec(sprintf('cd %s && npm install', $packagePath));

        if (is_string($commandResult)) {
            echo "🥳 Successfully installed MJML Node-Modules for package spatie/mjml-php" . PHP_EOL;
        } else {
            throw new \Exception('Failed to install MJML Node-Modules');
        }

    }

}