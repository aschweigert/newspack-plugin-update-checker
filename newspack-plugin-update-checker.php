<?php
/**
 * Plugin Name:       Newspack Plugin Update Checker
 * Description:       Keep tabs on updates to Newspack plugins and themes that are only available on GitHub
 * Version:           0.4.0a1
 * Requires at least: 3.7
 * Requires PHP:      7.4
 * Author:            Adam Schweigert, Media Toybox
 * Author URI:        https://mediatoybox.com/
 * License: 	      GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       newspack-plugin-update-checker
 *
 * @package Newspack-Plugin-Update-Checker
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// Load the update checker library https://github.com/YahnisElsts/plugin-update-checker.
require_once __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php';
require_once __DIR__ . '/includes/class-npuc-github-monorepo-api.php';

use YahnisElsts\PluginUpdateChecker\v5p2\Vcs\GitHubApi as NPUC_GitHub_Api;
use YahnisElsts\PluginUpdateChecker\v5p2\Vcs\PluginUpdateChecker as NPUC_Vcs_Plugin_Update_Checker;
use YahnisElsts\PluginUpdateChecker\v5p2\Vcs\ThemeUpdateChecker as NPUC_Vcs_Theme_Update_Checker;

// Newspack plugins and themes now ship from this monorepo rather than per-package GitHub repos.
define( 'NPUC_WORKSPACE_REPO_URL', 'https://github.com/Automattic/newspack-workspace' );

if ( ! function_exists( 'npuc_get_monitored_plugin_slugs' ) ) {
	/**
	 * GitHub-only Newspack plugin slugs to watch when they are installed.
	 *
	 * WordPress.org-hosted extensions (Newsletters, Republication Tracker Tool,
	 * Super Cool Ad Inserter) are omitted so they keep using .org updates.
	 *
	 * @return array<int, string>
	 */
	function npuc_get_monitored_plugin_slugs(): array {
		return array(
			'newspack-plugin',
			'newspack-ads',
			'newspack-blocks',
			'newspack-popups',
			'newspack-listings',
			'newspack-sponsors',
			'newspack-multibranded-site',
			'newspack-network',
			'newspack-story-budget',
		);
	}
}

if ( ! function_exists( 'npuc_get_standalone_plugins' ) ) {
	/**
	 * GitHub-only Newspack plugins that still ship from their own repositories.
	 *
	 * Each item is slug => repository URL. These cannot use the workspace
	 * adapter: tags are `vX.Y.Z` and the install zip lives on that repo.
	 *
	 * @return array<string, string>
	 */
	function npuc_get_standalone_plugins(): array {
		return array(
			'newspack-elections' => 'https://github.com/Automattic/newspack-elections',
		);
	}
}

if ( ! function_exists( 'npuc_get_plugin_bootstrap_file' ) ) {
	/**
	 * Main plugin file name inside the plugin directory.
	 *
	 * @param string $plugin_slug Plugin directory slug.
	 * @return string
	 */
	function npuc_get_plugin_bootstrap_file( string $plugin_slug ): string {
		if ( 'newspack-plugin' === $plugin_slug ) {
			// The main Newspack plugin uses newspack.php instead of newspack-plugin.php.
			return 'newspack.php';
		}

		return $plugin_slug . '.php';
	}
}

if ( ! function_exists( 'npuc_get_plugin_tag_prefix' ) ) {
	/**
	 * Monorepo git tag prefix for a plugin slug, including the trailing "@".
	 *
	 * @param string $plugin_slug Plugin directory slug.
	 * @return string
	 */
	function npuc_get_plugin_tag_prefix( string $plugin_slug ): string {
		if ( 'newspack-plugin' === $plugin_slug ) {
			// The main plugin is tagged as newspack@x.y.z, not newspack-plugin@x.y.z.
			return 'newspack@';
		}

		return $plugin_slug . '@';
	}
}

if ( ! function_exists( 'npuc_get_monitored_theme_slugs' ) ) {
	/**
	 * GitHub-only Newspack theme slugs to watch when they are installed.
	 *
	 * The parent theme and its five child themes ship together from the
	 * `newspack-theme@` monorepo release, each as its own zip.
	 *
	 * @return array<int, string>
	 */
	function npuc_get_monitored_theme_slugs(): array {
		return array(
			'newspack-theme',
			'newspack-joseph',
			'newspack-katharine',
			'newspack-nelson',
			'newspack-sacha',
			'newspack-scott',
		);
	}
}

if ( ! function_exists( 'npuc_get_github_access_token' ) ) {
	/**
	 * Optional GitHub token used to raise the unauthenticated API quota.
	 *
	 * @return string|null
	 */
	function npuc_get_github_access_token(): ?string {
		$access_token = apply_filters( 'npuc_github_access_token', '' );
		if ( ! is_string( $access_token ) || '' === $access_token ) {
			return null;
		}

		return $access_token;
	}
}

if ( ! function_exists( 'npuc_normalize_monorepo_version' ) ) {
	/**
	 * Fall back to the SemVer portion of a `package@version` tag if headers were unavailable.
	 *
	 * @param object|null $plugin_info Update info from Plugin Update Checker.
	 * @return object|null
	 */
	function npuc_normalize_monorepo_version( $plugin_info ) {
		if ( ! is_object( $plugin_info ) || empty( $plugin_info->version ) || ! is_string( $plugin_info->version ) ) {
			return $plugin_info;
		}

		$separator = strrpos( $plugin_info->version, '@' );
		if ( false !== $separator ) {
			$plugin_info->version = substr( $plugin_info->version, $separator + 1 );
		}

		return $plugin_info;
	}
}

if ( ! function_exists( 'npuc_get_installed_plugin_file' ) ) {
	/**
	 * Absolute path to a plugin bootstrap file, if that plugin is installed.
	 *
	 * @param string $plugin_slug Plugin directory slug.
	 * @return string|null
	 */
	function npuc_get_installed_plugin_file( string $plugin_slug ): ?string {
		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . npuc_get_plugin_bootstrap_file( $plugin_slug );

		return file_exists( $plugin_file ) ? $plugin_file : null;
	}
}

if ( ! function_exists( 'npuc_register_plugin_update_checker' ) ) {
	/**
	 * Attach Plugin Update Checker to an installed plugin.
	 *
	 * @param object $github_api   GitHub API client (workspace adapter or stock GitHub API).
	 * @param string $plugin_file  Absolute path to the main plugin file.
	 * @param string $plugin_slug  Plugin directory slug.
	 * @param bool   $is_workspace Whether versions come from monorepo `package@version` tags.
	 */
	function npuc_register_plugin_update_checker( object $github_api, string $plugin_file, string $plugin_slug, bool $is_workspace ): void {
		$npuc_update_checker = new NPUC_Vcs_Plugin_Update_Checker(
			$github_api,
			$plugin_file,
			$plugin_slug
		);

		if ( $is_workspace ) {
			$npuc_update_checker->addResultFilter( 'npuc_normalize_monorepo_version' );
		}
	}
}

if ( ! function_exists( 'npuc_newspack_plugin_update' ) ) {
	/**
	 * Loop through the plugins, make sure they exist, run the update checker.
	 */
	function npuc_newspack_plugin_update(): void {
		$access_token = npuc_get_github_access_token();

		$newspack_plugin_list = npuc_get_monitored_plugin_slugs();
		$newspack_plugin_list = apply_filters( 'npuc_newspack_plugin_list', $newspack_plugin_list );

		if ( is_array( $newspack_plugin_list ) ) {
			foreach ( $newspack_plugin_list as $plugin_slug ) {
				if ( ! is_string( $plugin_slug ) || '' === $plugin_slug ) {
					continue;
				}

				$plugin_file = npuc_get_installed_plugin_file( $plugin_slug );
				if ( null === $plugin_file ) {
					continue;
				}

				$github_api = new NPUC_GitHub_Monorepo_Api(
					NPUC_WORKSPACE_REPO_URL,
					npuc_get_plugin_tag_prefix( $plugin_slug ),
					$plugin_slug . '.zip',
					$access_token
				);

				npuc_register_plugin_update_checker( $github_api, $plugin_file, $plugin_slug, true );
			}
		}

		$standalone_plugins = npuc_get_standalone_plugins();
		$standalone_plugins = apply_filters( 'npuc_standalone_plugin_list', $standalone_plugins );

		if ( ! is_array( $standalone_plugins ) ) {
			return;
		}

		foreach ( $standalone_plugins as $plugin_slug => $repo_url ) {
			if ( ! is_string( $plugin_slug ) || '' === $plugin_slug || ! is_string( $repo_url ) || '' === $repo_url ) {
				continue;
			}

			$plugin_file = npuc_get_installed_plugin_file( $plugin_slug );
			if ( null === $plugin_file ) {
				continue;
			}

			$github_api = new NPUC_GitHub_Api( $repo_url, $access_token );
			$github_api->enableReleaseAssets(
				'/^' . preg_quote( $plugin_slug, '/' ) . '-.*\\.zip$/',
				NPUC_GitHub_Api::REQUIRE_RELEASE_ASSETS
			);

			npuc_register_plugin_update_checker( $github_api, $plugin_file, $plugin_slug, false );
		}
	}
}
add_action( 'plugins_loaded', 'npuc_newspack_plugin_update' );

if ( ! function_exists( 'npuc_newspack_theme_update' ) ) {
	/**
	 * Loop through the Newspack themes, make sure they exist, run the update checker.
	 */
	function npuc_newspack_theme_update(): void {
		$newspack_theme_list = npuc_get_monitored_theme_slugs();
		$newspack_theme_list = apply_filters( 'npuc_newspack_theme_list', $newspack_theme_list );

		if ( ! is_array( $newspack_theme_list ) ) {
			return;
		}

		$access_token = npuc_get_github_access_token();

		foreach ( $newspack_theme_list as $theme_slug ) {
			if ( ! is_string( $theme_slug ) || '' === $theme_slug ) {
				continue;
			}

			$stylesheet = get_theme_root( $theme_slug ) . '/' . $theme_slug . '/style.css';

			// Check to make sure this particular theme is installed before we check for updates.
			if ( ! is_readable( $stylesheet ) ) {
				continue;
			}

			$github_api = new NPUC_GitHub_Monorepo_Api(
				NPUC_WORKSPACE_REPO_URL,
				'newspack-theme@',
				$theme_slug . '.zip',
				$access_token,
				'themes/newspack-theme/' . $theme_slug
			);

			$npuc_update_checker = new NPUC_Vcs_Theme_Update_Checker(
				$github_api,
				$theme_slug,
				$theme_slug
			);

			$npuc_update_checker->addResultFilter( 'npuc_normalize_monorepo_version' );
		}
	}
}
add_action( 'plugins_loaded', 'npuc_newspack_theme_update' );
