/**
 * Form elements.
 *
 * Form elements and their settings.
 *
 * @file   This files defines form elements.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as dnd from './form-editor-draggable.js';
import * as events from './form-editor-events.js';
import * as generator from './form-editor-form-generator.js';
import * as htmlElements from './form-editor-html-elements.js';
import * as i18n from './i18n.js';
import * as utils from './form-editor-utils.js';
import * as validator from './form-editor-validator.js';

let formElementCouter = 0;
const classPrefix = 'ffe-';
const datasetPrefix = classPrefix.substring( 0, 3 );

/**
 * Generate CF7 form elements.
 *
 * @param {Object} e
 * @return {htmlElements} DETAILS element.
 */
export function generateFormElement( e ) {
	const field = generateField( e.target );
	const details = document.createElement( 'DETAILS' );
	const summary = document.createElement( 'SUMMARY' );
	const contents = document.createElement( 'DIV' );
	const detailsId = utils.generateRandomId();
	const formElementId = utils.generateRandomId();
	const datasetNameForFieldId = `${ datasetPrefix }FieldId`;
	const datasetNameForFormElementId = `${ datasetPrefix }FormElementId`;
	const fieldId = e.target.dataset[ datasetNameForFieldId ];

	details.id = detailsId;
	details.className = `${ classPrefix }form-element fade-in`;
	details.dataset[ datasetNameForFormElementId ] = formElementId;
	details.dataset[ datasetNameForFieldId ] = fieldId;
	details.open = true;
	details.draggable = true;

	summary.className = `${ classPrefix }form-element-title`;
	summary.dataset[ datasetNameForFormElementId ] = formElementId;
	summary.textContent = field.name;

	contents.className = `${ classPrefix }form-element-contents`;
	contents.dataset[ datasetNameForFormElementId ] = formElementId;

	const formElementContents = generateFormElementContents( field );
	formElementContents.forEach( ( el ) => {
		el.dataset[ datasetNameForFormElementId ] = formElementId;
		el.dataset[ datasetNameForFieldId ] = fieldId;
		el.addEventListener( 'change', generator.setFormTags );
		el.addEventListener( 'focusout', validator.validateFormElementSettings, false );
		const children = el.querySelectorAll( '*' );
		children.forEach( ( child ) => {
			child.dataset[ datasetNameForFormElementId ] = formElementId;
			child.dataset[ datasetNameForFieldId ] = fieldId;
		} );
		contents.appendChild( el );
	} );

	// Set events.
	details.addEventListener( 'dragstart', dnd.dragStart, false );
	details.addEventListener( 'dragenter', dnd.dragEnter, false );
	details.addEventListener( 'dragover', dnd.dragOver, false );
	details.addEventListener( 'dragleave', dnd.dragLeave, false );
	details.addEventListener( 'drop', dnd.drop, false );
	details.addEventListener( 'dragend', dnd.dragEnd, false );

	details.appendChild( summary );
	details.appendChild( contents );

	// Remove event to add a form element.
	removeButtonToAddFormElement( e.target );

	return details;
}

/**
 * Generate field object from custom data attributes.
 *
 * @param {htmlElements} el
 * @return {Object} Field object
 */
function generateField( el ) {
	const field = {};
	const customDataAttrs = [
		{ name: 'autoEnter', type: 'boolean' },
		{ name: 'displayType', type: 'string' },
		{ name: 'fourDigitYear', type: 'boolean' },
		{ name: 'global', type: 'boolean' },
		{ name: 'id', type: 'string' },
		{ name: 'maxCharacters', type: 'number' },
		{ name: 'maxRepeat', type: 'number' },
		{ name: 'name', type: 'string' },
		{ name: 'notEmpty', type: 'boolean' },
		{ name: 'numeric', type: 'boolean' },
		{ name: 'repetitionEnd', type: 'number' },
		{ name: 'repetitionStart', type: 'number' },
		{ name: 'result', type: 'string' },
		{ name: 'timeOfDay', type: 'boolean' },
		{ name: 'type', type: 'string' },
		{ name: 'valueList', type: 'string' },
	];

	customDataAttrs.forEach( ( attr ) => {
		const customDataAttr = `${ datasetPrefix }Field` + utils.capitalizeFirst( attr.name );
		field[ attr.name ] = typeCasting( attr.type, el.dataset[ customDataAttr ] );
	} );

	return field;
}

/**
 * Converting value types.
 *
 * @param {string} type
 * @param {string} value
 * @return {boolean|number|string} Type converted value.
 */
function typeCasting( type, value ) {
	if ( 'boolean' === type ) {
		return 'true' === value ? true : false;
	} else if ( 'number' === type ) {
		return Math.trunc( value );
	}

	return value;
}

/**
 * Remove event to add a form element.
 *
 * @param {Object} el
 */
function removeButtonToAddFormElement( el ) {
	const datasetName = `${ datasetPrefix }FieldId`;
	const fieldId = el.dataset[ datasetName ];
	const selector = `.${ classPrefix }add-element-to-form-btn[data-${ classPrefix }field-id="${ fieldId }"`;
	const target = document.querySelector( selector );

	if ( null === target ) {
		return;
	}

	const classes = [ `${ classPrefix }field-added`, 'no-submit' ];
	target.classList.add( ...classes );
}

/**
 * Generate UI to set form elements.
 *
 * @param {Object} field
 * @return {Array} Contents of form element.
 */
function generateFormElementContents( field ) {
	const contents = [];

	contents.push( label( field ) );
	contents.push( cf7tagType( field ) );
	contents.push( required( field ) );
	contents.push( cf7tagName( field ) );
	contents.push( valueList( field ) );
	contents.push( idAttribute() );
	contents.push( classAttributes() );
	contents.push( filetypes( field ) );
	contents.push( limit( field ) );
	contents.push( deleteButton() );

	return contents;
}

/**
 * Generate input elements to set labels.
 *
 * @param {Object} field
 * @return {htmlElements} HTML DIV element.
 */
function label( field ) {
	const arg = {
		label: i18n.__( 'Label' ),
		value: field.name,
		parentClassListArray: [ `${ classPrefix }form-element-content-label` ],
		validationRules: 'required',
	};

	return htmlElements.generateTextInput( arg );
}

/**
 * Generate checkbox elements to set required fields.
 *
 * @param {Object} field
 * @return {htmlElements} HTML DIV element.
 */
function required( field ) {
	const arg = {
		nameAttr: 'required',
		parentClassListArray: [ `${ classPrefix }form-element-content-required` ],
	};

	const options = [];
	options.push( {
		label: i18n.__( 'Required' ),
		value: '1',
		default: '0',
		help: i18n.__( 'Radio is always a requirement.' ),
		events: [ { type: 'click', listener: events.checkRequired } ],
	} );

	if ( field.notEmpty ) {
		options[ 0 ].default = '1';
	}

	return htmlElements.generateCheckbox( arg, options );
}

/**
 * Generate text input element to set CF7 type.
 *
 * @param {Object} field
 * @return {Array} Array containing html elements.
 */
function cf7tagType( field ) {
	const arg = {
		label: i18n.__( 'CF7 Form-tag type' ),
		parentClassListArray: [ `${ classPrefix }form-element-content-form-tag-type` ],
		validationRules: 'required',
		events: [ { type: 'change', listener: events.changeCf7TagType } ],
	};

	const options = [];
	options.push( { label: i18n.__( 'Text (Single line text)' ), value: 'text' } );
	options.push( { label: i18n.__( 'Textarea (Multi line text)' ), value: 'textarea' } );
	options.push( { label: i18n.__( 'Email' ), value: 'email' } );
	options.push( { label: i18n.__( 'Number' ), value: 'number' } );
	options.push( { label: i18n.__( 'Date' ), value: 'date' } );
	options.push( { label: i18n.__( 'Select' ), value: 'select' } );
	options.push( { label: i18n.__( 'Checkbox' ), value: 'checkbox' } );
	options.push( { label: i18n.__( 'Radio' ), value: 'radio' } );
	options.push( { label: i18n.__( 'File' ), value: 'file' } );

	const select = htmlElements.generateSelect( arg, options );
	const selectedValue = getCf7Type( field );
	select.querySelector( `option[value='${ selectedValue }']` ).selected = true;

	return select;
}

/**
 * Generate text input element to set CF7 tag name.
 *
 * @param {Object} field
 * @return {HTMLElement} HTML DIV element.
 */
function cf7tagName( field ) {
	const helpText = i18n.__( 'Specify a unique name.' ) +
		i18n.__( 'Use alphanumeric characters, hyphens and underscores.' ) +
		i18n.__( 'The required prefix (fm_field-) is automatically added.' );
	const arg = {
		label: i18n.__( 'CF7 Form-tag name' ),
		value: field.name,
		parentClassListArray: [ `${ classPrefix }form-element-content-form-tag-name` ],
		help: helpText,
		validationRules: 'required alphaNumeric unique',
	};

	if ( ! isValidCf7Name( field.name ) ) {
		formElementCouter++;
		arg.value = 'field' + formElementCouter;
	}

	return htmlElements.generateTextInput( arg );
}

/**
 * Generate text input elements that set the id attribute.
 *
 * @return {HTMLElement} HTML DIV element.
 */
function idAttribute() {
	const helpText = i18n.__( 'Use alphanumeric characters, hyphens and underscores.' );
	const arg = {
		label: i18n.__( 'Id Attribute' ),
		value: '',
		parentClassListArray: [ `${ classPrefix }form-element-content-id-attribute` ],
		help: helpText,
		validationRules: 'alphaNumeric',
	};

	return htmlElements.generateTextInput( arg );
}

/**
 * Generate text input elements that set the class attributes.
 *
 * @return {HTMLElement} HTML DIV element.
 */
function classAttributes() {
	const helpText = i18n.__( 'Use alphanumeric characters, hyphens and underscores.' ) +
		i18n.__( 'If there is more than one, separate them with a space.' );
	const arg = {
		label: i18n.__( 'Class Attributes' ),
		value: '',
		parentClassListArray: [ `${ classPrefix }form-element-content-class-attributes` ],
		help: helpText,
		validationRules: 'alphaNumericSpace',
	};

	return htmlElements.generateTextInput( arg );
}

/**
 * Generate text input element to set FileMaker value list name.
 *
 * @param {Object} field
 * @return {HTMLElement} HTML DIV element.
 */
function valueList( field ) {
	const helpText = i18n.__( 'Name of value list specified in FileMaker.' ) +
		i18n.__( 'Use alphanumeric characters, hyphens and underscores.' ) +
		i18n.__( 'The required prefix (data:fm_value_list-) is automatically added.' );
	const arg = {
		label: i18n.__( 'Value list name' ),
		value: field.valueList ?? '',
		parentClassListArray: [ `${ classPrefix }form-element-content-value-list` ],
		help: helpText,
		validationRules: 'useValueList',
	};

	// Control the display.
	const input = htmlElements.generateTextInput( arg );
	const value = getCf7Type( field );
	const multiValueTag = utils.useMultipleValues( value );
	if ( ! multiValueTag ) {
		input.classList.add( 'fade-out' );
	}

	return input;
}

/**
 * Specify the file type of the file to be uploaded.
 *
 * @param {Object} field
 * @return {HTMLElement} HTML DIV element.
 */
function filetypes( field ) {
	const helpText = i18n.__( 'Enter the file extension or MIME type. If there are multiple files, concatenate them with | (e.g. txt|application/pdf).' );
	const arg = {
		label: i18n.__( 'Filetypes' ),
		value: '',
		parentClassListArray: [ `${ classPrefix }form-element-content-filetypes` ],
		help: helpText,
		validationRules: 'filetypes',
	};

	// Control the display.
	const input = htmlElements.generateTextInput( arg );
	const value = getCf7Type( field );
	if ( 'file' !== value ) {
		input.classList.add( 'fade-out' );
	}

	return input;
}

/**
 * Specify the file size of the file to be uploaded.
 *
 * @param {Object} field
 * @return {HTMLElement} HTML DIV element.
 */
function limit( field ) {
	const helpText = i18n.__( 'Enter the file size and units (e.g., 10MB).' );
	const arg = {
		label: i18n.__( 'Limit' ),
		value: '',
		parentClassListArray: [ `${ classPrefix }form-element-content-limit` ],
		help: helpText,
		validationRules: 'limit',
	};

	// Control the display.
	const input = htmlElements.generateTextInput( arg );
	const value = getCf7Type( field );
	if ( 'file' !== value ) {
		input.classList.add( 'fade-out' );
	}

	return input;
}

/**
 * Generate button to delete form elements.
 *
 * @return {HTMLElement} HTML DIV element.
 */
function deleteButton() {
	const arg = {
		label: i18n.__( 'Delete' ),
		id: 'deleteFormElement',
		classListArray: [ 'button-secondary' ],
		parentClassListArray: [ `${ classPrefix }form-element-content-delete-element` ],
		events: [ { type: 'click', listener: events.deleteFormElement } ],
	};

	return htmlElements.generateButton( arg );
}

/**
 * Validate CF7 form tag name.
 *
 * @param {string} nameText
 */
function isValidCf7Name( nameText ) {
	return /^[0-9A-Za-z_]+$/.test( nameText );
}

/**
 * Get CF7 form-tag type from displayType of FileMaker.
 *
 * @param {Object} field
 * @return {string} Type of CF7 form-tag.
 */
function getCf7Type( field ) {
	const cf7Types = {
		editText: 'text',
		popupList: 'select',
		popupMenu: 'select',
		checkBox: 'checkbox',
		radioButtons: 'radio',
		calendar: 'text',
		secureText: 'text',
	};

	if ( 'container' === field.result ) {
		return 'file';
	}

	return cf7Types[ field.displayType ];
}
