<?php

require_once dirname( __FILE__, 2 ) . '/tests-utils.php';
use Emic\WP_UnitTestCase\Tests_Utils as ETU;

class FMPressConnectUtilsTest extends WP_UnitTestCase {
	private static $utils = null;
	private static $user_id;
	private static $post_id;

	/**
	 * テスト前に実行する処理
	 *
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$utils = new Emic\FMPress\Connect\Utils();

		// Admin ユーザーを作成.
		self::$user_id = $factory->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'ja',
			)
		);

		// 投稿データを作成.
		self::$post_id = $factory->post->create();
	}

	/**
	 * 各テスト前に毎回実行する処理
	 *
	 * @return void
	 */
	public function setUp() {
	}

	/**
	 * 各テスト後に毎回実行する処理
	 *
	 * @return void
	 */
	public function tearDown() {
	}

	/**
	 * 必要なファイルが読み込まれているかテスト
	 *
	 * @return void
	 */
	public function test_requires() {
		$includes = get_included_files();
		$this->assertIsArray( $includes );

		// 配列のキーと値を反転.
		$includes = array_flip( $includes );
		$this->assertArrayHasKey( ABSPATH . 'wp-includes/sodium_compat/src/Core/Util.php', $includes );
		$this->assertArrayHasKey( ABSPATH . 'wp-includes/sodium_compat/src/Compat.php', $includes );
	}

	/**
	 * 日付フォーマットの検証をテスト
	 *
	 * @return void
	 */
	public function test_is_valid_date() {
		$contents = array(
			null,
			123,
			'Y',
			'Y-',
			'Y-m',
			'Y-m-',
			'Y-m-d',
			'Y-m-d-H',
			'2021',
			'2021-',
			'2021-01',
			'2021-01-',
			'2021-01-02-03',
		);

		foreach ( $contents as $c ) {
			$this->assertFalse( self::$utils::is_valid_date( $c ) );
		}

		$this->assertTrue( self::$utils::is_valid_date( '2021-01-02' ) );
	}

	/**
	 * レコード更新時の日付フォーマットをテスト
	 *
	 * @return void
	 */
	public function test_format_date_for_update() {
		$this->assertEmpty( self::$utils::format_date_for_update( null ) );
		$this->assertEquals( '01/02/2021', self::$utils::format_date_for_update( '2021-01-02' ) );
		$this->assertEquals( '01/02/2021', self::$utils::format_date_for_update( '2021-01-02-03' ) );
	}

	/**
	 * WP_Error オブジェクトのテスト
	 *
	 * @return void
	 */
	public function test_generate_wp_error() {
		// 第1引数の未指定.
		$message = 'エラーメッセージが入っているはず…';
		$error   = self::$utils::generate_wp_error( $message );
		$this->assertTrue( $error instanceof WP_Error );
		$this->assertEquals( 'fmpress_connect_core', $error->get_error_code() );
		$this->assertEquals( "FMPress: $message", $error->get_error_message( 'fmpress_connect_core' ) );

		// 第1・第3引数を指定.
		$message = 'カスタムのエラーメッセージが入っているはず…';
		$error   = self::$utils::generate_wp_error( $message, null, 'FMPressCustom' );
		$this->assertTrue( $error instanceof WP_Error );
		$this->assertEquals( 'fmpress_connect_core', $error->get_error_code() );
		$this->assertEquals( "FMPressCustom: $message", $error->get_error_message( 'fmpress_connect_core' ) );

		// 第1・第2引数のみ指定.
		$message = 'カスタムコードのエラーメッセージが入っているはず…';
		$error   = self::$utils::generate_wp_error( $message, 'custom' );
		$this->assertTrue( $error instanceof WP_Error );
		$this->assertEquals( 'fmpress_connect_custom', $error->get_error_code() );
		$this->assertEquals( "FMPress: $message", $error->get_error_message( 'fmpress_connect_custom' ) );
	}

	/**
	 * カスタムフィールドの戻り値をテスト
	 *
	 * @return void
	 */
	public function test_get_custom_field_value() {
		// カスタムフィールドを登録.
		$key   = '__DUMMY__';
		$value = '__DUMMY_DATA__';
		add_post_meta( self::$post_id, $key, $value );

		// 投稿 ID を指定した場合.
		$this->assertEquals( $value, self::$utils->get_custom_field_value( $key, true, self::$post_id ) );

		// 投稿 ID を省略した場合.
		global $post;
		$post = get_post( self::$post_id );
		$this->assertEquals( $value, self::$utils->get_custom_field_value( $key ) );

		// カスタムフィールドの破棄.
		$this->assertTrue( delete_post_meta( self::$post_id, $key ) );
	}

	/**
	 * データソースのテスト
	 *
	 * @return void
	 */
	public function test_get_datasource_info() {
		$_password = '___PASSWORD__';
		$extracts  = array(
			'driver'              => '___DRIVER__',
			'server'              => '___SERVER__',
			'datasource'          => '___DATASOURCE__',
			'datasource_username' => '___USERNAME__',
			'datasource_password' => ETU::get_encrypted_value( $_password ),
		);

		// カスタムフィールドを登録.
		foreach ( $extracts as $key => $value ) {
			add_post_meta( self::$post_id, FMPRESS_CONNECT_NAMEPREFIX . '_' . $key, $value );
		}

		// データを検証.
		$results = self::$utils->get_datasource_info( self::$post_id );

		foreach ( $results as $key => $value ) {
			$value = ( 'datasource_password' === $key ) ? $_password : $extracts[ $key ];
			$this->assertEquals( $value, $value );
		}

		// カスタムフィールドを破棄.
		foreach ( $extracts as $key => $value ) {
			delete_post_meta( self::$post_id, FMPRESS_CONNECT_NAMEPREFIX . '_' . $key );
		}
	}

	/**
	 * データソースを元にパスワードを取得できるかテスト
	 *
	 * @return void
	 */
	public function test_get_datasource_password() {
		$key_pass  = FMPRESS_CONNECT_NAMEPREFIX . '_datasource_password';
		$key_id    = FMPRESS_CONNECT_NAMEPREFIX . '_datasource_id';
		$_password = '___PASSWORD__';
		$password  = ETU::get_encrypted_value( $_password );

		// カスタムフィールドを登録.
		update_post_meta( self::$post_id, $key_pass, $password );
		update_post_meta( self::$post_id, $key_id, self::$post_id );

		// カスタムフィールドから取得する場合.
		$this->assertEquals( $_password, self::$utils->get_datasource_password( self::$post_id ) );

		// 第2引数で指定した場合.
		$this->assertEquals( $_password, self::$utils->get_datasource_password( self::$post_id, $password ) );

		// 投稿 ID を指定しなかった場合.
		global $post;
		$post = get_post( self::$post_id );
		$this->assertEquals( $_password, self::$utils->get_datasource_password() );

		// カスタムフィルードを破棄.
		$this->assertTrue( delete_post_meta( self::$post_id, $key_pass ) );
		$this->assertTrue( delete_post_meta( self::$post_id, $key_id ) );
	}

	/**
	 * データソースのカスタム投稿 ID を取得出来るかテスト
	 *
	 * @return void
	 */
	public function test_get_datasource_post_id() {
		// 投稿データが取得出来なかった場合.
		$this->assertEmpty( self::$utils->get_datasource_post_id() );

		// カスタムフィールドを登録.
		$key = FMPRESS_CONNECT_NAMEPREFIX . '_datasource_id';
		update_post_meta( self::$post_id, $key, self::$post_id );

		// 投稿データが取得出来た場合.
		global $post;
		$post = get_post( self::$post_id );
		$this->assertEquals( self::$post_id, self::$utils->get_datasource_post_id() );

		// カスタムフィルードを破棄.
		$this->assertTrue( delete_post_meta( self::$post_id, $key ) );
	}

	/**
	 * メンバーページかのテスト
	 *
	 * @return void
	 */
	public function test_is_member_page() {
		$this->assertFalse( self::$utils::is_member_page() );
	}
}
