<?php

namespace SayHello\GitInstaller;

class Assets
{
    public function run()
    {
        add_action('wp_head', [$this, 'uiJsVars']);
        add_action('wp_enqueue_scripts', [$this, 'addAssets']);
        add_action('admin_enqueue_scripts', [$this, 'addAdminAssets']);
    }

    public function uiJsVars()
    {
        $defaults = [
            'ajaxUrl'             => admin_url('admin-ajax.php'),
            'homeUrl'             => trailingslashit(get_site_url()),
            'pluginPrefix'        => sayhelloGitInstaller()->prefix,
            'generalError'        => __('An unexpected error occured', 'shgi'),
            'restBase'            => trailingslashit(get_rest_url()),
            'restPluginBase'      => trailingslashit(get_rest_url() . sayhelloGitInstaller()->api_namespace),
            'restPluginNamespace' => sayhelloGitInstaller()->api_namespace,
        ];
        $vars     = json_encode(apply_filters('shgi/Assets/FooterJS', $defaults));
        echo '<script>' . PHP_EOL;
        echo "var shgiUiJsVars = {$vars};";
        echo '</script>' . PHP_EOL;
    }

    public function addAssets()
    {
        /*
        $script_version = sayhelloGitInstaller()->version;
        $dir_uri        = trailingslashit(plugin_dir_url(sayhelloGitInstaller()->file));

        wp_enqueue_style(
            sayhelloGitInstaller()->prefix . '-ui-installprompt',
            $dir_uri . 'assets/dist/ui-installprompt.css',
            [],
            $script_version
        );

        wp_enqueue_script(
            sayhelloGitInstaller()->prefix . '-ui-installprompt',
            $dir_uri . 'assets/dist/ui-installprompt.js',
            [],
            $script_version,
            true
        );
        */
    }

    public function addAdminAssets()
    {
        $script_version = sayhelloGitInstaller()->version;
        $dir_uri        = trailingslashit(plugin_dir_url(sayhelloGitInstaller()->file));

        wp_enqueue_media();
        wp_enqueue_style('wp-components');

        wp_enqueue_script('react', $dir_uri . 'assets/react.production.min.js', [], '17', true);
        wp_enqueue_script('react-dom', $dir_uri . 'assets/react-dom.production.min.js', ['react'], '17', true);

        /*
         * we can't use preact if we want to use wp-components
        wp_enqueue_script('preact', $dir_uri . 'assets/preact/preact.min.js', [], '10.5.12', true);
        wp_enqueue_script('preact-hooks', $dir_uri . 'assets/preact/preact-hooks.min.js', ['preact'], '10.5.12', true);
        wp_enqueue_script(
            'preact-compat',
            $dir_uri . 'assets/preact/preact-compat.min.js',
            ['preact', 'preact-hooks'],
            '10.5.12',
            true
        );*/

        wp_enqueue_style(
            sayhelloGitInstaller()->prefix . '-roboto',
            $dir_uri . 'assets/fonts/roboto.css',
            [],
            $script_version
        );

        wp_enqueue_style(
            sayhelloGitInstaller()->prefix . '-admin-style',
            $dir_uri . 'assets/dist/admin.css',
            [sayhelloGitInstaller()->prefix . '-roboto'],
            $script_version
        );

        wp_enqueue_script(
            sayhelloGitInstaller()->prefix . '-admin-script',
            $dir_uri . 'assets/dist/admin.js',
            [
                'react',
                'react-dom',
                'wp-components',
                'wp-i18n',
            ],
            $script_version,
            true
        );

        /**
         * Admin Footer JS
         */

        $defaults = [
            'ajaxUrl'             => admin_url('admin-ajax.php'),
            'homeUrl'             => trailingslashit(get_site_url()),
            'pluginUrl'           => trailingslashit(plugin_dir_url(sayhelloGitInstaller()->file)),
            'pluginPrefix'        => sayhelloGitInstaller()->prefix,
            'generalError'        => __('An unexpected error occured', 'shgi'),
            'settings'            => sayhelloGitInstaller()->Settings->getSettings(),
            'settingsData'        => sayhelloGitInstaller()->Settings->getSettings(),
            'restBase'            => trailingslashit(get_rest_url()),
            'restPluginBase'      => trailingslashit(get_rest_url() . sayhelloGitInstaller()->api_namespace),
            'restPluginNamespace' => sayhelloGitInstaller()->api_namespace,
            'pluginStrings'       => apply_filters('shgi/PluginStrings', []),
            'nonce'               => wp_create_nonce('wp_rest'),
        ];

        $vars = json_encode(apply_filters('shgi/Assets/AdminFooterJS', $defaults));

        wp_add_inline_script(sayhelloGitInstaller()->prefix . '-admin-script', "var shgiJsVars = {$vars};", 'before');
    }
}
