<?php

namespace SayHello\GitInstaller;

class AdminPage
{
    public string $capability = 'administrator';
    public string $settings_parent = 'shgi-git-packages';
    public string $menu_title = '';
    public string $hook_suffix = '';

    public function run()
    {
        if (is_multisite()) {
            add_action('network_admin_menu', [$this, 'menu']);
        } else {
            add_action('admin_menu', [$this, 'menu']);
        }
        add_filter('shgi/Assets/AdminFooterJS', [$this, 'footerVars']);
    }

    public function menu()
    {
        $icon = 'data:image/svg+xml;base64,' . base64_encode(sayhelloGitInstaller()->iconSvg);
        $this->menu_title = __('Git Installer', 'shgi');

        $this->hook_suffix = add_menu_page(
            sayhelloGitInstaller()->name,
            $this->menu_title,
            $this->capability,
            $this->settings_parent,
            [$this, 'page'],
            $icon,
            100
        );
    }

    public function page()
    {
        ?>
        <div id="shgi-app"></div>
        <?php
    }

    public function footerVars($vars)
    {
        $vars['settingsParentKey'] = $this->settings_parent;
        $vars['menu'] = [
            'git-packages' => [
                'title' => __('Git Packages', 'shgi'),
            ],
        ];
        $vars['adminUrl'] = get_admin_url();

        return $vars;
    }
}
