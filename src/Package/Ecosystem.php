<?php

namespace SayHello\GitInstaller\Package;

class Ecosystem
{
    public function run()
    {
        add_action('rest_api_init', [$this, 'registerRoute']);
        add_filter('shgi/Assets/AdminFooterJS', [$this, 'footerJsVars']);
    }

    public function registerRoute()
    {
        register_rest_route(sayhelloGitInstaller()->api_namespace, 'ping', [
            'methods' => 'GET',
            'callback' => function () {
                return [
                    'access' => true,
                ];
            },
            'permission_callback' => '__return_true',
        ]);
    }

    public function footerJsVars($vars)
    {
        $vars['activePlugins'] = get_option('active_plugins');

        return $vars;
    }
}
