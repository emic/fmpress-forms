/**
 * Sessions.
 *
 * Define sessions using session storage and local storage.
 *
 * @file   This files defines sessions.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as i18n from './i18n.js';
import * as modalDialog from './form-editor-modal-dialog.js';

export const sessionName = 'fmpress-forms';

/**
 * Initialize session.
 *
 * @return {Object} sessions.
 */
function init() {
	const sessions = { cf7Forms: {} };
	return sessions;
}

/**
 * Show modal dialog when save fails.
 *
 * @param {Array} messageArray
 */
function showErrorModal( messageArray ) {
	const modalName = 'showErrorModal';
	const titleText = i18n.__( 'Error' );
	const buttonArray = [];
	modalDialog.generateModal( modalName, titleText, messageArray, buttonArray );
}

/**
 * Save CF7 forms to session storage.
 */
export function saveCf7Form() {
	window.sessionStorage.removeItem( sessionName );

	const target = getCf7Form();
	if ( typeof target.value === 'undefined' ) {
		const messageArray = [ i18n.__( 'Failed to save form.' ) ];
		showErrorModal( messageArray );
		return false;
	} else if ( '' === target.value ) {
		return;
	}

	const sessionData = init();
	const replacedUri = replaceUri();
	sessionData.cf7Forms[ replacedUri ] = target.value;

	window.sessionStorage.setItem( sessionName, JSON.stringify( sessionData ) );
}

/**
 * Get Sessions.
 *
 * @return {Object} Sessions.
 */
export function getSessions() {
	const sessionsString = window.sessionStorage.getItem( sessionName );

	return ! sessionsString ? {} : JSON.parse( sessionsString );
}

/**
 * Restore the CF7 form in the session.
 */
export function restoreCf7Form() {
	const target = getCf7Form();
	const newSession = getCf7FormValue();
	target.value = newSession;
}

/**
 * Return CF7 form element.
 *
 * @return {HTMLElement} CF7 form element.
 */
function getCf7Form() {
	const target = document.getElementById( 'wpcf7-form' );
	if ( null === target ) {
		const messageArray = [ i18n.__( 'Cannot get CF7.' ) ];
		showErrorModal( messageArray );
	}

	return target;
}

/**
 * Return value of CF7 Form.
 *
 * @return {string} Value of CF7 Form.
 */
export function getCf7FormValue() {
	const sessions = getSessions();
	const replacedUri = replaceUri();

	if ( typeof sessions.cf7Forms !== 'undefined' ) {
		return sessions.cf7Forms[ replacedUri ];
	}

	return null;
}

/**
 * Remove unnecessary parameters from the URI.
 *
 * @return {string} URI.
 */
function replaceUri() {
	return encodeURI( window.location.href ).replace( /&active-tab=\d/, '' ).replace( '&action=edit', '' );
}

/**
 * Save modal setting.
 *
 * @param {Object} e
 */
export function saveModalSetting( e ) {
	const modalName = e.target.dataset.modalName;

	const session = window.localStorage.getItem( sessionName );

	let obj = { hideModal: {} };
	if ( null !== session ) {
		obj = JSON.parse( session );
	}

	obj.hideModal[ modalName ] = e.target.checked;

	window.localStorage.setItem( 'fmpress-forms', JSON.stringify( obj ) );
}

/**
 * Get modal setting.
 *
 * @param {string} modalName
 * @return {boolean} True if the modal is not displayed.
 */
export function doNotDisplayModal( modalName ) {
	const session = window.localStorage.getItem( sessionName );
	if ( null === session ) {
		return false;
	}

	const obj = JSON.parse( session );

	return obj.hideModal[ modalName ];
}
