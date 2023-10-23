# Newspack Plugin Update Checker

[Newspack](https://newspack.com) doesn't list most of their plugins in the wp.org plugin directory so they need to be updated from GitHub. 

Fortunately, there's a [plugin updater library](https://github.com/YahnisElsts/plugin-update-checker) that can check for updates and let you know when a new version is available.

For the time being this plugin just checks for the most commonly-used Newspack plugins (there are a [handful of others](https://github.com/orgs/Automattic/repositories?q=newspack) but those seemed less essential to keep tabs on):

* Newspack Plugin
* Newspack Ads
* Newspack Blocks
* Newspack Popups (aka Campaigns)
* Newspack Listings
* Newspack Sponsors
* Newspack Media Partners
* Newspack RSS Enhancements
* Newspack Supporters

Newspack uses Composer to build the releases for their plugins so a common gotcha is trying to download the Main (or Release) branch directly. Fortunately, this plugin updater library has the option to grab the zip file of the latest tagged release, so that's what it's using to perform the update (which should work just fine).

I hope this is helpful! Let me know if you have any questions or run into any issues.

