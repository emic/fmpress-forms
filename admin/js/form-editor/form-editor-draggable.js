/**
 * Drag and Drop interfaces.
 *
 * Used to replace form elements.
 * Using HTML Drag and Drop interfaces.
 *
 * @file   This files defines the Drag and Drop.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as formGenerator from './form-editor-form-generator.js';

const classPrefix = 'ffe-';

/**
 * Fires at start of a drag.
 *
 * @param {Object} e
 */
export function dragStart( e ) {
	setOpacity( e.target, '0.3' );
	e.dataTransfer.effectAllowed = 'move';
	e.dataTransfer.setData( 'text/plain', e.target.id );
}

/**
 * Fires at drag over.
 *
 * @param {Object} e
 */
export function dragOver( e ) {
	if ( e.preventDefault ) {
		e.preventDefault();
	}
	e.dataTransfer.dropEffect = 'move';
	return false;
}

/**
 * Fires at drag enter.
 */
export function dragEnter() {
	this.classList.add( 'over' );
}

/**
 * Fires at drag leave.
 */
export function dragLeave() {
	this.classList.remove( 'over' );
}

/**
 * Fires at drop.
 *
 * @param {Object} e
 */
export function drop( e ) {
	e.preventDefault();

	// Value of id attribute of drag element.
	const transferData = e.dataTransfer.getData( 'text/plain' );
	if ( transferData === this.id ) {
		// If drag element and drop element are the same, exit.
		return;
	}

	const dragEl = document.getElementById( transferData );
	const rect = this.getBoundingClientRect();

	if ( ( e.clientY - rect.top ) < ( this.clientHeight / 2 ) ) {
		// Mouse cursor position above half of the element.
		this.parentNode.insertBefore( dragEl, this );
	} else {
		// Mouse cursor position is below half of the element.
		this.parentNode.insertBefore( dragEl, this.nextSibling );
	}
}

/**
 * Fires at drag end.
 */
export function dragEnd() {
	setOpacity( this, '1' );
	removeClassName();
	formGenerator.setFormTags();
}

/**
 * Remove class name.
 */
function removeClassName() {
	const els = document.querySelectorAll( `.${ classPrefix }form-element` );
	els.forEach( function( el ) {
		el.classList.remove( 'over' );
	} );
}

/**
 * Set opacity value.
 *
 * @param {Object} el
 * @param {string} value
 */
function setOpacity( el, value ) {
	el.style.opacity = value;
}
