<?php

namespace Emic\WP_UnitTestCase;

use \WP_UnitTestCase;

final class Tests_Utils extends WP_UnitTestCase {
	/**
	 * パスワードを暗号化したものを返す
	 *
	 * @param string $value 暗号化したい文字列.
	 * @return string
	 */
	public static function get_encrypted_value( $value ) {
		defined( 'FMPRESS_CONNECT_ENCRYPT_IV' )  || exit;
		defined( 'FMPRESS_CONNECT_ENCRYPT_KEY' ) || exit;

		$ciphertext = \ParagonIE_Sodium_Compat::crypto_aead_aes256gcm_encrypt(
			$value,
			'',
			hex2bin( FMPRESS_CONNECT_ENCRYPT_IV ),
			FMPRESS_CONNECT_ENCRYPT_KEY
		);

		return base64_encode( $ciphertext );
	}

	/**
	 * Private, Protected メソッドの実行結果を返す
	 *
	 * @param object|string $object クラス名、またはクラスのオブジェクト.
	 * @param string        $name   メソッド名.
	 * @param array         $args   メソッドの引数.
	 * @return mixed
	 */
	public static function run_non_public_method( $object, $name, $args = array() ) {
		$r = new \ReflectionClass( $object );
		$m = $r->getMethod( $name );
		$m->setAccessible( true );

		return $m->invokeArgs( $object, $args );
	}

	/**
	 * Private, Protected プロパティの値を返す
	 *
	 * @param object|string $object クラス名、またはクラスのオブジェクト.
	 * @param string        $name   プロパティ名.
	 * @return mixed
	 */
	public static function get_non_public_value( $object, $name ) {
		$r = new \ReflectionClass( $object );
		$p = $r->getProperty( $name );
		$p->setAccessible( true );

		return $p->getValue( $object );
	}
}
