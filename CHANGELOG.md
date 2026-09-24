# Changelog

## [0.3.0] - 2026-09-16

### Added

- Check the Newspack parent theme and its five child themes (Joseph, Katharine, Nelson, Sacha, Scott) when they are installed, using the `newspack-theme@` monorepo release and each theme’s own zip

## [0.2.0] - 2026-08-19

### Changed

- Check [newspack-workspace](https://github.com/Automattic/newspack-workspace) for updates instead of the archived per-plugin GitHub repositories
- Install the matching `{slug}.zip` release asset for each package, using tags such as `newspack@6.48.5`
- Watch additional GitHub-only plugins (Multibranded Site, Network, Story Budget) when they are installed

### Fixed

- Apply the `npuc_newspack_plugin_list` filter when building the watch list
- Look up Newspack Sponsors as `newspack-sponsors` instead of the misspelled slug
- Stop checking plugins that were merged into Newspack or are no longer shipped from GitHub

## [0.1.0] - 2023-10-24

_Initial release._

[0.3.0]: https://github.com/aschweigert/newspack-plugin-update-checker/releases/tag/v0.3.0
[0.2.0]: https://github.com/aschweigert/newspack-plugin-update-checker/releases/tag/v1.1.0
[0.1.0]: https://github.com/aschweigert/newspack-plugin-update-checker/releases/tag/v0.1
