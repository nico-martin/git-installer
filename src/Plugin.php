<?php

namespace SayHello\GitUpdater;

class Plugin
{

    private static $instance;
    public $name = '';
    public $version = '';
    public $prefix = '';
    public $api_namespace = '';
    public $api_namespace_live = '';
    public $debug = false;
    public $file = '';

    public $upload_dir = '';
    public $upload_url = '';

    public $option_key = 'shgu_data';

    public $AdminPage;
    public $Assets;
    public $Settings;
    public $PasswordProtected;
    public $GitPackages;

    public static function getInstance($file)
    {
        if (!isset(self::$instance) && !(self::$instance instanceof Plugin)) {
            self::$instance = new Plugin();

            if (get_option(sayhelloGitUpdater()->option_key)) {
                $data = get_option(sayhelloGitUpdater()->option_key);
            } elseif (function_exists('get_plugin_data')) {
                $data = get_plugin_data($file);
            } else {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                $data = get_plugin_data($file);
            }

            self::$instance->name = $data['Name'];
            self::$instance->version = $data['Version'];

            self::$instance->prefix = 'shgu';
            self::$instance->api_namespace = 'sayhello-wp-manage/v1';
            self::$instance->debug = true;
            self::$instance->file = $file;

            self::$instance->run();
        }

        return self::$instance;
    }

    public function run()
    {
        add_action('plugins_loaded', [$this, 'loadPluginTextdomain']);
        add_action('admin_init', [$this, 'updatePluginData']);
        register_deactivation_hook(sayhelloGitUpdater()->file, [$this, 'deactivate']);
        register_activation_hook(sayhelloGitUpdater()->file, [$this, 'activate']);

        add_filter('shgu/PluginStrings', [$this, 'pluginStrings']);
    }

    /**
     * Load translation files from the indicated directory.
     */
    public function loadPluginTextdomain()
    {
        load_plugin_textdomain(
            'progressive-wp',
            false,
            dirname(plugin_basename(sayhelloGitUpdater()->file)) . '/languages'
        );
    }

    /**
     * Update Assets Data
     */
    public function updatePluginData()
    {

        $db_data = get_option(sayhelloGitUpdater()->option_key);
        $file_data = get_plugin_data(sayhelloGitUpdater()->file);

        if (!$db_data || version_compare($file_data['Version'], $db_data['Version'], '>')) {

            sayhelloGitUpdater()->name = $file_data['Name'];
            sayhelloGitUpdater()->version = $file_data['Version'];

            update_option(sayhelloGitUpdater()->option_key, $file_data);
            if (!$db_data) {
                do_action('shgu_on_first_activate');
            } else {
                do_action('shgu_on_update', $db_data['Version'], $file_data['Version']);
            }
        }
    }

    public function activate()
    {
        do_action('shgu_on_activate');
    }

    public function deactivate()
    {
        do_action('shgu_on_deactivate');
        delete_option(sayhelloGitUpdater()->option_key);
    }

    public function pluginStrings($strings)
    {
        $strings['plugin.name'] = sayhelloGitUpdater()->name;

        return $strings;
    }
}
