/**
 * Field list.
 *
 * Display fields retrieved from FileMaker layouts.
 *
 * @file   This files defines field list.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as events from './form-editor-events.js';
import * as i18n from './i18n.js';
import * as modal from './form-editor-modal-dialog.js';
import * as sessions from './form-editor-sessions.js';
import * as utils from './form-editor-utils.js';

const editorId = 'fmpressFormsFormEditor';
const classPrefix = 'ffe-';
const datasetPrefix = classPrefix.substring( 0, 3 );

/**
 * Get layout meta from FileMaker.
 */
export function getLayoutMeta() {
	if ( ! utils.isReadyForWpAjax ) {
		return;
	}

	const dataSourceId = Math.trunc( document.getElementById( 'dataSourceId' ).value );
	const layout = document.getElementById( 'fileMakerLayoutName' ).value;
	const data = {
		action: 'get_layout_from_filemaker',
		wp_post_id: dataSourceId,
		filemaker_layout_name: layout,
		// eslint-disable-next-line no-undef
		fmpress_ajax_nonce: localize.fmpressAjaxNonce,
	};

	const xhr = new XMLHttpRequest();
	// eslint-disable-next-line no-undef
	xhr.open( 'POST', ajaxurl );
	const array = [];
	Object.keys( data ).forEach( ( element ) =>
		array.push(
			encodeURIComponent( element ) + '=' + encodeURIComponent( data[ element ] )
		)
	);
	const body = array.join( '&' );
	xhr.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
	xhr.send( body );

	xhr.onload = () => {
		if ( '' !== xhr.response ) {
			generateFieldList( JSON.parse( xhr.response ) );
			saveValueLists( JSON.parse( xhr.response ) );
		}
	};
	xhr.onerror = () => {
		// eslint-disable-next-line no-console
		console.error( xhr.status );
		// eslint-disable-next-line no-console
		console.error( xhr.response );
	};
}

/**
 * Create a field list based on the obtained layout information.
 *
 * @param {Object} data Messages and response data.
 */
function generateFieldList( data ) {
	const leftArea = document.getElementById( `${ editorId }LeftArea` );
	if ( null === leftArea ) {
		return;
	}

	const fieldList = document.createElement( 'DIV' );
	fieldList.id = `${ editorId }FieldList`;
	fieldList.className = `${ classPrefix }field-list`;

	const fields = data.response.fieldMetaData;
	if ( 0 === fields.length ) {
		return;
	}

	fields.forEach( ( field ) => {
		const regexp = /^<No Access>$|^.+::<No Access>$/;
		if ( ! regexp.test( field.name ) ) {
			const li = generateList( field );
			fieldList.appendChild( li );
		}
	} );

	leftArea.appendChild( fieldList );

	modalDisplayWhenGeneratingFieldList();
}

/**
 * Modal to display when generating field list.
 */
function modalDisplayWhenGeneratingFieldList() {
	const modalName = 'generateFieldList';
	const titleText = i18n.__( 'Let\'s start.' );
	const messageArray = [ i18n.__( 'switched to FMPress Forms editor, which accesses the FileMaker layout to create a list of fields and place them in the left pane. Start editing your form (note that if you reload your web browser, you will lose the content you created in FMPress Forms editor).' ) ];
	const buttonArray = [
		{ name: 'close', className: 'button-secondary', label: i18n.__( 'Start editing' ), eventName: modal.removeModal },
	];
	const displayControl = true;
	modal.generateModal( modalName, titleText, messageArray, buttonArray, displayControl );
}

/**
 * Generate field list.
 *
 * @param {Object} field Field.
 * @return {HTMLElement} HTML LI element.
 */
function generateList( field ) {
	const li = document.createElement( 'LI' );
	const left = document.createElement( 'DIV' );
	const right = document.createElement( 'DIV' );
	const title = document.createElement( 'H4' );
	const meta = document.createElement( 'UL' );
	const displayType = document.createElement( 'LI' );
	const notEmpty = document.createElement( 'LI' );
	const valueList = document.createElement( 'LI' );
	const button = document.createElement( 'BUTTON' );
	const fieldListPrefix = `${ classPrefix }field-list-`;

	li.className = `${ fieldListPrefix }field d-flex fade-in`;
	left.className = `${ fieldListPrefix }field-wrapper ${ fieldListPrefix }field-wrapper-left`;
	right.className = `${ fieldListPrefix }field-wrapper ${ fieldListPrefix }field-wrapper-right d-flex`;
	title.className = `${ fieldListPrefix }name`;
	meta.className = `${ fieldListPrefix }meta`;
	displayType.className = `${ fieldListPrefix }meta-value`;
	notEmpty.className = `${ fieldListPrefix }meta-value`;
	valueList.className = `${ fieldListPrefix }meta-value`;
	button.className = `${ classPrefix }btn ${ classPrefix }add-element-to-form-btn`;

	// Set field-specific id.
	const datasetName = `${ datasetPrefix }FieldId`;
	button.dataset[ datasetName ] = utils.generateRandomId();

	title.textContent = field.name;
	// eslint-disable-next-line @wordpress/i18n-no-variables
	displayType.textContent = `${ i18n.__( 'Type:' ) } ${ i18n.__( field.displayType ) }`;

	const notEmptyValue = field.notEmpty ? i18n.__( 'Yes' ) : i18n.__( 'No' );
	// eslint-disable-next-line @wordpress/i18n-no-variables
	notEmpty.textContent = `${ i18n.__( 'NotEmpty:' ) } ${ i18n.__( notEmptyValue ) }`;
	meta.appendChild( displayType );
	meta.appendChild( notEmpty );

	if ( typeof field.valueList !== 'undefined' ) {
		valueList.textContent = `${ i18n.__( 'Value list:' ) } ${ field.valueList }`;
		meta.appendChild( valueList );
	}

	left.appendChild( title );
	left.appendChild( meta );
	right.appendChild( button );
	li.appendChild( left );
	li.appendChild( right );

	button.addEventListener( 'click', events.addElementToForm, false );

	// Set layout meta information to custom data attributes.
	const atts = Object.keys( field );
	atts.forEach( ( key ) => {
		const _key = utils.capitalizeFirst( key );
		button.dataset[ `${ datasetPrefix }Field${ _key }` ] = field[ key ];
	} );

	return li;
}

/**
 * Save value lists.
 *
 * @param {Object} data Messages and response data.
 */
function saveValueLists( data ) {
	if ( typeof data.response.valueLists === 'undefined' ) {
		return;
	}

	const sessionData = sessions.getSessions();
	sessionData.valueList = data.response.valueLists;

	window.sessionStorage.setItem( sessions.sessionName, JSON.stringify( sessionData ) );
}
