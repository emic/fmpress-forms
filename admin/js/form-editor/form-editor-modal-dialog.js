/**
 * Modal dialog.
 *
 * Generate modal dialog.
 *
 * @file   This files defines modal dialog.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import { capitalizeFirst } from './form-editor-utils.js';
import * as i18n from './i18n.js';
import * as sessions from './form-editor-sessions.js';

const editorId = 'fmpressFormsFormEditor';
const classPrefix = 'ffe-';

/**
 * Generate and display a modal.
 *
 * @param {string}  modalName
 * @param {string}  titleText
 * @param {Array}   messageArray
 * @param {Array}   buttonArray
 * @param {boolean} displayControl
 */
export function generateModal( modalName, titleText, messageArray, buttonArray, displayControl = false ) {
	if ( sessions.doNotDisplayModal( modalName ) ) {
		return;
	}

	const modal = document.createElement( 'DIALOG' );
	const wrapper = document.createElement( 'DIV' );
	const header = document.createElement( 'DIV' );
	const title = document.createElement( 'H3' );
	const body = document.createElement( 'DIV' );
	const footer = document.createElement( 'DIV' );
	const closeButton = document.createElement( 'BUTTON' );

	modal.id = `${ editorId }Modal`;
	modal.className = `${ classPrefix }modal-dialog`;

	wrapper.className = `${ classPrefix }modal-dialog-wrapper`;

	header.className = `${ classPrefix }modal-dialog-header`;

	title.className = `${ classPrefix }modal-dialog-title`;
	title.textContent = titleText;

	body.className = `${ classPrefix }modal-dialog-body`;

	footer.className = `${ classPrefix }modal-dialog-footer`;

	// Add close button.
	if ( buttonArray.length === 0 ) {
		closeButton.id = `${ editorId }CloseModal`;
		closeButton.className = `${ classPrefix }close-modal-dialog button-secondary`;
		closeButton.textContent = i18n.__( 'Close' );
		closeButton.addEventListener( 'click', removeModal, false );
		footer.appendChild( closeButton );
	}

	// Add custom buttons.
	for ( let i = 0; i < buttonArray.length; i++ ) {
		const el = document.createElement( 'BUTTON' );
		const btn = buttonArray[ i ];
		el.id = `${ editorId }ModalButton` + capitalizeFirst( btn.name );
		el.className = btn.className;
		el.className = `${ classPrefix }button-modal-dialog ` + btn.className;
		el.textContent = btn.label;
		el.addEventListener( 'click', btn.eventName, false );
		footer.appendChild( el );
	}

	// Add messages.
	messageArray.forEach( ( text ) => {
		const message = document.createElement( 'P' );
		message.textContent = text;
		body.appendChild( message );
	} );

	// Do not show this dialog.
	if ( displayControl ) {
		const checkboxWrapper = document.createElement( 'DIV' );
		const checkboxLabel = document.createElement( 'LABEL' );
		const checkboxSpan = document.createElement( 'SPAN' );
		const checkbox = document.createElement( 'INPUT' );
		checkboxWrapper.className = `${ classPrefix }modal-dialog-do-not-show-this-dialog`;
		checkboxSpan.textContent = i18n.__( 'Do not show this dialog' );
		checkbox.type = 'checkbox';
		checkbox.value = '1';
		checkbox.dataset.modalName = modalName;
		checkbox.addEventListener( 'click', sessions.saveModalSetting, false );
		checkboxLabel.appendChild( checkbox );
		checkboxLabel.appendChild( checkboxSpan );
		checkboxWrapper.appendChild( checkboxLabel );
		body.appendChild( checkboxWrapper );
	}

	header.appendChild( title );
	wrapper.appendChild( header );
	wrapper.appendChild( body );
	wrapper.appendChild( footer );
	modal.appendChild( wrapper );
	document.body.appendChild( modal );

	modal.showModal();
}

/**
 * Remove Modal.
 */
export function removeModal() {
	const modals = document.querySelectorAll( `.${ classPrefix }modal-dialog` );
	modals.forEach( ( modal ) => {
		modal.remove();
	} );
}
