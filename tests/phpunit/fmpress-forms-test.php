<?php
/**
 * Class FMPressFormsTest
 *
 * @package FMPress Forms
 */

require_once __DIR__ . '/tests-utils.php';
use Emic\WP_UnitTestCase\Tests_Utils as ETU;

/**
 * FMPress test case.
 */
class FMPressFormsTest extends WP_UnitTestCase {
	private static $plugin = null;
	private static $user_id;

	/**
	 * テスト前に実行する処理
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$plugin = new Emic\FMPress\Forms\FMPress_Forms();

		// Admin ユーザーを作成.
		self::$user_id = $factory->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'ja',
			)
		);
	}

	/**
	 * 各テスト前に毎回実行する処理
	 *
	 * @return void
	 */
	public function setUp(): void {
	}

	/**
	 * 各テスト後に毎回実行する処理
	 *
	 * @return void
	 */
	public function tearDown(): void {
	}

	/**
	 * 定数のテスト
	 *
	 * @return void
	 */
	public function test_constant_value() {
		$droot = 'true' === getenv( 'GITLAB_CI' ) ? '/home/wordpress' : '/var/www/html';

		$this->assertEquals( FMPRESS_FORMS_PLUGIN_DIR, "${droot}/wp-content/plugins/fmpress-forms" );
		$this->assertEquals( FMPRESS_FORMS_CF7_SETTINGS_KEY, 'fmpress_connect_settings_data' );
	}

	/**
	 * プラグインのバージョンをテスト
	 *
	 * @return void
	 */
	public function test_plugin_version() {
		$this->assertEquals( '1.3.1', self::$plugin::VERSION );

		$droot = 'true' === getenv( 'GITLAB_CI' ) ? '/home/wordpress' : '/var/www/html';
		$data = get_plugin_data("${droot}/wp-content/plugins/fmpress-forms/fmpress-forms.php");
		$this->assertEquals( self::$plugin::VERSION, $data['Version'] );
	}

	/**
	 * PHP の最小バージョンをテスト
	 *
	 * @return void
	 */
	public function test_minimum_php_version() {
		$this->assertEquals( '7.4.0', self::$plugin::MINIMUM_PHP_VERSION );
	}

	/**
	 * Contact Form 7 の最小バージョンをテスト
	 *
	 * @return void
	 */
	public function test_minimum_cf7_version() {
		$this->assertEquals( '5.5', self::$plugin::MINIMUM_CF7_VERSION );
	}

	/**
	 * ロケールの設定
	 *
	 * @return string
	 */
	public function filter_set_locale_to_ja() {
		return 'ja';
	}

	/**
	 * 翻訳ファイルがロードできるかテスト：fmpress-forms
	 *
	 * @return void
	 */
	public function test_textdomain_fmpress_forms() {
		add_filter( 'locale', array( $this, 'filter_set_locale_to_ja' ) );
		load_textdomain( 'fmpress-forms', FMPRESS_FORMS_PLUGIN_DIR . '/languages/fmpress-forms-ja.mo' );

		$this->assertEquals( get_locale(), 'ja' );
		$this->assertTrue( is_textdomain_loaded( 'fmpress-forms' ) );
		$this->assertSame( 'リレーションシップ', __( 'Relationship', 'fmpress-forms' ) );
		unload_textdomain( 'fmpress-forms' );
	}

	/**
	 * プラグイン有効化前のテスト
	 *
	 * @return void
	 */
	public function test_is_plugin_requirements() {
		$result = self::$plugin->is_require_php_version( self::$plugin::MINIMUM_PHP_VERSION );
		version_compare( PHP_VERSION, self::$plugin::MINIMUM_PHP_VERSION, '<' )
			? $this->assertFalse( $result )
			: $this->assertTrue( $result );
	}

	/**
	 * PHP バージョンによる挙動のテスト
	 *
	 * @return void
	 */
	public function test_is_require_php_version() {
		ob_start();
		$result = self::$plugin->is_require_php_version( self::$plugin::MINIMUM_PHP_VERSION );
		$actual = ob_get_clean();

		// 最低バージョン以上の場合.
		if ( version_compare( PHP_VERSION, self::$plugin::MINIMUM_PHP_VERSION, '>=' ) ) {
			$this->assertTrue( $result );
		}

		// 最低バージョンに満たない場合.
		else {
			$this->assertFalse( $result );
			$this->assertStringContainsString(
				esc_html(
					sprintf(
						'"%s" requires "PHP" version %s or greater.',
						self::$plugin::PLUGIN_NAME,
						self::$plugin::MINIMUM_PHP_VERSION
					)
				),
				$actual
			);
		}
	}

	/**
	 * FMPRESS_CONNECT_ENCRYPT_KEY が定義されているかテスト
	 *
	 * @return void
	 */
	public function test_is_defined_encrypt_key() {
		$this->assertTrue( defined( 'FMPRESS_CONNECT_ENCRYPT_KEY' ) );
		$this->assertTrue( self::$plugin->is_defined_encrypt_key() );
		$this->assertRegExp( '/^[a-z0-9!@#$%^&*()]{32}$/i', FMPRESS_CONNECT_ENCRYPT_KEY );
	}

	/**
	 * FMPRESS_CONNECT_ENCRYPT_IV が定義されているかテスト
	 *
	 * @return void
	 */
	public function test_is_defined_encrypt_iv() {
		$this->assertTrue( defined( 'FMPRESS_CONNECT_ENCRYPT_IV' ) );
		$this->assertTrue( self::$plugin->is_defined_encrypt_key() );
		$this->assertRegExp( '/^[0-9a-f]+$/i', FMPRESS_CONNECT_ENCRYPT_IV );
	}

	/**
	 * Contact Form 7 のバージョンによる挙動のテスト
	 *
	 * @return void
	 */
	public function test_is_require_cf7_version() {
		ob_start();
		$result = self::$plugin->is_require_cf7_version( self::$plugin::PLUGIN_NAME );
		$actual = ob_get_clean();

		// 最低バージョン以上の場合.
		if ( version_compare( WPCF7_VERSION, self::$plugin::MINIMUM_CF7_VERSION, '>=' ) ) {
			$this->assertTrue( $result );
		}

		// 最低バージョンに満たない場合.
		else {
			$this->assertFalse( $result );
			$this->assertStringContainsString(
				esc_html(
					sprintf(
						'"%s" requires "Contact Form 7" version %s or greater.',
						self::$plugin::PLUGIN_NAME,
						self::$plugin::MINIMUM_CF7_VERSION
					)
				),
				$actual
			);
		}

		$this->markTestIncomplete( 'Contact Form 7 のバージョンごとのテストをどうするか要検討' );
	}

	/**
	 * FMPress Forms Pro が有効な場合の挙動をテスト
	 *
	 * @return void
	 */
	public function test_is_activated_fmpress_forms() {
		// FMPress Forms と
		// FMPress Forms Pro の両方が有効な場合.
		do_action( 'fmpress_forms_pro_loaded' );
		$this->assertEquals( 1, did_action( 'fmpress_forms_loaded' ) );
		$this->assertEquals( 1, did_action( 'fmpress_forms_pro_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_connect_loaded' ) );

		ob_start();
		do_action( 'admin_notices' );
		$result = self::$plugin->is_activated_fmpress_forms();
		$actual = ob_get_clean();

		$this->assertFalse( $result );
		$this->assertStringContainsString(
			esc_html( sprintf( '"FMPress Forms" and "%s" cannot be activated simultaneously.', self::$plugin::PLUGIN_NAME ) ),
			$actual
		);

		// FMPress Forms Pro を無効に.
		global $wp_actions;

		if ( isset( $wp_actions['fmpress_forms_pro_loaded'] ) ) {
			unset( $wp_actions['fmpress_forms_pro_loaded'] );
		}

		// FMPress Forms のみ有効な場合.
		$this->assertEquals( 1, did_action( 'fmpress_forms_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_forms_pro_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_connect_loaded' ) );
		$this->assertTrue( self::$plugin->is_activated_fmpress_forms() );
	}

	/**
	 * FMPress Forms Pro のみが有効な場合の挙動をテスト
	 *
	 * @return void
	 */
	public function test_is_not_activated_fmpress_core() {
		// FMPress Connect は無効
		// FMPress Forms Pro が有効な場合.
		global $wp_actions;

		if ( isset( $wp_actions['fmpress_connect_loaded'] ) ) {
			unset( $wp_actions['fmpress_connect_loaded'] );
		}

		do_action( 'fmpress_forms_pro_loaded' );
		$this->assertEquals( 1, did_action( 'fmpress_forms_loaded' ) );
		$this->assertEquals( 1, did_action( 'fmpress_forms_pro_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_connect_loaded' ) );

		ob_start();
		$this->set_current_action();
		$result = self::$plugin->is_not_activated_fmpress_core();
		$this->set_current_action( true );
		$actual = ob_get_clean();

		$this->assertFalse( $result );
		$this->assertStringContainsString(
			esc_html(
				sprintf(
					'"%s" requires "FMPress Pro" to be installed and activated.',
					self::$plugin::PLUGIN_NAME
				)
			),
			$actual
		);

		// FMPress Connect
		// FMPress Forms Pro ともに無効な状態に戻す.
		if ( isset( $wp_actions['fmpress_forms_pro_loaded'] ) ) {
			unset( $wp_actions['fmpress_forms_pro_loaded'] );
		}

		$this->assertEquals( 1, did_action( 'fmpress_forms_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_forms_pro_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_connect_loaded' ) );
		$this->assertTrue( self::$plugin->is_not_activated_fmpress_core() );
	}

	/**
	 * FMPress Connect が有効な場合の挙動をテスト
	 *
	 * @return void
	 */
	public function test_is_activated_fmpress_core() {
		// FMPress Connect
		// FMPress Forms ともに有効な場合.
		do_action( 'fmpress_connect_loaded' );
		$this->assertEquals( 1, did_action( 'fmpress_forms_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_forms_pro_loaded' ) );
		$this->assertEquals( 1, did_action( 'fmpress_connect_loaded' ) );

		ob_start();
		$this->set_current_action();
		$result = self::$plugin->is_activated_fmpress_core();
		$this->set_current_action( true );
		$actual = ob_get_clean();

		$this->assertFalse( $result );
		$this->assertStringContainsString(
			esc_html( '"FMPress Forms" and "FMPress Pro" cannot be activated simultaneously.' ),
			$actual
		);

		// FMPress Connect を無効化.
		global $wp_actions;

		if ( isset( $wp_actions['fmpress_connect_loaded'] ) ) {
			unset( $wp_actions['fmpress_connect_loaded'] );
		}

		$this->assertEquals( 1, did_action( 'fmpress_forms_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_forms_pro_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_connect_loaded' ) );
		$this->assertTrue( self::$plugin->is_activated_fmpress_core() );
	}

	/**
	 * プラグインメニューの「設定」リンクのテスト
	 *
	 * @return void
	 */
	public function test_fmpress_add_link_to_settings() {
		$name = 'plugin_action_links_' . plugin_basename( FMPRESS_FORMS_PLUGIN_DIR ) . '/fmpress-forms.php';
		$this->assertTrue( has_filter( $name ) );
		$this->assertEquals(
			array( sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wpcf7' ), __( 'Settings' ) ) ),
			apply_filters( $name, array() )
		);
	}

	/**
	 * 必要なファイルが読み込まれているかテスト
	 *
	 * @return void
	 */
	public function test_fmpress_require_files() {
		$this->assertEquals( 1, did_action( 'fmpress_forms_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_forms_pro_loaded' ) );
		$this->assertEquals( 0, did_action( 'fmpress_connect_loaded' ) );
		$this->assertEquals( FMPRESS_CONNECT_NAMEPREFIX, 'fmpress_connect' );

		$includes = get_included_files();
		$this->assertIsArray( $includes );

		// 配列のキーと値を反転.
		$includes = array_flip( $includes );
		$this->assertArrayHasKey( FMPRESS_FORMS_PLUGIN_DIR . '/admin/class-admin.php', $includes );
		$this->assertArrayHasKey( FMPRESS_FORMS_PLUGIN_DIR . '/includes/class-forms.php', $includes );
		$this->assertArrayHasKey( FMPRESS_FORMS_PLUGIN_DIR . '/admin/class-datasources.php', $includes );
		$this->assertArrayHasKey( FMPRESS_FORMS_PLUGIN_DIR . '/admin/class-settings.php', $includes );
		$this->assertArrayHasKey( FMPRESS_FORMS_PLUGIN_DIR . '/includes/class-utils.php', $includes );
		$this->assertArrayHasKey( FMPRESS_FORMS_PLUGIN_DIR . '/includes/drivers/class-database.php', $includes );
		$this->assertArrayHasKey( FMPRESS_FORMS_PLUGIN_DIR . '/includes/drivers/class-fmdapi.php', $includes );
		$this->assertArrayHasKey( FMPRESS_FORMS_PLUGIN_DIR . '/includes/class-fm-value-list.php', $includes );
		$this->assertFalse( in_array( FMPRESS_FORMS_PLUGIN_DIR . '/includes/class-update-form.php', $includes, true ) );
	}

	/**
	 * 必要な CSS が読み込まれているか
	 *
	 * @return void
	 */
	public function test_fmpress_enqueue_styles() {
		set_current_screen( 'dashboard' );
		wp_set_current_user( self::$user_id );
		do_action( 'admin_menu' );

		$wp_styles = wp_styles();
		$this->assertTrue( wp_style_is( 'fmpress-connect-admin', 'registered' ) );
		$this->assertEquals( $wp_styles->registered['fmpress-connect-admin']->handle, 'fmpress-connect-admin' );
		$this->assertEquals( $wp_styles->registered['fmpress-connect-admin']->src, plugins_url( '/admin/css/admin.css', FMPRESS_FORMS_PLUGIN_DIR . '/fmpress.php' ) );
		$this->assertEquals( $wp_styles->registered['fmpress-connect-admin']->args, 'all' );
	}

	/**
	 * 必要な JS が読み込まれているか
	 *
	 * @return void
	 */
	public function test_fmpress_enqueue_scripts() {
		set_current_screen( 'dashboard' );
		wp_set_current_user( self::$user_id );
		do_action( 'admin_enqueue_scripts' );

		$wp_scripts = wp_scripts();
		$this->assertTrue( wp_style_is( 'fmpress-connect-admin', 'registered' ) );
		$this->assertEquals( $wp_scripts->registered['fmpress-connect-admin']->handle, 'fmpress-connect-admin' );
		$this->assertEquals( $wp_scripts->registered['fmpress-connect-admin']->src, plugins_url( '/admin/js/admin.min.js', FMPRESS_FORMS_PLUGIN_DIR . '/fmpress.php' ) );
		$this->assertSame( $wp_scripts->registered['fmpress-connect-admin']->deps, array() );
	}

	/**
	 * プラグインの無効化処理のテスト
	 *
	 * @return void
	 */
	public function test_fmpress_deactivate_plugin() {
		$_GET['activate'] = true;
		$this->assertSame( $_GET, array( 'activate' => true ) );

		// 無効化できたかのテスト方法を模索…
		self::$plugin->fmpress_deactivate_plugin();
		$this->assertFalse( isset( $_GET['activate'] ) );
	}

	/**
	 * 通知テキストのテスト
	 *
	 * @return void
	 */
	public function test_fmpress_show_notice() {
		$message  = 'テストメッセージが表示されているはず…';
		$expected = '<div class="notice notice-warning is-dismissible"><p>' . $message . '</p></div>' . PHP_EOL;

		// admin_notices で呼び出されていない場合.
		ob_start();
		ETU::run_non_public_method( self::$plugin, 'fmpress_show_notice', array( $message ) );
		$actual = ob_get_clean();

		$this->assertSame( '', $actual );

		// admin_notices で呼び出された場合.
		ob_start();
		$this->set_current_action();
		ETU::run_non_public_method( self::$plugin, 'fmpress_show_notice', array( $message ) );
		$this->set_current_action( true );
		$actual = ob_get_clean();

		$this->assertSame( $expected, $actual );
	}

	/**
	 * current_action() の判定に介入
	 *
	 * @param boolean $unset True/False.
	 * @return void
	 */
	private function set_current_action( $unset = false ) {
		global $wp_current_filter;

		if ( true === $unset ) {
			$wp_current_filter = array();
			return;
		}

		$wp_current_filter[] = 'admin_notices';
	}
}
