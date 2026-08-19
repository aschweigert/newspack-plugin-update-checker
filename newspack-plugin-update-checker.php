<?php
/**
 * Plugin Name:       Newspack Plugin Update Checker
 * Description:       Keep tabs on updates to Newspack plugins that are only available on GitHub
 * Version:           1.1.0
 * Requires at least: 3.7
 * Requires PHP:      7.4
 * Author:            Media Toybox
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

use YahnisElsts\PluginUpdateChecker\v5p2\Vcs\PluginUpdateChecker as NPUC_Vcs_Plugin_Update_Checker;

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

if ( ! function_exists( 'npuc_newspack_plugin_update' ) ) {
	/**
	 * Loop through the plugins, make sure they exist, run the update checker.
	 */
	function npuc_newspack_plugin_update(): void {
		$newspack_plugin_list = npuc_get_monitored_plugin_slugs();
		$newspack_plugin_list = apply_filters( 'npuc_newspack_plugin_list', $newspack_plugin_list );

		if ( ! is_array( $newspack_plugin_list ) ) {
			return;
		}

		$access_token = apply_filters( 'npuc_github_access_token', '' );
		if ( ! is_string( $access_token ) || '' === $access_token ) {
			$access_token = null;
		}

		foreach ( $newspack_plugin_list as $plugin_slug ) {
			if ( ! is_string( $plugin_slug ) || '' === $plugin_slug ) {
				continue;
			}

			$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . npuc_get_plugin_bootstrap_file( $plugin_slug );

			// Check to make sure this particular plugin file exists before we check for updates.
			if ( ! file_exists( $plugin_file ) ) {
				continue;
			}

			$github_api = new NPUC_GitHub_Monorepo_Api(
				NPUC_WORKSPACE_REPO_URL,
				npuc_get_plugin_tag_prefix( $plugin_slug ),
				$plugin_slug . '.zip',
				$access_token
			);

			$npuc_update_checker = new NPUC_Vcs_Plugin_Update_Checker(
				$github_api,
				$plugin_file,
				$plugin_slug
			);

			$npuc_update_checker->addResultFilter( 'npuc_normalize_monorepo_version' );
		}
	}
}
add_action( 'plugins_loaded', 'npuc_newspack_plugin_update' );
