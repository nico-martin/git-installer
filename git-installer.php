<?php

/*
Plugin Name: Git Installer (Beta)
Plugin URI: https://github.com/SayHelloGmbH/git-installer
Description: Install and Update Plugins and Themes from GitHub, Gitlab and Bitbucket
Author: Nico Martin - mail@nico.dev
Author URI: https://nico.dev
Version: 0.2.2
Text Domain: shgi
Domain Path: /languages
Requires PHP: 7.4
Update URI: https://update.git-installer.com/infos.php?release=latest
*/

defined('ABSPATH') or die();

add_action('init', function () {
    load_plugin_textdomain('shgi', false, basename(dirname(__FILE__)) . '/languages');
});

require_once 'src/Helpers.php';
require_once 'src/FsHelpers.php';
require_once 'src/Plugin.php';
require_once 'src/Assets.php';
require_once 'src/Settings.php';
require_once 'src/AdminPage.php';
require_once 'src/Package/Helpers/GitPackageManagement.php';
require_once 'src/Package/UpdateLog.php';
require_once 'src/Package/GitPackages.php';
require_once 'src/Package/provider/Provider.php';
require_once 'src/Package/provider/Github.php';
require_once 'src/Package/provider/Gitlab.php';
require_once 'src/Package/provider/Bitbucket.php';
require_once 'src/Package/Updater.php';
require_once 'src/Package/Ecosystem.php';

function sayhelloGitInstaller(): \SayHello\GitInstaller\Plugin
{
    return SayHello\GitInstaller\Plugin::getInstance(__FILE__);
}

sayhelloGitInstaller()->Assets = new SayHello\GitInstaller\Assets();
sayhelloGitInstaller()->Assets->run();

sayhelloGitInstaller()->Settings = new SayHello\GitInstaller\Settings();
sayhelloGitInstaller()->Settings->run();

sayhelloGitInstaller()->AdminPage = new SayHello\GitInstaller\AdminPage();
sayhelloGitInstaller()->AdminPage->run();

/**
 * Packages
 */

sayhelloGitInstaller()->UpdateLog = new SayHello\GitInstaller\Package\UpdateLog();
sayhelloGitInstaller()->UpdateLog->run();

sayhelloGitInstaller()->GitPackages = new SayHello\GitInstaller\Package\GitPackages();
sayhelloGitInstaller()->GitPackages->run();

sayhelloGitInstaller()->Updater = new SayHello\GitInstaller\Package\Updater();
sayhelloGitInstaller()->Updater->run();

sayhelloGitInstaller()->Ecosystem = new SayHello\GitInstaller\Package\Ecosystem();
sayhelloGitInstaller()->Ecosystem->run();

require_once 'src/plugin-update-checker-5.0/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://update.git-installer.com/infos.php?release=v0.2.1',
    __FILE__,
    'shgi'
);
