/**
 * Validator.
 *
 * Define validation functions.
 *
 * @file   This files defines validation functions.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as i18n from './i18n.js';
import * as modalDialog from './form-editor-modal-dialog.js';
import * as utils from './form-editor-utils.js';

const classPrefix = 'ffe-';
const datasetPrefix = classPrefix.substring( 0, 3 );
let errors = 0;

/**
 * Reset counter of errors.
 */
export function resetErrors() {
	errors = 0;
}

/**
 * Validate data sources.
 *
 * @return {boolean} Returns True if the verification result is correct.
 */
export function validateDataSource() {
	const dataSourceId = Math.trunc( document.getElementById( 'dataSourceId' ).value );
	const layout = document.getElementById( 'fileMakerLayoutName' ).value;

	if ( dataSourceId < 1 || '' === layout ) {
		const modalName = 'validateDataSource';
		const titleText = i18n.__( 'Error' );
		const messageArray = [ i18n.__( 'It appears that FileMaker data source and layout are not set up, please go to FMPress tab and set them up.' ) ];
		const buttonArray = [];
		modalDialog.generateModal( modalName, titleText, messageArray, buttonArray );
		return false;
	}

	return true;
}

/**
 * Validate form element settings.
 *
 * @param {Object|HTMLElement} e
 */
export function validateFormElementSettings( e ) {
	const el = e.target ?? e;

	resetErrors();

	const formElementId = utils.getFormElementId( el );
	const prefix = `${ classPrefix }form-element-`;
	const selector = `.${ prefix }content-text[data-${ prefix }id="${ formElementId }"], .${ prefix }content-checkbox[data-${ prefix }id="${ formElementId }"], .${ prefix }content-select[data-${ prefix }id="${ formElementId }"]`;
	const settings = document.querySelectorAll( selector );
	settings.forEach( ( element ) => {
		validateFormElementSetting( element );
	} );
}

/**
 * Validate form element setting.
 *
 * @param {Object|HTMLElement} e
 */
export function validateFormElementSetting( e ) {
	const el = e.target ?? e;

	const datasetOwnName = 'formElementContentValidationRules';
	const datasetName = datasetPrefix + utils.capitalizeFirst( datasetOwnName );
	const rulesString = el.dataset[ datasetName ];

	if ( typeof rulesString === 'undefined' ) {
		return;
	}

	const rules = rulesString.split( ' ' );
	const value = el.value;
	let validate = true;
	let errorMessage = '';

	for ( let i = 0; i < rules.length; i++ ) {
		const rule = rules[ i ];

		if ( 'required' === rule ) {
			validate = validateRequired( value );
			if ( ! validate ) {
				errors++;
				errorMessage += i18n.__( 'Required field.' );
			}
		}

		if ( 'unique' === rule ) {
			const values = getValuesOfAllFields( el );
			validate = validateUniqueValue( value, values );
			if ( ! validate ) {
				errors++;
				errorMessage += i18n.__( 'This name is already in use.' );
			}
		}

		if ( 'alphaNumeric' === rule ) {
			validate = validateAlphaNumeric( value );
			if ( ! validate ) {
				errors++;
				errorMessage += i18n.__( 'Contains unavailable characters.' );
			}
		}

		if ( 'alphaNumericSpace' === rule ) {
			validate = validateAlphaNumericSpace( value );
			if ( ! validate ) {
				errors++;
				errorMessage += i18n.__( 'Contains unavailable characters.' );
			}
		}

		if ( 'useValueList' === rule ) {
			const multiple = utils.tagsUsingMultipleValues;
			const cf7FormTagType = getCf7FormTagType( el );

			if ( ! multiple.includes( cf7FormTagType ) ) {
				continue;
			}

			if ( '' === el.value.trim() ) {
				errors++;
				errorMessage += i18n.__( 'Value list name not specified.' );
			} else if ( ! validateAlphaNumeric( el.value ) ) {
				errors++;
				errorMessage += i18n.__( 'Contains unavailable characters.' );
			}
		}

		if ( 'filetypes' === rule ) {
			validate = validateFiletypes( value );
			if ( ! validate ) {
				errors++;
				errorMessage += i18n.__( 'This format cannot be used.' );
			}
		}

		if ( 'limit' === rule ) {
			validate = validateLimit( value );
			if ( ! validate ) {
				errors++;
				errorMessage += i18n.__( 'This format cannot be used.' );
			}
		}
	}

	const formElement = getFormElement( el );
	if ( null === formElement ) {
		return;
	}

	if ( errors > 0 ) {
		formElement.classList.remove( `${ classPrefix }validated-valid` );
		formElement.classList.add( `${ classPrefix }validated-invalid` );
		removeErrorMessageFromFormElement( el, errorMessage );
		showErrorMessageToFormElement( el, errorMessage );
	}

	if ( 0 === errors ) {
		formElement.classList.remove( `${ classPrefix }validated-invalid` );
		formElement.classList.add( `${ classPrefix }validated-valid` );
		removeErrorMessageFromFormElement( el, errorMessage );
	}
}

/**
 * Get Cf7 form tag type.
 *
 * @param {HTMLElement} el
 * @return {string} Cf7 Form-tag type.
 */
function getCf7FormTagType( el ) {
	const inputPrefix = `${ classPrefix }form-element-`;
	const formElementId = utils.getFormElementId( el );
	const selector = `.${ inputPrefix }content-select[data-${ inputPrefix }id="${ formElementId }"]`;
	const select = document.querySelector( selector );
	return select.value;
}

/**
 * Get values of all fields (CF7 form-tag name).
 *
 * @param {HTMLElement} el
 * @return {Array} Values of all fields.
 */
function getValuesOfAllFields( el ) {
	const values = [];
	const prefix = `.${ classPrefix }form-element-content-`;
	const selector = `${ prefix }form-tag-name ${ prefix }text:not(#${ el.id })`;
	const elements = document.querySelectorAll( selector );
	elements.forEach( ( element ) => {
		values.push( element.value );
	} );
	return values;
}

/**
 * Verification of required items.
 *
 * @param {string} value
 * @return {boolean} Verification result.
 */
function validateRequired( value ) {
	return '' === value.trim() ? false : true;
}

/**
 * Verification of unique value.
 *
 * @param {string} value
 * @param {Array}  values
 * @return {boolean} Verification result.
 */
function validateUniqueValue( value, values ) {
	return true === values.includes( value ) ? false : true;
}

/**
 * Verification of alpha numeric.
 *
 * @param {string} value
 * @return {boolean} Verification result.
 */
function validateAlphaNumeric( value ) {
	if ( '' === value ) {
		return true;
	}
	const regex = /^[0-9a-zA-Z-_]+$/g;
	return value.match( regex );
}

/**
 * Verification of alpha numeric.
 *
 * @param {string} value
 * @return {boolean} Verification result.
 */
function validateAlphaNumericSpace( value ) {
	if ( '' === value ) {
		return true;
	}
	const regex = /^[a-zA-Z0-9-_ ]+$/g;
	return value.match( regex );
}

/**
 * Verification of filetype of upload file.
 * Default value is `audio/*|video/*|image/*`
 *
 * @param {string} value
 * @return {boolean} Verification result.
 */
function validateFiletypes( value ) {
	if ( '' === value ) {
		return true;
	}

	const regex = /^([a-z]+)((\/)([*]|[a-z0-9.+-]+))?$/i;
	const splited = value.split( '|' );
	for ( let i = 0; i < splited.length; i++ ) {
		const filetype = splited[ i ];
		if ( ! regex.test( filetype ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Verification of limit of upload file.
 * Default value is `1mb`
 *
 * @param {string} value
 * @return {boolean} Verification result.
 */
function validateLimit( value ) {
	if ( '' === value ) {
		return true;
	}
	const regex = /^([1-9][0-9]*)([kKmM]?[bB])?$/;
	return value.match( regex );
}

/**
 * Show error message to form elment setting.
 *
 * @param {HTMLElement} el
 * @param {string}      errorMessage
 */
function showErrorMessageToFormElement( el, errorMessage ) {
	const inputPrefix = `${ classPrefix }form-element-content-input-`;
	const parent = getElementContentInputWrapper( el );

	const p = document.createElement( 'P' );
	p.className = `${ inputPrefix }error-message`;
	p.dataset[ `${ datasetPrefix }FormElementContentInputId` ] = el.id;
	p.textContent = errorMessage;

	parent.appendChild( p );
}

/**
 * Remove error message from form elment setting.
 *
 * @param {HTMLElement} el
 */
function removeErrorMessageFromFormElement( el ) {
	const inputPrefix = `${ classPrefix }form-element-content-input-`;
	const selector = `.${ inputPrefix }error-message[data-${ inputPrefix }id="${ el.id }"]`;

	const targets = document.querySelectorAll( selector );
	targets.forEach( ( target ) => {
		target.remove();
	} );
}

/**
 * Return element content input wrapper.
 *
 * @param {HTMLElement} el
 * @return {HTMLElement} Element content input wrapper.
 */
function getElementContentInputWrapper( el ) {
	const inputPrefix = `${ classPrefix }form-element-content-input-`;
	const selector = `.${ inputPrefix }wrapper[data-${ inputPrefix }id="${ el.id }"]`;
	const parent = document.querySelector( selector );
	return parent;
}

/**
 * Return form element.
 *
 * @param {HTMLElement} el
 * @return {HTMLElement} Form Element.
 */
function getFormElement( el ) {
	const formElementId = utils.getFormElementId( el );
	const prefix = `${ classPrefix }form-element`;
	const selector = `.${ prefix }[data-${ prefix }-id="${ formElementId }"]`;
	const formElement = document.querySelector( selector );
	return formElement;
}
