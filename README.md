# Git Installer (Alpha)

## Features

|                                                                                                                                                       |         Free          |     Pro      |
|-------------------------------------------------------------------------------------------------------------------------------------------------------|:---------------------:|:------------:|
| **Install and update Plugins from Git repositories**                                                                                                  |           ✅           |      ✅       |
| **Provider**                                                                                                                                          |                       |              |
| - Github                                                                                                                                              |           ✅           |      ✅       |
| - Gitlab                                                                                                                                              |           ✅           |      ✅       |
| - Bitbucket                                                                                                                                           |           ✅           |      ✅       |
| **Push to deploy URL**                                                                                                                                |           ✅           |      ✅       |
| **Private Repositories**                                                                                                                              |           ❌           |      ✅       |
| **Must Use Plugin support**<br />*[https://wordpress.org/support/article/must-use-plugins/](https://wordpress.org/support/article/must-use-plugins/)* |           ✅           |      ✅       |
| **Branches**                                                                                                                                          |  only default branch  |  any branch  |
| **Multisite**                                                                                                                                         |           ✅           |      ✅       |
| **Install from subdirectories**                                                                                                                       |           ❌           |      ✅       |
| **Check directory**<br />Validates a Repository and checks wether a valid WordPress theme or plugin is found.                                         |           ✅           |      ✅       |

## Docs
### Must Use Plugin
Activate "Must Use Plugin" support
```php
add_filter('shgi/Repositories/MustUsePlugins', '__return_true');
```
Now you are able to select the target folder for your plugin before the installation.

## Changelog
### 0.0.4
- warning if REST API access is disabled
- overwrite existing packages on install
- fixed a couple of bugs

### 0.0.3
- added support for Plugins or Themes from subdirectories
- fixed "Version: null" bug after install

### 0.0.2
- added support for [Must Use Plugins](https://wordpress.org/support/article/must-use-plugins/)
- improvements for error messages
- added automatic check for plugins and themes
- added multisite support
- improved Auth-Key handling
- delete invalid characters from Auth-Keys
- Bugfix: works now with permalink settings "Plain"

### 0.0.1
- initial release
