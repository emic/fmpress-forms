/**
 * Events.
 *
 * Event interface.
 *
 * @file   This files defines events.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as formElements from './form-editor-form-elements.js';
import * as formGenerator from './form-editor-form-generator.js';
import * as i18n from './i18n.js';
import * as modal from './form-editor-modal-dialog.js';
import * as utils from './form-editor-utils.js';
import * as validator from './form-editor-validator.js';

const editorId = 'fmpressFormsFormEditor';
const classPrefix = 'ffe-';
const datasetPrefix = classPrefix.substring( 0, 3 );

/**
 * Show modal when saving.
 *
 * @param {Object} e
 */
export function savingModal( e ) {
	e.preventDefault();

	const count = utils.getCountOfFormElements();
	if ( 0 === count ) {
		modalDisplayOnNoFormElements();
		return;
	}

	const items = document.querySelectorAll( `.${ classPrefix }form-element` );
	items.forEach( ( item ) => {
		validator.validateFormElementSettings( item );
	} );

	const errorItems = document.querySelectorAll( `.${ classPrefix }form-element.${ classPrefix }validated-invalid` );
	if ( errorItems.length > 0 ) {
		modalDisplayOnValidationFailure();
	} else {
		modalDisplayOnSuccessfulValidation();
	}
}

/**
 * Modal to display when there is no form element.
 */
function modalDisplayOnNoFormElements() {
	const modalName = 'modalDisplayOnNoFormElements';
	const titleText = i18n.__( 'Cannot save' );
	const messageArray = [ i18n.__( 'Cannot save because there are no form elements.' ) ];
	const buttonArray = [
		{ name: 'close', className: 'button-secondary', label: i18n.__( 'Close' ), eventName: modal.removeModal },
	];
	const displayControl = false;
	modal.generateModal( modalName, titleText, messageArray, buttonArray, displayControl );
}

/**
 * Modal display on validation failure.
 */
function modalDisplayOnValidationFailure() {
	const modalName = 'modalDisplayOnValidationFailure';
	const titleText = i18n.__( 'Cannot save' );
	const messageArray = [ i18n.__( 'Correct any items with errors before saving.' ) ];
	const buttonArray = [
		{ name: 'close', className: 'button-secondary', label: i18n.__( 'Close' ), eventName: modal.removeModal },
	];
	const displayControl = false;
	modal.generateModal( modalName, titleText, messageArray, buttonArray, displayControl );
}

/**
 * Modal display on successful validation.
 */
function modalDisplayOnSuccessfulValidation() {
	const modalName = 'modalDisplayOnSuccessfulValidation';
	const titleText = i18n.__( 'Save' );
	const messageArray = [ i18n.__( 'Save your edits in FMPress Forms editor. The current Contact Form 7 form will be overwritten (if you press save, you will not be able to return to the state before the overwrite). From now on, use Contact Form 7\'s form editor to edit the form.' ) ];
	const buttonArray = [
		{ name: 'cancel', className: 'button-secondary', label: i18n.__( 'Cancel' ), eventName: modal.removeModal },
		{ name: 'save', className: 'button-secondary', label: i18n.__( 'Save' ), eventName: saveForm },
	];
	const displayControl = false;
	modal.generateModal( modalName, titleText, messageArray, buttonArray, displayControl );
}

/**
 * Save form.
 */
function saveForm() {
	const idText = 'wpcf7-admin-form-element';
	const form = document.getElementById( idText );
	if ( null === form ) {
		return;
	}

	const input = document.createElement( 'INPUT' );
	input.name = 'fmpress-form-editor-form-structure';
	input.type = 'hidden';
	const formStructure = getFormStructure();
	input.value = JSON.stringify( formStructure );

	form.appendChild( input );
	form.submit();
}

/**
 * Get form structure.
 *
 * @return {Object} Form structure.
 */
function getFormStructure() {
	const form = {};
	const els = document.querySelectorAll( `.${ classPrefix }form-element-title` );

	els.forEach( ( el ) => {
		const formElementId = utils.getFormElementId( el );
		const selector = `.${ classPrefix }form-element-content-form-tag-name[data-${ datasetPrefix }-form-element-id="${ formElementId }"] .${ classPrefix }form-element-content-text`;
		const tagNameEl = document.querySelector( selector );
		if ( null !== tagNameEl ) {
			const tagName = `fm_field-${ tagNameEl.value }`;
			form[ tagName ] = el.textContent;
		}
	} );

	return form;
}

/**
 * Add element to form area.
 *
 * @param {Object} e
 */
export function addElementToForm( e ) {
	e.preventDefault();

	// Exit if the same field element already exists.
	const fieldId = e.target.dataset[ `${ datasetPrefix }FieldId` ];
	const el = document.getElementById( `${ editorId }FormElements` );
	const selector = `.${ classPrefix }form-element[data-${ classPrefix }field-id="${ fieldId }"]`;
	const field = el.querySelector( selector );
	if ( null !== field ) {
		return;
	}

	const idText = `${ editorId }FormElements`;
	const parent = document.getElementById( idText );
	if ( null === parent ) {
		return;
	}

	const item = formElements.generateFormElement( e );

	parent.appendChild( item );

	validator.validateFormElementSettings( item );

	setrequired( item );

	formGenerator.setFormTags();
}

/**
 * Event to generate a form tag and set it to the input element.
 *
 * @param {Object} e
 */
export function setFormTags( e ) {
	e.preventDefault();
	formGenerator.setFormTags();
}

/**
 * Checkbox event to set required fields.
 *
 * @param {Object} e
 */
export function checkRequired( e ) {
	// Get form element title.
	const formElementTitle = utils.getFormElementTitle( e.target );
	if ( null === formElementTitle ) {
		return;
	}

	// Set class name.
	if ( e.target.checked ) {
		formElementTitle.classList.add( 'required' );
	} else {
		formElementTitle.classList.remove( 'required' );
	}
}

/**
 * Select box event to set CF7 type.
 *
 * @param {Object} e
 */
export function changeCf7TagType( e ) {
	if ( '' === e.target.value ) {
		return;
	}

	// Get form element wrapper.
	const formElementId = utils.getFormElementId( e.target );

	// Show hide settings.
	showHideValueListArea( formElementId, e.target.value );
	showHideFiletypes( formElementId, e.target.value );
	showHideLimit( formElementId, e.target.value );
}

/**
 * Display control of value list.
 *
 * @param {string} formElementId
 * @param {string} value
 */
function showHideValueListArea( formElementId, value ) {
	const selector = `.${ classPrefix }form-element-content-value-list[data-${ classPrefix }form-element-id="${ formElementId }"]`;
	const valueListArea = document.querySelector( selector );
	if ( null === valueListArea ) {
		return null;
	}

	const multiValueTag = utils.useMultipleValues( value );

	if ( multiValueTag ) {
		valueListArea.classList.remove( 'fade-out' );
		valueListArea.classList.add( 'fade-in' );
	} else {
		valueListArea.classList.remove( 'fade-in' );
		valueListArea.classList.add( 'fade-out' );
	}
}

/**
 * Display control of filetype.
 *
 * @param {string} formElementId
 * @param {string} value
 */
function showHideFiletypes( formElementId, value ) {
	const selector = `.${ classPrefix }form-element-content-filetypes[data-${ classPrefix }form-element-id="${ formElementId }"]`;
	const filetypesArea = document.querySelector( selector );
	if ( null === filetypesArea ) {
		return null;
	}

	if ( 'file' === value ) {
		filetypesArea.classList.remove( 'fade-out' );
		filetypesArea.classList.add( 'fade-in' );
	} else {
		filetypesArea.classList.remove( 'fade-in' );
		filetypesArea.classList.add( 'fade-out' );
	}
}

/**
 * Display control of limit of upload file.
 *
 * @param {string} formElementId
 * @param {string} value
 */
function showHideLimit( formElementId, value ) {
	const selector = `.${ classPrefix }form-element-content-limit[data-${ classPrefix }form-element-id="${ formElementId }"]`;
	const filetypesArea = document.querySelector( selector );
	if ( null === filetypesArea ) {
		return null;
	}

	if ( 'file' === value ) {
		filetypesArea.classList.remove( 'fade-out' );
		filetypesArea.classList.add( 'fade-in' );
	} else {
		filetypesArea.classList.remove( 'fade-in' );
		filetypesArea.classList.add( 'fade-out' );
	}
}

/**
 * Delete form element.
 *
 * @param {Object} e
 */
export function deleteFormElement( e ) {
	e.preventDefault();

	const formElement = getFormElement( e.target );
	formElement.remove();

	changeFieldElementIcon( e.target );
}

/**
 * Change field element icons to plus.
 *
 * @param {HTMLElement} el
 */
function changeFieldElementIcon( el ) {
	const datasetName = `${ datasetPrefix }FieldId`;
	const fieldId = el.dataset[ datasetName ];

	if ( typeof fieldId === 'undefined' ) {
		return;
	}

	const selector = `.${ classPrefix }add-element-to-form-btn[data-${ classPrefix }field-id="${ fieldId }"]`;
	const field = document.querySelector( selector );
	if ( null !== field ) {
		const classes = [ `${ classPrefix }field-added`, 'no-submit' ];
		field.classList.remove( ...classes );
	}
}

/**
 * Get form elements (DETAILS element).
 *
 * @param {Object} el
 * @return {null|HTMLElement} HTML DETAILS element.
 */
function getFormElement( el ) {
	const formElementId = utils.getFormElementId( el );
	const selector = `.${ classPrefix }form-element[data-${ datasetPrefix }-form-element-id="${ formElementId }"]`;
	return document.querySelector( selector );
}

/**
 * Reflects field options not empty.
 *
 * @param {HTMLElement} el
 * @return {undefined} Undefined.
 */
function setrequired( el ) {
	const formElementId = utils.getFormElementId( el );
	const selector = `.${ classPrefix }form-element-content-required .${ classPrefix }form-element-content-checkbox[data-${ classPrefix }form-element-id="${ formElementId }"]`;
	const input = document.querySelector( selector );
	const checked = input.checked;
	if ( null === input || ! checked ) {
		return;
	}

	const formElementTitle = utils.getFormElementTitle( input );
	if ( null === formElementTitle ) {
		return;
	}

	// Set class name.
	formElementTitle.classList.add( 'required' );
}
