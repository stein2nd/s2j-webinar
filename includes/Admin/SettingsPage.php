<?php
/**
 * Site settings: integration (Settings → S2J Webinar) and provider credentials (GatherPress → Webinar).
 *
 * @package S2J_Webinar
 */

declare(strict_types=1);

namespace S2J\Webinar\Admin;

use S2J\Webinar\Constants;
use S2J\Webinar\GatherPress\DependencyStatus;
use S2J\Webinar\OAuth\TokenStore;
use S2J\Webinar\Provider\WebinarProvider;

final class SettingsPage {

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_notices', array( self::class, 'render_notice' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_styles' ) );
		add_action(
			'admin_post_' . Constants::ADMIN_POST_SAVE_GP_SETTINGS,
			array( self::class, 'handle_gatherpress_tab_save' )
		);
	}

	public static function add_menu(): void {
		add_options_page(
			__( 'S2J Webinar', 's2j-webinar' ),
			__( 'S2J Webinar', 's2j-webinar' ),
			'manage_options',
			Constants::SETTINGS_PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	public static function enqueue_styles( string $hook ): void {
		$on_options = $hook === 'settings_page_' . Constants::SETTINGS_PAGE_SLUG;
		$on_gp_tab  = $hook === 'gatherpress_event_page_gatherpress_webinar_settings';
		if ( ! $on_options && ! $on_gp_tab ) {
			return;
		}
		$style = S2J_WEBINAR_PLUGIN_DIR . 'dist/css/s2j-webinar-admin.css';
		if ( is_readable( $style ) ) {
			wp_enqueue_style(
				's2j-webinar-admin',
				S2J_WEBINAR_PLUGIN_URL . 'dist/css/s2j-webinar-admin.css',
				array(),
				(string) filemtime( $style )
			);
		}
	}

	public static function register_settings(): void {
		register_setting(
			's2j_webinar_settings',
			's2j_webinar_zoom_client_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			's2j_webinar_settings',
			's2j_webinar_zoom_client_secret',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( self::class, 'sanitize_client_secret' ),
			)
		);

		register_setting(
			's2j_webinar_settings',
			's2j_webinar_webhook_secret',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( self::class, 'sanitize_webhook_secret' ),
			)
		);

		register_setting(
			's2j_webinar_settings',
			Constants::OPTION_SHOW_GP_SETTINGS_TAB,
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => array( self::class, 'sanitize_show_gp_settings_tab' ),
			)
		);
	}

	public static function show_gatherpress_settings_tab(): bool {
		return (bool) get_option( Constants::OPTION_SHOW_GP_SETTINGS_TAB, true );
	}

	/**
	 * Active provider slug (e.g. zoom). Future: persisted option + selector on Settings → S2J Webinar.
	 */
	public static function webinar_provider_slug(): string {
		return WebinarProvider::slug();
	}

	public static function webinar_provider_label(): string {
		return WebinarProvider::label();
	}

	public static function connect_provider_button_label(): string {
		return \sprintf(
			/* translators: %s: webinar provider name, e.g. Zoom */
			__( 'Connect %s', 's2j-webinar' ),
			WebinarProvider::label()
		);
	}

	/**
	 * @param mixed $value
	 */
	public static function sanitize_show_gp_settings_tab( $value ): bool {
		if ( \is_bool( $value ) ) {
			return $value;
		}
		return \is_scalar( $value ) && (string) $value === '1';
	}

	/**
	 * @param mixed $value
	 */
	public static function sanitize_client_secret( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( $value === '' ) {
			return TokenStore::get_client_secret();
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 */
	public static function sanitize_webhook_secret( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( $value === '' ) {
			return (string) get_option( 's2j_webinar_webhook_secret', '' );
		}
		return $value;
	}

	/** Persist OAuth / webhook fields from GatherPress → Webinar (not wp-admin/options.php). */
	public static function handle_gatherpress_tab_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 's2j-webinar' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 's2j_webinar_settings-options' );

		$keys = array(
			's2j_webinar_zoom_client_id',
			's2j_webinar_zoom_client_secret',
			's2j_webinar_webhook_secret',
		);

		foreach ( $keys as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$value = wp_unslash( $_POST[ $key ] );
			/** @var mixed $value */
			$value = apply_filters( 'sanitize_option_' . $key, $value );
			update_option( $key, $value, false );
		}

		add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 's2j-webinar' ), 'success' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		wp_safe_redirect(
			add_query_arg( 'settings-updated', 'true', DependencyStatus::gatherpress_settings_url() )
		);
		exit;
	}

	public static function render_notice(): void {
		if ( ! self::is_settings_screen() ) {
			return;
		}
		$code = isset( $_GET['s2j_webinar_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['s2j_webinar_notice'] ) ) : '';
		if ( $code === '' ) {
			return;
		}
		$provider = self::webinar_provider_label();
		$messages = array(
			'connected'             => \sprintf(
				/* translators: %s: webinar provider name, e.g. Zoom */
				__( '%s account connected.', 's2j-webinar' ),
				$provider
			),
			'disconnected'          => \sprintf(
				/* translators: %s: webinar provider name, e.g. Zoom */
				__( '%s connection removed.', 's2j-webinar' ),
				$provider
			),
			'missing_credentials'   => __( 'Enter Client ID and Client Secret before connecting.', 's2j-webinar' ),
			'oauth_denied'          => \sprintf(
				/* translators: %s: webinar provider name, e.g. Zoom */
				__( '%s authorization was not completed.', 's2j-webinar' ),
				$provider
			),
			'oauth_exchange_failed' => __( 'Could not exchange the authorization code. Check credentials and redirect URL.', 's2j-webinar' ),
		);
		if ( ! isset( $messages[ $code ] ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
			esc_html( $messages[ $code ] )
		);
	}

	private static function is_settings_screen(): bool {
		if ( ! isset( $_GET['page'] ) ) {
			return false;
		}
		$page = sanitize_key( wp_unslash( (string) $_GET['page'] ) );
		if ( $page === Constants::SETTINGS_PAGE_SLUG ) {
			return true;
		}
		if ( class_exists( '\GatherPress\Core\Utility' ) ) {
			return $page === \GatherPress\Core\Utility::prefix_key( 'webinar_settings' );
		}
		return false;
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap s2j-webinar-settings">';
		echo '<h1>' . esc_html__( 'S2J Webinar', 's2j-webinar' ) . '</h1>';
		self::render_page_inner();
		echo '</div>';
	}

	/** Settings → S2J Webinar: GatherPress status + integration preferences. */
	public static function render_page_inner(): void {
		self::render_gatherpress_status();
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 's2j_webinar_settings' ); ?>
			<table class="form-table" role="presentation">
				<?php self::render_gatherpress_tab_toggle_row(); ?>
				<?php self::render_provider_row(); ?>
			</table>
			<?php submit_button( __( 'Save settings', 's2j-webinar' ) ); ?>
		</form>
		<p class="description s2j-webinar-settings__footer-note">
			<?php
			esc_html_e(
				'S2J Webinar settings are saved above. Event panel controls load in the block editor.',
				's2j-webinar'
			);
			?>
		</p>
		<?php
	}

	/** GatherPress Settings → Webinar: OAuth, webhooks, connection. */
	public static function render_gatherpress_tab_inner(): void {
		self::render_provider_setup_guide_link();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( Constants::ADMIN_POST_SAVE_GP_SETTINGS ); ?>" />
			<?php wp_nonce_field( 's2j_webinar_settings-options' ); ?>
			<table class="form-table" role="presentation">
				<?php self::render_zoom_credential_rows(); ?>
				<?php self::render_connection_row(); ?>
			</table>
			<?php submit_button( __( 'Save settings', 's2j-webinar' ) ); ?>
		</form>
		<p class="s2j-webinar-settings__actions">
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=s2j_webinar_oauth_connect' ), 's2j_webinar_oauth_connect' ) ); ?>"><?php echo esc_html( self::connect_provider_button_label() ); ?></a>
			<?php if ( TokenStore::is_connected() ) : ?>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=s2j_webinar_oauth_disconnect' ), 's2j_webinar_oauth_disconnect' ) ); ?>"><?php esc_html_e( 'Disconnect', 's2j-webinar' ); ?></a>
			<?php endif; ?>
		</p>
		<?php
	}

	public static function provider_setup_guide_url(): string {
		/**
		 * URL to provider setup documentation (Marketplace ↔ WordPress fields).
		 *
		 * @param string $url Default GitHub docs URL.
		 */
		return (string) apply_filters(
			's2j_webinar_provider_setup_guide_url',
			Constants::DOCS_PROVIDER_SETUP_GUIDE_URL
		);
	}

	private static function render_provider_setup_guide_link(): void {
		$url = self::provider_setup_guide_url();
		if ( $url === '' ) {
			return;
		}

		$provider = self::webinar_provider_label();
		echo '<p class="description s2j-webinar-settings__setup-guide">';
		echo wp_kses(
			sprintf(
				/* translators: 1: webinar provider name, 2: opening anchor tag, 3: closing anchor tag */
				__( 'Where to find each value in the %1$s developer console: %2$sProvider setup guide%3$s.', 's2j-webinar' ),
				esc_html( $provider ),
				'<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
					'rel'    => array(),
				),
			)
		);
		echo '</p>';
	}

	private static function render_provider_row(): void {
		$guide = __(
			'Which webinar platform this site uses. Additional providers may be selectable here in a future release.',
			's2j-webinar'
		);
		self::render_readonly_row(
			__( 'Webinar provider', 's2j-webinar' ),
			$guide,
			'<strong>' . esc_html( self::webinar_provider_label() ) . '</strong>'
		);
	}

	private static function render_zoom_credential_rows(): void {
		$provider = self::webinar_provider_label();
		self::render_field_row(
			's2j_webinar_zoom_client_id',
			__( 'Client ID', 's2j-webinar' ),
			\sprintf(
				/* translators: 1: webinar provider name, 2: same as 1 when users click Connect */
				__( 'From your %1$s OAuth app (General app). Identifies this WordPress site to %2$s when users click Connect.', 's2j-webinar' ),
				$provider,
				$provider
			),
			'<input name="s2j_webinar_zoom_client_id" id="s2j_webinar_zoom_client_id" type="text" class="regular-text" value="' . esc_attr( TokenStore::get_client_id() ) . '" autocomplete="off" />'
		);
		self::render_field_row(
			's2j_webinar_zoom_client_secret',
			__( 'Client Secret', 's2j-webinar' ),
			__( 'Proves your site owns the OAuth app. Stored encrypted in the database; leave blank to keep the saved secret.', 's2j-webinar' ),
			'<input name="s2j_webinar_zoom_client_secret" id="s2j_webinar_zoom_client_secret" type="password" class="regular-text" value="" placeholder="' . esc_attr( TokenStore::get_client_secret() !== '' ? '********' : '' ) . '" autocomplete="new-password" />'
		);
		self::render_readonly_row(
			__( 'Redirect URI', 's2j-webinar' ),
			\sprintf(
				/* translators: 1: webinar provider name, 2: same as 1 after users approve access */
				__( 'Add this exact URL to your %1$s app’s Redirect URL allow list. %2$s sends users back here after they approve access.', 's2j-webinar' ),
				$provider,
				$provider
			),
			'<code>' . esc_html( TokenStore::redirect_uri() ) . '</code>'
		);
		self::render_field_row(
			's2j_webinar_webhook_secret',
			__( 'Webhook secret', 's2j-webinar' ),
			\sprintf(
				/* translators: 1: webinar provider name, 2: same as 1 for webhook origin */
				__( 'From %1$s Event Subscriptions. Used to verify that started/ended notifications really come from %2$s.', 's2j-webinar' ),
				$provider,
				$provider
			),
			'<input name="s2j_webinar_webhook_secret" id="s2j_webinar_webhook_secret" type="password" class="regular-text" value="" placeholder="' . esc_attr( get_option( 's2j_webinar_webhook_secret', '' ) !== '' ? '********' : '' ) . '" autocomplete="new-password" />'
		);
		$webhook_url = rest_url( Constants::REST_NAMESPACE . '/webhook/zoom' );
		self::render_readonly_row(
			__( 'Webhook URL', 's2j-webinar' ),
			\sprintf(
				/* translators: 1: webinar provider name, 2: same as 1 for notifications */
				__( 'Paste into %1$s Event Subscriptions endpoint URL so %2$s can notify this site when a webinar starts or ends.', 's2j-webinar' ),
				$provider,
				$provider
			),
			'<code>' . esc_html( $webhook_url ) . '</code>'
		);
	}

	private static function render_gatherpress_tab_toggle_row(): void {
		$checked = self::show_gatherpress_settings_tab();
		$label   = __( 'Show Webinar tab in GatherPress Settings', 's2j-webinar' );
		$guide   = __(
			'When enabled, a Webinar tab appears near Tools and Alpha under GatherPress Settings.',
			's2j-webinar'
		);
		$control = '<input type="hidden" name="' . esc_attr( Constants::OPTION_SHOW_GP_SETTINGS_TAB ) . '" value="0" />'
			. '<label for="' . esc_attr( Constants::OPTION_SHOW_GP_SETTINGS_TAB ) . '">'
			. '<input name="' . esc_attr( Constants::OPTION_SHOW_GP_SETTINGS_TAB ) . '" id="' . esc_attr( Constants::OPTION_SHOW_GP_SETTINGS_TAB ) . '" type="checkbox" value="1" ' . checked( $checked, true, false ) . ' /> '
			. esc_html__( 'Enabled', 's2j-webinar' )
			. '</label>';
		self::render_field_row( Constants::OPTION_SHOW_GP_SETTINGS_TAB, $label, $guide, $control );
	}

	private static function render_gatherpress_status(): void {
		$ready  = DependencyStatus::is_ready();
		$gp     = DependencyStatus::is_gatherpress_active();
		$alpha  = DependencyStatus::is_gatherpress_alpha_active();
		$events = DependencyStatus::has_event_post_type();
		$class  = $ready ? 'notice-success' : 'notice-warning';
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> s2j-webinar-settings__gp-status">
			<p><strong><?php esc_html_e( 'GatherPress', 's2j-webinar' ); ?></strong></p>
			<ul class="s2j-webinar-settings__gp-list">
				<li>
					<?php
					echo esc_html(
						$gp
							? __( 'GatherPress plugin: active', 's2j-webinar' )
							: __( 'GatherPress plugin: not active — install and activate GatherPress to edit webinars on events.', 's2j-webinar' )
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						$events
							? __( 'Event post type: available', 's2j-webinar' )
							: __( 'Event post type: not found — check GatherPress activation.', 's2j-webinar' )
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						$alpha
							? __( 'GatherPress alpha: active (companion for current GatherPress releases)', 's2j-webinar' )
							: __( 'GatherPress alpha: not active — wp.org GatherPress may require the alpha companion plugin.', 's2j-webinar' )
					);
					?>
				</li>
			</ul>
			<?php if ( $gp && self::show_gatherpress_settings_tab() ) : ?>
				<p>
					<a href="<?php echo esc_url( DependencyStatus::gatherpress_settings_url() ); ?>">
						<?php esc_html_e( 'Open GatherPress Settings → Webinar tab', 's2j-webinar' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_connection_row(): void {
		$connected      = TokenStore::is_connected();
		$guide          = __( 'Authorize once so editors can sync webinars from GatherPress events. Tokens stay on the server; editors never see secrets.', 's2j-webinar' );
		$connect_action = self::connect_provider_button_label();
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Connection status', 's2j-webinar' ); ?></th>
			<td>
				<p class="description"><?php echo esc_html( $guide ); ?></p>
				<?php if ( $connected ) : ?>
					<p><?php esc_html_e( 'Status: connected', 's2j-webinar' ); ?></p>
					<?php
					$email = TokenStore::get_account_email();
					if ( $email !== '' ) :
						?>
						<p><?php echo esc_html( sprintf( /* translators: %s: provider account email */ __( 'Account: %s', 's2j-webinar' ), $email ) ); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: connect button label, e.g. Connect Zoom */
								__( 'Status: not connected — save Client ID/Secret, then click %s.', 's2j-webinar' ),
								$connect_action
							)
						);
						?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private static function render_field_row( string $id, string $label, string $description, string $control_html ): void {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<?php echo $control_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_attr above ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			</td>
		</tr>
		<?php
	}

	private static function render_readonly_row( string $label, string $description, string $value_html ): void {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<?php echo $value_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in caller ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			</td>
		</tr>
		<?php
	}
}
