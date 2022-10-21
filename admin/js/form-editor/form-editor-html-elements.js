/**
 * HTML elements.
 *
 * Generate HTML elements.
 *
 * @file   This files defines HTML elements.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as utils from './form-editor-utils.js';

const classPrefix = 'ffe-';
const datasetPrefix = classPrefix.substring( 0, 3 );
const contentClassName = `${ classPrefix }form-element-content fade-in`;

/**
 * Generate UI to set text input elements.
 *
 * @param {Object} arg
 * @return {HTMLElement} HTML DIV element.
 */
export function generateTextInput( arg ) {
	const content = generateWrapper();
	const uid = utils.generateRandomId();
	const elementPrefix = `${ classPrefix }form-element-content-`;

	const label = document.createElement( 'LABEL' );
	label.htmlFor = uid;
	label.className = `${ elementPrefix }label`;
	label.textContent = arg.label;

	const inputWrapper = document.createElement( 'DIV' );
	inputWrapper.className = `${ elementPrefix }input-wrapper`;
	inputWrapper.dataset[ `${ datasetPrefix }FormElementContentInputId` ] = uid;

	const input = document.createElement( 'INPUT' );
	input.id = uid;
	input.className = `${ elementPrefix }text`;
	input.type = 'text';
	input.value = arg.value;

	input.addEventListener( 'keydown', doNotSubmit, false );

	setClassListToParent( content, arg );
	setClassList( input, arg );
	setHelp( label, arg );
	setValidateRules( input, arg );

	inputWrapper.appendChild( input );
	content.appendChild( label );
	content.appendChild( inputWrapper );

	return content;
}

/**
 * Generate UI to set checkbox elements.
 * Multiple items are not fully supported.
 *
 * @param {Array} arg
 * @param {Array} options
 * @return {HTMLElement} HTML DIV element.
 */
export function generateCheckbox( arg, options ) {
	const content = generateWrapper();
	const uid = utils.generateRandomId();
	const nameText = arg.nameAttr ?? utils.generateRandomId();
	const elementPrefix = `${ classPrefix }form-element-content-`;

	const inputWrapper = document.createElement( 'DIV' );
	inputWrapper.className = `${ elementPrefix }input-wrapper`;
	inputWrapper.dataset[ `${ datasetPrefix }FormElementContentInputId` ] = uid;

	options.forEach( ( option ) => {
		const label = document.createElement( 'LABEL' );
		label.htmlFor = uid;
		label.className = `${ elementPrefix }label`;
		label.textContent = option.label;

		const input = document.createElement( 'INPUT' );
		input.id = uid;
		input.className = `${ elementPrefix }checkbox`;
		input.type = 'checkbox';
		input.name = nameText;
		input.value = option.value;

		if ( '1' === option.default ) {
			input.checked = true;
		}

		input.addEventListener( 'keydown', doNotSubmit, false );

		inputWrapper.appendChild( input );
		content.appendChild( label );
		content.appendChild( inputWrapper );

		if ( '' !== option.help ) {
			const help = generateHelp( option.help );
			label.appendChild( help );
		}

		setEventHandler( input, option );
	} );

	setClassListToParent( content, arg );

	return content;
}

/**
 * Generate UI to set select elements.
 *
 * @param {Array} arg
 * @param {Array} options
 * @return {HTMLElement} HTML DIV element.
 */
export function generateSelect( arg, options ) {
	const content = generateWrapper();
	const uid = utils.generateRandomId();
	const label = document.createElement( 'LABEL' );
	const select = document.createElement( 'SELECT' );
	const elementPrefix = `${ classPrefix }form-element-content-`;

	const inputWrapper = document.createElement( 'DIV' );
	inputWrapper.className = `${ elementPrefix }input-wrapper`;
	inputWrapper.dataset[ `${ datasetPrefix }FormElementContentInputId` ] = uid;

	label.htmlFor = uid;
	label.className = `${ elementPrefix }label`;
	label.textContent = arg.label;

	select.id = uid;
	select.className = `${ elementPrefix }select`;

	select.addEventListener( 'keydown', doNotSubmit, false );

	setClassListToParent( content, arg );
	setClassList( select, arg );
	setValidateRules( select, arg );
	setEventHandler( select, arg );

	options.forEach( ( option ) => {
		const optionEl = document.createElement( 'OPTION' );
		optionEl.className = `${ elementPrefix }option`;
		optionEl.textContent = option.label;
		optionEl.value = option.value;

		select.appendChild( optionEl );
	} );

	inputWrapper.appendChild( select );
	content.appendChild( label );
	content.appendChild( inputWrapper );

	return content;
}

/**
 * Generate UI to set button elements.
 *
 * @param {Object} arg
 * @return {HTMLElement} HTML DIV element.
 */
export function generateButton( arg ) {
	const content = generateWrapper();
	const button = document.createElement( 'BUTTON' );
	button.textContent = arg.label;

	button.id = utils.generateRandomId();
	button.className = `${ classPrefix }form-element-content-button`;

	setClassListToParent( content, arg );
	setClassList( button, arg );
	setValidateRules( button, arg );
	setEventHandler( button, arg );

	if ( false === arg.parent ) {
		return button;
	}

	content.appendChild( button );

	return content;
}

/**
 * Create and return content wrapper elements.
 *
 * @return {HTMLElement} HTML DIV element.
 */
function generateWrapper() {
	const content = document.createElement( 'DIV' );
	content.className = contentClassName;
	return content;
}

/**
 * Set validation rules.
 *
 * @param {HTMLElement} el
 * @param {Array}       arg
 */
function setValidateRules( el, arg ) {
	if ( typeof arg.validationRules === 'undefined' ) {
		return;
	}

	const datasetOwnName = 'formElementContentValidationRules';
	const datasetName = datasetPrefix + utils.capitalizeFirst( datasetOwnName );
	el.dataset[ datasetName ] = arg.validationRules;
}

/**
 * Generate help text elements.
 *
 * @param {string} text
 * @return {HTMLElement} HTML DIV element.
 */
function generateHelp( text ) {
	const help = document.createElement( 'SPAN' );
	help.className = `dashicons dashicons-editor-help`;
	help.title = text;

	return help;
}

/**
 * Setting Help Text.
 *
 * @param {HTMLElement} target
 * @param {Array}       arg
 */
function setHelp( target, arg ) {
	if ( typeof arg.help === 'undefined' ) {
		return;
	}

	const help = generateHelp( arg.help );
	target.appendChild( help );
}

/**
 * Add multiple class attributes to an element.
 *
 * @param {HTMLElement} el
 * @param {Array}       arg
 */
function setClassList( el, arg ) {
	if ( typeof arg.classListArray === 'undefined' ) {
		return;
	}

	el.classList.add( ...arg.classListArray );
}

/**
 * Add multiple class attributes to parent element.
 *
 * @param {HTMLElement} el
 * @param {Array}       arg
 */
function setClassListToParent( el, arg ) {
	if ( typeof arg.parentClassListArray === 'undefined' ) {
		return;
	}

	el.classList.add( ...arg.parentClassListArray );
}

/**
 * Register event handler.
 *
 * @param {HTMLElement} el
 * @param {Array}       arg
 */
function setEventHandler( el, arg ) {
	if ( typeof arg.events === 'undefined' ) {
		return;
	}

	arg.events.forEach( ( event ) => {
		el.addEventListener(
			event.type,
			event.listener,
			false
		);
	} );
}

/**
 * Event to be executed when the enter key is pressed in an input element.
 *
 * @param {Object} e
 * @return {undefined|false} Undefined or false
 */
function doNotSubmit( e ) {
	if ( e.code.toLowerCase() === 'enter' || e.code.toLowerCase() === 'return' ) {
		e.preventDefault();
		return false;
	}
}
