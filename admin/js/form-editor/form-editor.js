/**
 * Form editor.
 *
 * Main file of FMPress Form editor.
 *
 * @file   This files defines form editor.
 * @author Author: Emic Corporation.
 * @since  1.3.0
 */

import * as events from './form-editor-events.js';
import * as fieldList from './form-editor-field-list.js';
import * as formGenerator from './form-editor-form-generator.js';
import * as i18n from './i18n.js';
import * as modal from './form-editor-modal-dialog.js';
import * as sessions from './form-editor-sessions.js';
import * as utils from './form-editor-utils.js';
import * as validator from './form-editor-validator.js';

/**
 * Event is fired when the whole page has loaded.
 */
window.addEventListener( 'load', function() {
	const editorId = 'fmpressFormsFormEditor';
	const classPrefix = 'ffe-';
	const defaultButtonLabel = i18n.__( 'FMPress Forms Editor' );
	const backToButtonLabel = i18n.__( 'Back to CF7' );

	/**
	 * Add button to switch form editors.
	 */
	const addButtonToSwitchFormEditor = () => {
		const cf7formPanel = document.getElementById( 'form-panel' );
		if ( null === cf7formPanel ) {
			return;
		}

		const btn = document.createElement( 'BUTTON' );
		btn.id = 'switchFormEditor';
		btn.className = `button-primary ${ classPrefix }switch-form-editor`;
		btn.textContent = defaultButtonLabel;

		cf7formPanel.prepend( btn );
	};

	/**
	 * Show modal when switching form editors.
	 *
	 * @param {Object} e
	 */
	const showModal = ( e ) => {
		e.preventDefault();

		const show = isShowFormEditor();
		let modalName = 'swichToFMPressEditor';
		let hideModal = sessions.doNotDisplayModal( modalName );

		if ( show || hideModal ) {
			// Switch to CF7 Form Editor.
			modalName = 'backToCF7';
			hideModal = sessions.doNotDisplayModal( modalName );
			const count = utils.getCountOfFormElements();

			if ( 0 === count || hideModal ) {
				switchFormEditor();
			} else {
				modalForCf7FormEditor( modalName );
			}
		} else {
			// Switch to FMPress Form Editor.
			modalForFormEditor( modalName );
		}
	};

	/**
	 * Modal to display when switching to CF7 editor.
	 *
	 * @param {string} modalName
	 */
	const modalForCf7FormEditor = ( modalName ) => {
		const titleText = i18n.__( 'Confirmation' );
		const messageArray = [
			i18n.__( 'Back to CF7. Edits made in FMPress Forms editor will not be saved until you press Save.' ),
		];
		const buttonArray = [
			{ name: 'close', className: 'button-secondary', label: i18n.__( 'Close' ), eventName: modal.removeModal },
			{ name: 'continue', className: 'button-secondary', label: i18n.__( 'Continue' ), eventName: switchFormEditor },
		];
		const displayControl = true;
		modal.generateModal( modalName, titleText, messageArray, buttonArray, displayControl );
	};

	/**
	 * Modal to display when switching to FMPress Forms editor.
	 *
	 * @param {string} modalName
	 */
	const modalForFormEditor = ( modalName ) => {
		const titleText = i18n.__( 'Welcome !' );
		const messageArray = [
			i18n.__( 'To create a form in FMPress Forms, you need to add the name of a field or list of values from the FileMaker database to a form tag in Contact Form 7, and there are some rules for this. FMPress Forms editor allows you to do this through a GUI.' ),
		];
		const buttonArray = [
			{ name: 'cancel', className: 'button-secondary', label: i18n.__( 'Cancel' ), eventName: modal.removeModal },
			{ name: 'continue', className: 'button-secondary', label: i18n.__( 'Continue' ), eventName: switchFormEditor },
		];
		const displayControl = true;

		const cf7Form = sessions.getCf7FormValue();
		if ( null !== cf7Form ) {
			messageArray.push( i18n.__( 'Forms created in FMPress Forms Editor will override Contact Form 7 forms. Currently, Contact Form 7 forms have form definitions. If you are sure there is no problem with overwriting it, press Continue.' ) );
		}

		modal.generateModal( modalName, titleText, messageArray, buttonArray, displayControl );
	};

	/**
	 * Switching form editors.
	 */
	const switchFormEditor = () => {
		modal.removeModal();

		if ( ! validator.validateDataSource() ) {
			return;
		}

		const show = isShowFormEditor();

		if ( null === show ) {
			generateFormEditor();
			fieldList.getLayoutMeta();
		}

		if ( false === show || null === show ) {
			// Switch to FMPress Forms Editor.
			eraseCf7Form();
			showFormEditor();
			hideCf7FormEditorTitle();
			hideCf7FormEditor();
			changeButtonLabel( backToButtonLabel );
			changeSavingBehavior();
			formGenerator.setFormTags();
		} else if ( show ) {
			// Switch to CF7 Form Editor.
			sessions.restoreCf7Form();
			hideFormEditor();
			showCf7FormEditorTitle();
			showCf7FormEditor();
			changeButtonLabel( defaultButtonLabel );
			restoreSavingBehavior();
		}
	};

	/**
	 * Erase the contents of the CF7 form.
	 */
	const eraseCf7Form = () => {
		const cf7Form = document.getElementById( 'wpcf7-form' );
		if ( null === cf7Form ) {
			return;
		}

		cf7Form.value = '';
	};

	/**
	 * Hide the title of the CF7 form editor.
	 */
	const hideCf7FormEditorTitle = () => {
		const target = document.querySelector( '#form-panel h2' );
		if ( null === target ) {
			return;
		}

		target.style.display = 'none';
	};

	/**
	 * Hide the CF7 form editor.
	 */
	const hideCf7FormEditor = () => {
		const target = getCf7FormEditor();
		if ( null === target ) {
			return;
		}

		target.style.display = 'none';
	};

	/**
	 * Display the title of the CF7 form editor.
	 */
	const showCf7FormEditorTitle = () => {
		const target = document.querySelector( '#form-panel h2' );
		if ( null === target ) {
			return;
		}

		target.style.display = 'block';
	};

	/**
	 * Display the CF7 form editor.
	 */
	const showCf7FormEditor = () => {
		const target = getCf7FormEditor();
		if ( null === target ) {
			return;
		}

		target.style.display = 'block';
	};

	/**
	 * Generate and display FMPress form editor.
	 */
	const showFormEditor = () => {
		if ( ! existsFormEditor() ) {
			return;
		}

		const target = getFormEditor();
		if ( null === target ) {
			return;
		}

		target.classList.remove( 'hide' );
		target.classList.add( 'show' );
	};

	/**
	 * Hide FMPress form editor.
	 */
	const hideFormEditor = () => {
		if ( ! existsFormEditor() ) {
			return;
		}

		const target = getFormEditor();
		if ( null === target ) {
			return;
		}

		target.classList.remove( 'show' );
		target.classList.add( 'hide' );
	};

	/**
	 * Change the label of the form switching button.
	 *
	 * @param {string} labelText
	 */
	const changeButtonLabel = ( labelText ) => {
		const target = getSwitchButton();
		if ( null === target ) {
			return;
		}

		target.textContent = labelText;
	};

	/**
	 * Change saving behavior.
	 */
	const changeSavingBehavior = () => {
		const selector = 'input[name="wpcf7-save"]';
		const els = document.querySelectorAll( selector );
		els.forEach( ( el ) => {
			el.addEventListener( 'click', events.savingModal, false );
		} );
	};

	/**
	 * Restore saving behavior.
	 */
	const restoreSavingBehavior = () => {
		const selector = 'input[name="wpcf7-save"]';
		const els = document.querySelectorAll( selector );
		els.forEach( ( el ) => {
			el.removeEventListener( 'click', events.savingModal, false );
		} );
	};

	/**
	 * Does a form editor exist?.
	 *
	 * @return {boolean} True if editor exists.
	 */
	const existsFormEditor = () => {
		const target = document.getElementById( editorId );
		if ( null === target ) {
			return false;
		}

		return true;
	};

	/**
	 * Is the form editor displayed?.
	 *
	 * @return {(null|boolean)} True if editor displayed.
	 */
	const isShowFormEditor = () => {
		if ( ! existsFormEditor() ) {
			return null;
		}

		const target = getFormEditor();
		if ( target.classList.contains( 'show' ) ) {
			return true;
		}

		return false;
	};

	/**
	 * Get a button to switch form editors.
	 *
	 * @return {HTMLElement} HTML BUTTON element.
	 */
	const getSwitchButton = () => {
		return document.getElementById( 'switchFormEditor' );
	};

	/**
	 * Get Form Editor.
	 */
	const getFormEditor = () => {
		return document.getElementById( editorId );
	};

	/**
	 * Get the CF7 form editor.
	 */
	const getCf7FormEditor = () => {
		return document.querySelector( '#form-panel fieldset' );
	};

	/**
	 * Start over from the beginning.
	 *
	 * @param {Object} e
	 */
	const startOver = ( e ) => {
		e.preventDefault();

		const post = utils.getQueryVar( 'post' );

		if ( null !== post || post > 0 ) {
			window.location.href = window.location.pathname + `?page=wpcf7&post=${ post }`;
		}
	};

	/**
	 * Get data source name.
	 *
	 * @return {string} Data source name.
	 */
	const getDataSourceName = () => {
		const target = document.getElementById( 'dataSourceId' );
		if ( null === target ) {
			return;
		}

		return target.options[ target.selectedIndex ].text;
	};

	/**
	 * Get layaout name of FileMaker.
	 *
	 * @return {string} Layout name.
	 */
	const getFileMakerLayoutName = () => {
		const target = document.getElementById( 'fileMakerLayoutName' );
		if ( null === target ) {
			return;
		}

		return target.value;
	};

	/**
	 * Generate form editor.
	 */
	const generateFormEditor = () => {
		const parent = document.getElementById( 'form-panel' );
		if ( null === parent ) {
			return;
		}

		const editor = document.createElement( 'DIV' );
		const header = document.createElement( 'DIV' );
		const title = document.createElement( 'H2' );
		const help1 = document.createElement( 'P' );
		const toolbar = document.createElement( 'DIV' );
		const contentWrapper = document.createElement( 'DIV' );
		const leftArea = document.createElement( 'DIV' );
		const rightArea = document.createElement( 'DIV' );
		const leftAreaTitleWrapper = document.createElement( 'DIV' );
		const rightAreaTitleWrapper = document.createElement( 'DIV' );
		const leftAreaTitle = document.createElement( 'H3' );
		const rightAreaTitle = document.createElement( 'H3' );
		const formElements = document.createElement( 'DIV' );
		const dataSourceNameP = document.createElement( 'P' );
		const fileMakerLayoutNameP = document.createElement( 'P' );
		const formAreaDiscriptionP1 = document.createElement( 'P' );
		const formAreaDiscriptionP2 = document.createElement( 'P' );
		const startOverButton = document.createElement( 'BUTTON' );
		const dataSourceName = getDataSourceName();
		const fileMakerLayoutName = getFileMakerLayoutName();

		editor.id = editorId;
		editor.className = `${ classPrefix }form-editor show`;

		header.id = `${ editorId }Header`;
		header.className = `${ classPrefix }form-editor-heder`;

		// eslint-disable-next-line @wordpress/i18n-no-variables
		title.textContent = i18n.__( defaultButtonLabel );
		title.id = `${ editorId }Title`;
		title.className = `${ classPrefix }form-editor-title`;

		help1.textContent = i18n.__( 'To create a form in FMPress Forms, you need to add the name of a field or list of values from the FileMaker database to a form tag in Contact Form 7, and there are some rules for this. FMPress Forms editor allows you to do this through a GUI.' );

		toolbar.id = `${ editorId }Toolbar`;
		toolbar.className = `${ classPrefix }form-editor-toolbar`;

		startOverButton.id = `${ editorId }StartOverButton`;
		startOverButton.className = `${ classPrefix }start-over-button button-secondary`;
		startOverButton.textContent = i18n.__( 'Start over' );
		startOverButton.addEventListener( 'click', startOver, false );

		contentWrapper.id = `${ editorId }ContentWrapper`;
		contentWrapper.className = `${ classPrefix }content-wrapper`;

		leftArea.id = `${ editorId }LeftArea`;
		leftArea.className = `${ classPrefix }area ${ classPrefix }area-left`;

		leftAreaTitleWrapper.id = `${ editorId }LeftAreaTitleWrapper`;
		leftAreaTitleWrapper.className = `${ classPrefix }area-title-wrapper`;

		leftAreaTitle.className = `${ classPrefix }area-title`;

		dataSourceNameP.className = `${ classPrefix }area-item`;
		fileMakerLayoutNameP.className = `${ classPrefix }area-item`;

		rightArea.id = `${ editorId }RightArea`;
		rightArea.className = `${ classPrefix }area ${ classPrefix }area-right`;

		rightAreaTitleWrapper.id = `${ editorId }RightAreaTitleWrapper`;
		rightAreaTitleWrapper.className = `${ classPrefix }area-title-wrapper`;

		rightAreaTitle.className = `${ classPrefix }area-title`;

		formElements.id = `${ editorId }FormElements`;
		formElements.className = `${ classPrefix }form-elements`;

		leftAreaTitle.textContent = i18n.__( 'Fields' );
		rightAreaTitle.textContent = i18n.__( 'Form' );

		dataSourceNameP.textContent = i18n.__( 'Data source: ' );
		fileMakerLayoutNameP.textContent = i18n.__( 'Layout: ' );

		formAreaDiscriptionP1.className = `${ classPrefix }area-item`;
		formAreaDiscriptionP2.className = `${ classPrefix }area-item`;
		formAreaDiscriptionP1.textContent = i18n.__( 'To add a field to a form item, press the plus inside the field item.' );
		formAreaDiscriptionP2.textContent = i18n.__( 'Form items are collapsed by pressing the heading. They can also be reordered by drag-and-drop.' );

		if ( null !== dataSourceName ) {
			dataSourceNameP.textContent += dataSourceName;
		}

		if ( null !== fileMakerLayoutName ) {
			fileMakerLayoutNameP.textContent += fileMakerLayoutName;
		}

		toolbar.appendChild( startOverButton );
		leftAreaTitleWrapper.appendChild( leftAreaTitle );
		rightAreaTitleWrapper.appendChild( rightAreaTitle );
		leftAreaTitleWrapper.appendChild( dataSourceNameP );
		leftAreaTitleWrapper.appendChild( fileMakerLayoutNameP );
		rightAreaTitleWrapper.appendChild( formAreaDiscriptionP1 );
		rightAreaTitleWrapper.appendChild( formAreaDiscriptionP2 );
		leftArea.appendChild( leftAreaTitleWrapper );
		rightArea.appendChild( rightAreaTitleWrapper );
		rightArea.appendChild( formElements );
		contentWrapper.appendChild( leftArea );
		contentWrapper.appendChild( rightArea );
		header.appendChild( title );
		header.appendChild( help1 );
		header.appendChild( toolbar );
		editor.appendChild( header );
		editor.appendChild( contentWrapper );
		parent.appendChild( editor );
	};

	/**
	 * Set event.
	 */
	const addEvents = () => {
		/**
		 * Set event on button to switch form editor.
		 *
		 * @return {undefined}
		 */
		const setEventToSwitchFormEditor = () => {
			const editor = getSwitchButton();
			if ( null === editor ) {
				return;
			}
			editor.addEventListener( 'click', showModal, false );
		};
		setEventToSwitchFormEditor();
	};

	/**
	 * initialization process.
	 */
	const init = () => {
		if ( ! utils.isEditPostOfCf7() ) {
			return;
		}

		sessions.saveCf7Form();
		addButtonToSwitchFormEditor();
		addEvents();
	};
	init();
} );
