/**
 * Form generator.
 *
 * Generate CF7 form tags.
 *
 * @file   This files defines form generator.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as utils from './form-editor-utils.js';
import * as sessions from './form-editor-sessions.js';

const classPrefix = 'ffe-';
const formElementContentPrefix = `.${ classPrefix }form-element-content-`;

/**
 * Generate a form tag and set it to the input element.
 */
export function setFormTags() {
	const el = document.getElementById( 'wpcf7-form' );
	if ( null === el ) {
		return;
	}

	const formTags = generateFormTags();
	if ( '' === formTags || typeof formTags === 'undefined' ) {
		return;
	}

	el.value = formTags;
}

/**
 * Generate form-tags.
 */
function generateFormTags() {
	const formElements = document.querySelectorAll( `.${ classPrefix }form-element` );
	if ( 0 === formElements.length ) {
		return;
	}

	let formTags = '';

	formElements.forEach( ( el ) => {
		formTags += generateFormTag( el );
	} );

	return formTags;
}

/**
 * Generate form-tag.
 *
 * @param {HTMLElement} el
 * @return {string} string
 */
function generateFormTag( el ) {
	const label = getLabel( el );
	const cf7TagType = getCf7TagType( el );
	const required = getRequired( el );
	const cf7TagName = getCf7TagName( el );
	const valueListName = getValueListName( el );
	const idAttribute = getIdAttribute( el );
	const classAttributes = getClassAttributes( el );
	const limit = getLimit( el );
	const filetype = getFiletype( el );
	const multiple = utils.tagsUsingMultipleValues;
	let formTag = '';

	if ( null !== label && 'checkbox' !== cf7TagType && 'radio' !== cf7TagType ) {
		formTag += `<label>`;
	}

	if ( null !== label ) {
		formTag += `${ label } \n    `;
	}

	formTag += `[${ cf7TagType }`;

	if ( null !== required && 'radio' !== cf7TagType ) {
		formTag += required;
	}

	formTag += ` fm_field-${ cf7TagName }`;

	if ( ( 'checkbox' === cf7TagType || 'radio' === cf7TagType ) && null !== valueListName ) {
		formTag += ' use_label_element';
	}

	if ( null !== idAttribute ) {
		formTag += idAttribute;
	}

	if ( null !== classAttributes ) {
		formTag += classAttributes;
	}

	if ( 0 <= multiple.indexOf( cf7TagType ) && null !== valueListName ) {
		formTag += ` data:fm_value_list-${ valueListName }`;

		// Expand value list.
		const valueList = getValueList( valueListName );
		const keys = Object.keys( valueList );
		if ( keys.includes( 'name' ) && keys.includes( 'type' ) && keys.includes( 'values' ) ) {
			valueList.values.forEach( ( item ) => {
				formTag += ` "${ item.value }"`;
			} );
		}
	}

	if ( 'file' === cf7TagType && '' !== limit ) {
		formTag += ` limit:${ limit }`;
	}

	if ( 'file' === cf7TagType && '' !== filetype ) {
		formTag += ` filetypes:${ filetype }`;
	}

	if ( 'textarea' === cf7TagType ) {
		formTag += ` 50x5`;
	}

	formTag += `]`;

	if ( null !== label && 'checkbox' !== cf7TagType && 'radio' !== cf7TagType ) {
		formTag += `</label>`;
	}

	formTag += `\n\n`;

	return formTag;
}

/**
 * Get value to be used as label for form-tag.
 *
 * @param {HTMLElement} el
 * @return {string} HTML.
 */
function getLabel( el ) {
	const selector = `${ formElementContentPrefix }label ${ formElementContentPrefix }text`;
	const input = el.querySelector( selector );
	if ( null === input ) {
		return null;
	}

	return input.value;
}

/**
 * Get value to be used as label for form-tag.
 *
 * @param {HTMLElement} el
 * @return {string} HTML.
 */
function getCf7TagType( el ) {
	const selector = `${ formElementContentPrefix }form-tag-type ${ formElementContentPrefix }select`;
	const select = el.querySelector( selector );
	if ( null === select ) {
		return null;
	}

	return select.value;
}

function getRequired( el ) {
	const selector = `${ formElementContentPrefix }required ${ formElementContentPrefix }checkbox`;
	const checkbox = el.querySelector( selector );
	if ( null === checkbox ) {
		return null;
	}

	return checkbox.checked ? '*' : '';
}
/**
 * Get CF7 form-tag name.
 *
 * @param {HTMLElement} el
 * @return {null|string} Null or CF7 form-tag name.
 */
function getCf7TagName( el ) {
	const selector = `${ formElementContentPrefix }form-tag-name ${ formElementContentPrefix }text`;
	const input = el.querySelector( selector );
	if ( null === input ) {
		return null;
	}

	return input.value;
}

/**
 * Get value list name.
 *
 * @param {HTMLElement} el
 * @return {null|string} Null or value list name.
 */
function getValueListName( el ) {
	const selector = `${ formElementContentPrefix }value-list ${ formElementContentPrefix }text`;
	const input = el.querySelector( selector );
	if ( null === input ) {
		return null;
	}

	return input.value;
}

/**
 * Get id attribute.
 *
 * @param {HTMLElement} el
 * @return {null|string} Null or id attribute.
 */
function getIdAttribute( el ) {
	const selector = `${ formElementContentPrefix }id-attribute ${ formElementContentPrefix }text`;
	const input = el.querySelector( selector );
	if ( null === input ) {
		return null;
	}

	return '' === input.value ? '' : ` id:${ input.value }`;
}

/**
 * Get class attributes.
 *
 * @param {HTMLElement} el
 * @return {null|string} Null or class attributes.
 */
function getClassAttributes( el ) {
	const selector = `${ formElementContentPrefix }class-attributes ${ formElementContentPrefix }text`;
	const input = el.querySelector( selector );
	if ( null === input || '' === input.value ) {
		return null;
	}

	let classListText = '';
	const classListArray = input.value.split( ' ' );
	classListArray.forEach( ( classNameText ) => {
		classListText += ` class:${ classNameText }`;
	} );

	return classListText;
}

/**
 *
 * @param {string} valueListName
 * @return {Array} Value list.
 */
function getValueList( valueListName ) {
	const sessionData = sessions.getSessions();
	if ( typeof sessionData.valueList === 'undefined' ) {
		return;
	}

	const valueLists = sessionData.valueList;
	for ( let i = 0; i < valueLists.length; i++ ) {
		const valueList = valueLists[ i ];
		if ( valueListName === valueList.name ) {
			return valueList;
		}
	}

	return [];
}

function getFiletype( el ) {
	const selector = `${ formElementContentPrefix }filetypes ${ formElementContentPrefix }text`;
	const input = el.querySelector( selector );
	if ( null === input ) {
		return null;
	}

	return input.value;
}

function getLimit( el ) {
	const selector = `${ formElementContentPrefix }limit ${ formElementContentPrefix }text`;
	const input = el.querySelector( selector );
	if ( null === input ) {
		return null;
	}

	return input.value;
}
