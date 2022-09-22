<?php

namespace SayHello\GitInstaller;

class AdminPage
{
    public $capability = '';
    public $settings_parent = '';
    public $menu_title = '';
    private $menu = [];

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
        $icon = 'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M2.6,10.59L8.38,4.8L10.07,6.5C9.83,7.35 10.22,8.28 11,8.73V14.27C10.4,14.61 10,15.26 10,16A2,2 0 0,0 12,18A2,2 0 0,0 14,16C14,15.26 13.6,14.61 13,14.27V9.41L15.07,11.5C15,11.65 15,11.82 15,12A2,2 0 0,0 17,14A2,2 0 0,0 19,12A2,2 0 0,0 17,10C16.82,10 16.65,10 16.5,10.07L13.93,7.5C14.19,6.57 13.71,5.55 12.78,5.16C12.35,5 11.9,4.96 11.5,5.07L9.8,3.38L10.59,2.6C11.37,1.81 12.63,1.81 13.41,2.6L21.4,10.59C22.19,11.37 22.19,12.63 21.4,13.41L13.41,21.4C12.63,22.19 11.37,22.19 10.59,21.4L2.6,13.41C1.81,12.63 1.81,11.37 2.6,10.59Z" /></svg>');
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
