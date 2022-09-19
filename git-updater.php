<?php

/*
Plugin Name: Git Updater
Plugin URI: https://bitbucket.org/sayhellogmbh/shp_git-updater
Description: Install and Update Plugins and Themes from Github, Gitlab and Bitbucket
Author: Nico Martin - Say Hello GmbH
Author URI: https://nico.dev
Version: 0.0.1
Text Domain: shgu
Domain Path: /languages
*/

defined('ABSPATH') or die();

add_action('init', function () {
    load_plugin_textdomain('shgu', false, basename(dirname(__FILE__)) . '/languages');
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

function sayhelloGitUpdater(): \SayHello\GitUpdater\Plugin
{
    return SayHello\GitUpdater\Plugin::getInstance(__FILE__);
}

sayhelloGitUpdater()->Assets = new SayHello\GitUpdater\Assets();
sayhelloGitUpdater()->Assets->run();

sayhelloGitUpdater()->Settings = new SayHello\GitUpdater\Settings();
sayhelloGitUpdater()->Settings->run();

sayhelloGitUpdater()->AdminPage = new SayHello\GitUpdater\AdminPage();
sayhelloGitUpdater()->AdminPage->run();

/**
 * Packages
 */

sayhelloGitUpdater()->GitPackages = new SayHello\GitUpdater\Package\GitPackages();
sayhelloGitUpdater()->GitPackages->run();



