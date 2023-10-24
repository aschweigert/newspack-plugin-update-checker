# Newspack Plugin Update Checker

[Newspack](https://newspack.com) doesn't list most of their plugins in the wp.org plugin directory so they need to be updated from GitHub. 

Fortunately, there's a [plugin updater library](https://github.com/YahnisElsts/plugin-update-checker) that can check for updates and let you know when a new version is available.

For the time being this plugin just checks for the most commonly-used Newspack plugins (there are a [handful of others](https://github.com/orgs/Automattic/repositories?q=newspack) but those seemed less essential to keep tabs on):

* [Newspack Plugin](https://github.com/automattic/newspack-plugin)
* [Newspack Ads](https://github.com/automattic/newspack-ads)
* [Newspack Blocks](https://github.com/automattic/newspack-blocks)
* [Newspack Popups (aka Campaigns)](https://github.com/automattic/newspack-popups)
* [Newspack Listings](https://github.com/automattic/newspack-listings)
* [Newspack Sponsors](https://github.com/automattic/newspack-sponsors)
* [Newspack Media Partners](https://github.com/automattic/newspack-media-partners)
* [Newspack RSS Enhancements](https://github.com/automattic/newspack-rss-enhancements)
* [Newspack Supporters](https://github.com/automattic/newspack-supporters)

## Installation

You can download the latest release as a zip file from [the releases tab](https://github.com/aschweigert/newspack-plugin-update-checker/releases) here on GitHub.

Rename the file to remove the version number (the file name will become the folder name in your wp-content/plugins directory when WP unzips the file to install it).

Go to the Plugins section of WP Admin, click on "Add New" at the top, upload the zip file, install, activate, and you should be good to go!

You'll know it's working if you see the options to "check for updates" and "enable auto updates" for any of the Newspack plugins you have installed:

![plugin-updater](https://github.com/aschweigert/newspack-plugin-update-checker/assets/490703/dc4af5fa-4753-4b87-8492-bd71357a9809)

## Some Notes

The plugin assumes you have the plugin(s) in folders named using their respective slugs (e.g. wp-content/plugins/newspack-plugin/). If you initially downloaded the plugin(s) from GitHub they may have had the branch name or release tag appended (e.g. wp-content/plugins/newspack-plugin-master/). You'll need to rename the folder if this is the case.

Newspack uses Composer to build the releases for their plugins so a common gotcha is trying to download the Main (or Release) branch directly. Fortunately, this plugin updater library has the option to grab the zip file of the latest tagged release, so that's what it's using to perform the update (which should work just fine).

While the plugin will allow you to enable auto-updates, I'd recommend keeping an eye on the [Newspack release notes](https://newspack.com/release-notes/) to make sure you're aware of what's in each release and any potentially breaking changes that could affect your site.

## Questions? Comments?

I hope this is helpful. Let me know if you have any questions or run into any issues. The best thing is simply to open a GitHub issue on this repository and I'll get back to you as soon as I can!

