=== Git Installer ===
Contributors: nico_martin, sayhellogmbh
Donate link: https://github.com/sponsors/SayHelloGmbH/
License: MIT
Tags: Git, Github, Gitlab, Bitbucket
Tested up to: 6.1
Stable tag: 1.3.1
Requires PHP: 7.4

Install and update WordPress themes and plugins directly from your Git repository via GitHub, Gitlab or Bitbucket.

== Description ==

"Git Installer" works with public and private repositories, different branches, subdirectories and even allows automated updates via webhooks. Furthermore, plugins or themes are automatically recognised and validated and it also supports must use plugins and multisite installations.

### Features
* **Install and update Plugins from Git repositories**
* **Provider**
* * GitHub
* * Gitlab
* * Bitbucket
* **Webhook updates**
* **Integrated WordPress update process**: View pending updates directly in the WordPress overview and update them individually or as a bulk update.
* **Private Repositories**
* **Must Use Plugin support**: *[https://wordpress.org/support/article/must-use-plugins/](https://wordpress.org/support/article/must-use-plugins/)*
* **Branches**: use any branch
* **Multisite**
* **Install from subdirectories**
* **Check directory**: Validates a Repository and checks wether a valid WordPress theme or plugin is found.

### Webhook updates

"Git Installer" enables updates to be carried out automatically via a webhook. For each package, a "Webhook Update URL" is created, which must be deposited with the respective provider.

#### GitHub
*Repository -> Settings -> Webhooks -> Add webhook:*
- Payload URL: the Webhook Update URL
- Content type: application/x-www-form-urlencoded
- Secret: none
- Which events would you like to trigger this webhook?: Just the push event

#### Gitlab
*Repository -> Settings -> Webhooks:*
- URL: the Webhook Update URL
- Trigger: Push events (Branch name should match the branch you are using, blank works as well)
- Secret token: none
- SSL verification: checked

#### Bitbucket
*Repository -> Repository settings -> Workflow -> Webhooks -> Add webhook:*
- Title: choose your own
- URL: the Webhook Update URL
- Active: checked
- SSL/TLS: unchecked
- Triggers: Repository > Push

### Hooks
Read more about the available hooks on Github: [https://github.com/SayHelloGmbH/git-installer#hooks](https://github.com/SayHelloGmbH/git-installer#hooks)

== Frequently Asked Questions ==

== Screenshots ==

1. Install Themes and Plugins directly from the URL
2. Select a branch and subdirectory from which the package is to be installed.
3. Sit back and watch how everything is happening in the background
4. View pending updates directly in the WordPress overview and update them individually or as a bulk update..
5. .. or update from the plugin settings page

== Changelog ==

[https://github.com/SayHelloGmbH/git-installer#changelog](https://github.com/SayHelloGmbH/git-installer#changelog)
