<?php
/**
 * GitHub API adapter for Automattic/newspack-workspace package releases.
 *
 * @package Newspack-Plugin-Update-Checker
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use YahnisElsts\PluginUpdateChecker\v5p2\Vcs\GitHubApi;
use YahnisElsts\PluginUpdateChecker\v5p2\Vcs\Reference;

/**
 * Resolves a single Newspack package from the workspace monorepo.
 *
 * Newspack publishes every plugin from one GitHub repository using tags like
 * `newspack-ads@3.14.2` and a matching `{slug}.zip` release asset. Plugin Update
 * Checker's default GitHub client treats the repo as one plugin, so this adapter
 * selects the right tag, zip, and source path for a given package.
 */
class NPUC_GitHub_Monorepo_Api extends GitHubApi {
	/**
	 * Tag prefix for this package, including the trailing "@".
	 *
	 * @var string
	 */
	protected string $tag_prefix;

	/**
	 * @param string      $repository_url GitHub repository URL.
	 * @param string      $tag_prefix     Package tag prefix, e.g. "newspack-ads@".
	 * @param string      $asset_filename Release zip filename, e.g. "newspack-ads.zip".
	 * @param string|null $access_token   Optional GitHub token for a higher API quota.
	 */
	public function __construct( string $repository_url, string $tag_prefix, string $asset_filename, $access_token = null ) {
		parent::__construct( $repository_url, $access_token );

		$this->tag_prefix = $tag_prefix;

		// Built install zips are attached as release assets; the git archive is the whole monorepo.
		$this->enableReleaseAssets(
			'/^' . preg_quote( $asset_filename, '/' ) . '$/',
			self::REQUIRE_RELEASE_ASSETS
		);
	}

	/**
	 * Only look at GitHub Releases. Tags and branches in the workspace are source, not install zips.
	 *
	 * @param string $configBranch Requested branch (unused; releases are the only strategy).
	 * @return array<string, callable>
	 */
	protected function getUpdateDetectionStrategies( $configBranch ) {
		unset( $configBranch );

		return array(
			self::STRATEGY_LATEST_RELEASE => array( $this, 'getLatestRelease' ),
		);
	}

	/**
	 * Find the newest stable release for this package via matching tag refs.
	 *
	 * @return Reference|null
	 */
	public function getLatestRelease() {
		foreach ( array_slice( $this->npuc_get_stable_tag_names(), 0, 10 ) as $tag_name ) {
			$release = $this->api(
				'/repos/:user/:repo/releases/tags/' . rawurlencode( $tag_name )
			);
			if ( is_wp_error( $release ) || ! is_object( $release ) || empty( $release->tag_name ) ) {
				continue;
			}

			if ( ! empty( $release->draft ) || ! empty( $release->prerelease ) ) {
				continue;
			}

			$reference = $this->npuc_reference_from_release( $release );
			if ( null !== $reference ) {
				return $reference;
			}
		}

		return null;
	}

	/**
	 * Read plugin files from plugins/{slug}/ in the monorepo instead of the repo root.
	 *
	 * @param string $path File path relative to the plugin directory.
	 * @param string $ref  Git ref (tag, branch, or commit).
	 * @return string|null
	 */
	public function getRemoteFile( $path, $ref = 'master' ) {
		if ( '' !== $this->slug && 0 !== strpos( $path, 'plugins/' ) ) {
			$path = 'plugins/' . $this->slug . '/' . ltrim( $path, '/' );
		}

		return parent::getRemoteFile( $path, $ref );
	}

	/**
	 * Return stable `{prefix}{version}` tags for this package, newest first.
	 *
	 * @return array<int, string>
	 */
	protected function npuc_get_stable_tag_names(): array {
		$refs = $this->api(
			'/repos/:user/:repo/git/matching-refs/tags/' . rawurlencode( $this->tag_prefix )
		);
		if ( is_wp_error( $refs ) || ! is_array( $refs ) ) {
			return array();
		}

		$versions_by_tag = array();
		foreach ( $refs as $ref ) {
			if ( ! is_object( $ref ) || empty( $ref->ref ) || ! is_string( $ref->ref ) ) {
				continue;
			}

			$tag_name = preg_replace( '#^refs/tags/#', '', $ref->ref );
			if ( ! is_string( $tag_name ) || ! $this->npuc_is_stable_package_tag( $tag_name ) ) {
				continue;
			}

			$versions_by_tag[ $tag_name ] = $this->npuc_version_from_tag( $tag_name );
		}

		if ( empty( $versions_by_tag ) ) {
			return array();
		}

		uksort(
			$versions_by_tag,
			static function ( string $tag_a, string $tag_b ) use ( $versions_by_tag ): int {
				return version_compare( $versions_by_tag[ $tag_b ], $versions_by_tag[ $tag_a ] );
			}
		);

		return array_keys( $versions_by_tag );
	}

	/**
	 * Whether a tag is a stable release for this package (not alpha/hotfix/epic).
	 *
	 * @param string $tag_name Full git tag name.
	 * @return bool
	 */
	protected function npuc_is_stable_package_tag( string $tag_name ): bool {
		if ( 0 !== strpos( $tag_name, $this->tag_prefix ) ) {
			return false;
		}

		$version = $this->npuc_version_from_tag( $tag_name );
		if ( '' === $version ) {
			return false;
		}

		if ( preg_match( '/[-.](alpha|beta|rc|hotfix|epic)/i', $version ) ) {
			return false;
		}

		return (bool) preg_match( '/^\d+(?:\.\d+)+$/', $version );
	}

	/**
	 * Strip the package prefix from a monorepo tag so WordPress can compare versions.
	 *
	 * @param string $tag_name Full git tag name.
	 * @return string
	 */
	protected function npuc_version_from_tag( string $tag_name ): string {
		if ( 0 !== strpos( $tag_name, $this->tag_prefix ) ) {
			return ltrim( $tag_name, 'v' );
		}

		return substr( $tag_name, strlen( $this->tag_prefix ) );
	}

	/**
	 * Build a PUC reference that downloads this package's release zip.
	 *
	 * @param object $release GitHub release object.
	 * @return Reference|null
	 */
	protected function npuc_reference_from_release( object $release ): ?Reference {
		$version = $this->npuc_version_from_tag( (string) $release->tag_name );

		$reference = new Reference(
			array(
				'name'        => $release->tag_name,
				'version'     => $version,
				'updated'     => $release->created_at,
				'apiResponse' => $release,
			)
		);

		$matching_assets = array();
		if ( isset( $release->assets ) && is_array( $release->assets ) ) {
			$matching_assets = array_values( array_filter( $release->assets, array( $this, 'matchesAssetFilter' ) ) );
		}

		if ( empty( $matching_assets ) ) {
			return null;
		}

		$asset                    = $matching_assets[0];
		$reference->downloadUrl   = $this->isAuthenticationEnabled() ? $asset->url : $asset->browser_download_url;
		$reference->downloadCount = $asset->download_count;

		if ( ! empty( $release->body ) ) {
			$reference->changelog = \Parsedown::instance()->text( $release->body );
		}

		return $reference;
	}
}
