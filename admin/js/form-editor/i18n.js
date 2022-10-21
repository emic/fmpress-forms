/**
 * i18n.
 *
 * Define Internationalization.
 *
 * @file   This files defines internationalization.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as ja from './lang/translation-ja.js';

/**
 * Returns translated text.
 *
 * @param {string} key
 * @return {string} Translated text.
 */
export function __( key ) {
	const lang = document.documentElement.lang;
	const translation = ja.translations.filter( ( t ) => t.lang === lang );

	if ( 0 === translation.length ) {
		return key;
	}

	const word = translation[ 0 ].words.filter( ( obj ) => Object.keys( obj )[ 0 ] === key );

	return 0 === word.length ? key : word[ 0 ][ key ];
}
