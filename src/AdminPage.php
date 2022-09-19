<?php

namespace SayHello\GitUpdater;

class AdminPage
{
    public $capability = '';
    public $settings_parent = '';
    public $menu_title = '';
    private $menu = [];

    public function __construct()
    {
        $this->capability = 'administrator';
        $this->menu_title = __('Git Updater', 'shgu');
        $this->menu = [];
    }

    public function run()
    {
        add_action('admin_menu', [$this, 'menu']);
        add_filter('shgu/Assets/AdminFooterJS', [$this, 'footerVars']);
    }

    public function menu()
    {
        $icon = plugin_dir_url(sayhelloGitUpdater()->file) . '/assets/img/hello-menu-icon.png';
        $menuItems = $this->getMenuItems();
        $this->settings_parent = sayhelloGitUpdater()->prefix . '-' . array_key_first($menuItems);

        add_menu_page(
            sayhelloGitUpdater()->name,
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
                    sayhelloGitUpdater()->prefix . '-' . $slug,
                    [$this, 'page']
                );
            }
        }
    }

    public function page()
    {
        ?>
        <div id="shgu-app"></div>
        <?php
    }

    public function getMenuItems()
    {
        return apply_filters('shgu/AdminPage/Menu', $this->menu);
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
