<?php

namespace SayHello\GitInstaller;

class AdminPage
{
    public string $capability = '';
    public string $settings_parent = '';
    public string $menu_title = '';
    private array $menu = [];

    public function __construct()
    {
        $this->capability = 'administrator';
        $this->menu_title = __('Git Installer', 'shgi');
        $this->menu = [];
    }

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
        $menuItems = $this->getMenuItems();
        $this->settings_parent = sayhelloGitInstaller()->prefix . '-' . array_key_first($menuItems);

        add_menu_page(
            sayhelloGitInstaller()->name,
            $this->menu_title,
            $this->capability,
            $this->settings_parent,
            [$this, 'page'],
            $icon,
            100
        );

        if (count($menuItems) === 1) {
            foreach ($this->getMenuItems() as $slug => $menuElment) {
                add_submenu_page(
                    $this->settings_parent,
                    $menuElment['title'],
                    $menuElment['title'],
                    $this->capability,
                    sayhelloGitInstaller()->prefix . '-' . $slug,
                    [$this, 'page']
                );
            }
        }
    }

    public function page()
    {
        ?>
        <div id="shgi-app"></div>
        <?php
    }

    public function getMenuItems()
    {
        return apply_filters('shgi/AdminPage/Menu', $this->menu);
    }

    public function footerVars($vars)
    {
        /*
        foreach ($this->getMenuItems() as $slug => $item) {
          $this->menu[$slug]['submenu'] = apply_filters('pwp_submenu_' . $slug, $item['submenu']);
        }
        */
        $vars['settingsParentKey'] = $this->settings_parent;
        $vars['menu'] = $this->getMenuItems();
        $vars['adminUrl'] = get_admin_url();

        return $vars;
    }
}
