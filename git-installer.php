<?php

/*
Plugin Name: Git Installer (Alpha)
Plugin URI: https://bitbucket.org/sayhellogmbh/shp_git-installer
Description: Install and Update Plugins and Themes from Github, Gitlab and Bitbucket
Author: Nico Martin - mail@nico.dev
Author URI: https://nico.dev
Version: 0.0.1
Text Domain: shgi
Domain Path: /languages
*/

defined('ABSPATH') or die();

add_action('init', function () {
    load_plugin_textdomain('shgi', false, basename(dirname(__FILE__)) . '/languages');
});

require_once 'src/Helpers.php';
require_once 'src/Plugin.php';
require_once 'src/Assets.php';
require_once 'src/Settings.php';
require_once 'src/AdminPage.php';
require_once 'src/Package/GitPackages.php';
require_once 'src/Package/provider/Provider.php';
require_once 'src/Package/provider/Github.php';
require_once 'src/Package/provider/Gitlab.php';
require_once 'src/Package/provider/Bitbucket.php';

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

sayhelloGitInstaller()->GitPackages = new SayHello\GitInstaller\Package\GitPackages();
sayhelloGitInstaller()->GitPackages->run();
