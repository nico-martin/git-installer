# Changelog

## 1.3.3

## 1.3.2
- Fix for issue #69

## 1.3.1
- Fix for maintenance mode on update error (issue #63)
- Fix for timeout for wp_remote_get package zip (issue #63)
- Fix for `validateDOMNesting` (issue #61)

## 1.3.0
- New feature: Postupdate Hooks (issue #50)
- New feature: If the Plugin/Theme is installed from a directory inside the repository, the directory name will be the Plugin/Theme-directory (issue #46)
- Fix for fullDelete bug
- Fix for invalid/changed repository URLs (issue #49)
- Fix for directory name (issue #48)
- Fix for `register_rest_route was called incorrectly` by [neverything](https://github.com/neverything)
- Fix for `validateDOMNesting` (issue #56)

## 1.2.5
- Fix for dot in repository name

## 1.2.4
- Improvement for .maintenance

## 1.2.3
- Removed possible PHP Notices
- Added .maintenance while updating

## 1.2.2
- Bugfix for gitlab URL parsing

## 1.2.1
- Bugfix for issue #42

## 1.2.0
- improved GitHub, Gitlab and Bitbucket link validation
- added support for GitHub fine-grained personal access tokens
- some text-adjustments

## 1.1.0
- added action `shgi/GitPackages/updatePackage/success`
- added action `shgi/GitPackages/updatePackage/error`
- bugfix: update log did not save prevVersion

## 1.0.0

- out of beta 🎉 
- small fixes and adjustments

## 0.2.2

- added update process for "git installer"
- added permission callback for GET/POST git-packages-update/(?P<slug>\S+)/

## 0.2.1

- added support to update packages directly from the theme/plugin overview
- bugfix: install fails on first try
- bugfix: theme in subfolder

## 0.2.0

- public beta, no changes

## 0.1.1

- added confirmation modal before deletion
- added possibility to keep theme/plugin and only remove git connection
- added update log

## 0.1.0

- stable Beta, no changes

## 0.0.5

- bugfix: UI adjustments if installation fails
- bugfix: copyDir/rename
- bugfix: flush theme cache after new Theme is added
- pushToDeploy URL now also works for POST requests

## 0.0.4

- warning if REST API access is disabled
- overwrite existing packages on install
- fixed a couple of bugs

## 0.0.3

- added support for Plugins or Themes from subdirectories
- fixed "Version: null" bug after install

## 0.0.2

- added support for [Must Use Plugins](https://wordpress.org/support/article/must-use-plugins/)
- improvements for error messages
- added automatic check for plugins and themes
- added multisite support
- improved Auth-Key handling
- delete invalid characters from Auth-Keys
- Bugfix: works now with permalink settings "Plain"

## 0.0.1

- initial release
