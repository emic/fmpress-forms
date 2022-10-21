/**
 * Utility functions.
 *
 * Define utility functions.
 *
 * @file   This files defines utility functions.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

const editorId = 'fmpressFormsFormEditor';
const classPrefix = 'ffe-';
const datasetPrefix = classPrefix.substring( 0, 3 );

export const tagsUsingMultipleValues = [ 'checkbox', 'radio', 'select' ];

/**
 * Generate random strings.
 */
export function generateRandomId() {
	return '_' + Math.random().toString( 36 ).substring( 2 );
}

/**
 * Getting URL parameters.
 *
 * @param {string} name
 * @param {string} url
 * @return {string} URL argument value.
 */
export function getQueryVar( name, url ) {
	if ( ! url ) {
		url = window.location.href;
	}
	name = name.replace( /[\[\]]/g, '\\$&' );
	const regex = new RegExp( '[?&]' + name + '(=([^&#]*)|&|#|$)' ),
		results = regex.exec( url );
	if ( ! results ) {
		return null;
	}
	if ( ! results[ 2 ] ) {
		return null;
	}
	return decodeURIComponent( results[ 2 ].replace( /\+/g, ' ' ) );
}

/**
 * Whether it is CF7 post edit screen.
 */
export function isEditPostOfCf7() {
	const page = getQueryVar( 'page' );
	const post = getQueryVar( 'post' );

	if ( 'wpcf7' === page && Math.trunc( post ) > 0 ) {
		return true;
	}

	return false;
}

/**
 * Return form element id.
 *
 * @param {HTMLElement} el
 * @return {string} Form element id.
 */
export function getFormElementId( el ) {
	return el.dataset[ `${ datasetPrefix }FormElementId` ];
}

/**
 * Get the title of the form element (SUMMARY element).
 *
 * @param {Object} el
 * @return {null|HTMLElement} HTML SUMMARY element.
 */
export function getFormElementTitle( el ) {
	const formElementId = getFormElementId( el );
	const selector = `.${ classPrefix }form-element-title[data-${ datasetPrefix }-form-element-id="${ formElementId }"]`;
	return document.querySelector( selector );
}

/**
 * Capitalize first letter.
 *
 * @param {string} str
 * @return {string} Capitalize only first letter.
 */
export function capitalizeFirst( str ) {
	if ( typeof str === 'undefined' ) {
		return;
	}

	return str.charAt( 0 ).toUpperCase() + str.slice( 1 );
}

/**
 * Determine if WordPress Ajax is ready.
 *
 * @return {boolean} True if WordPress Ajax is ready.
 */
export function isReadyForWpAjax() {
	if ( typeof ajaxurl === 'undefined' || typeof localize === 'undefined' ) {
		return false;
	}

	return true;
}

/**
 * Determine if a tag uses multiple values.
 *
 * @param {string} cf7FormTagType
 * @return {boolean} True if tag is use multiple value.
 */
export function useMultipleValues( cf7FormTagType ) {
	const multiple = tagsUsingMultipleValues;
	if ( multiple.includes( cf7FormTagType ) ) {
		return true;
	}

	return false;
}

/**
 * Does a form editor exist?
 *
 * @return {boolean} True if editor exists.
 */
export function existsFormEditor() {
	const target = document.getElementById( editorId );
	if ( null === target ) {
		return false;
	}

	return true;
}

/**
 * Is the form editor displayed?
 *
 * @return {(null|boolean)} True if editor displayed.
 */
export function isShowFormEditor() {
	if ( ! existsFormEditor() ) {
		return null;
	}

	const target = getFormEditor();
	if ( target.classList.contains( 'show' ) ) {
		return true;
	}

	return false;
}

/**
 * Get Form Editor.
 */
export function getFormEditor() {
	return document.getElementById( editorId );
}

/**
 * Get count of form elements.
 *
 * @return {number} Count of form elements.
 */
export function getCountOfFormElements() {
	const selector = `.${ classPrefix }form-element`;
	const els = document.querySelectorAll( selector );
	return els.length;
}
