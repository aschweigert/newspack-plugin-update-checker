# Newspack Plugin Update Checker

[Newspack](https://newspack.com) doesn't list most of their plugins in the wp.org plugin directory so they need to be updated from GitHub.

Fortunately, there's a [plugin updater library](https://github.com/YahnisElsts/plugin-update-checker) that can check for updates and let you know when a new version is available.

Newspack now ships those GitHub-only plugins from the [newspack-workspace](https://github.com/Automattic/newspack-workspace) monorepo (the old per-plugin repos are archived). This plugin checks that repository for the latest stable release zip of each installed plugin. For the time being it watches the GitHub-only Newspack plugins (extensions that are also on [WordPress.org](https://wordpress.org/plugins/) keep using .org updates):

* [Newspack Plugin](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-plugin)
* [Newspack Ads](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-ads)
* [Newspack Blocks](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-blocks)
* [Newspack Popups (aka Campaigns)](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-popups)
* [Newspack Listings](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-listings)
* [Newspack Sponsors](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-sponsors)
* [Newspack Multibranded Site](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-multibranded-site)
* [Newspack Network](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-network)
* [Newspack Story Budget](https://github.com/Automattic/newspack-workspace/tree/main/plugins/newspack-story-budget)

## Installation

You can download the latest release as a zip file from [the releases tab](https://github.com/aschweigert/newspack-plugin-update-checker/releases) here on GitHub.

Rename the file to remove the version number (the file name will become the folder name in your wp-content/plugins directory when WP unzips the file to install it).

Go to the Plugins section of WP Admin, click on "Add New" at the top, upload the zip file, install, activate, and you should be good to go!

You'll know it's working if you see the options to "check for updates" and "enable auto updates" for any of the Newspack plugins you have installed:

![plugin-updater](https://github.com/aschweigert/newspack-plugin-update-checker/assets/490703/dc4af5fa-4753-4b87-8492-bd71357a9809)

## Some Notes

The plugin assumes you have the plugin(s) in folders named using their respective slugs (e.g. wp-content/plugins/newspack-plugin/). If you initially downloaded the plugin(s) from GitHub they may have had the branch name or release tag appended (e.g. wp-content/plugins/newspack-plugin-master/). You'll need to rename the folder if this is the case.

Newspack builds installable zips and attaches them to [GitHub Releases](https://github.com/Automattic/newspack-workspace/releases) in the workspace monorepo (you can also grab the latest stable packages from the [Download Center](https://newspack.com/download-center/)). A common gotcha is cloning or downloading the Main branch of the monorepo — that's source, not a WordPress install. This plugin uses the zip asset from the latest stable tagged release for each installed plugin (for example `newspack@6.48.5` → `newspack-plugin.zip`).

While the plugin will allow you to enable auto-updates, I'd recommend keeping an eye on the [Newspack release notes](https://newspack.com/release-notes/) to make sure you're aware of what's in each release and any potentially breaking changes that could affect your site.

## Questions? Comments?

I hope this is helpful. Let me know if you have any questions or run into any issues. The best thing is simply to open a GitHub issue on this repository and I'll get back to you as soon as I can!
