/**
 * External dependencies
 */
import { Component } from 'react';
// NOTE: We only import the debounce function here for reduced bundle size.
//       Do not import the entire lodash library!
// eslint-disable-next-line lodash/import-scope
import debounce from 'lodash/debounce';

// This component is used primarily to bind DOM event handlers to elements outside of the Jetpack Search overlay.
export default class DomEventHandler extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			// When typing in CJK, the following events fire in order:
			// keydown, compositionstart, compositionupdate, input, keyup, keydown,compositionend, keyup
			// We toggle isComposing on compositionstart and compositionend events.
			// (CJK = Chinese, Japanese, Korean; see https://en.wikipedia.org/wiki/CJK_characters)
			isComposing: false,
		};
		this.props.initializeQueryValues();
	}

	componentDidMount() {
		this.disableAutocompletion();
		this.addEventListeners();
	}

	componentWillUnmount() {
		this.removeEventListeners();
	}

	disableAutocompletion() {
		document.querySelectorAll( this.props.themeOptions.searchInputSelector ).forEach( input => {
			input.setAttribute( 'autocomplete', 'off' );
			input.form.setAttribute( 'autocomplete', 'off' );
		} );
	}

	addEventListeners() {
		window.addEventListener( 'popstate', this.handleHistoryNavigation );

		// Add listeners for input and submit
		document.querySelectorAll( this.props.themeOptions.searchInputSelector ).forEach( input => {
			input.form.addEventListener( 'submit', this.handleSubmit );
			// keydown handler is causing text duplication because it actively sets the search input
			// value after system input method empty the input but before filling the input again.
			// so changed to keyup event which is fired after compositionend when Enter is pressed.
			input.addEventListener( 'keyup', this.handleKeyup );
			input.addEventListener( 'input', this.handleInput );
			input.addEventListener( 'compositionstart', this.handleCompositionStart );
			input.addEventListener( 'compositionend', this.handleCompositionEnd );
		} );

		document.querySelectorAll( this.props.themeOptions.overlayTriggerSelector ).forEach( button => {
			button.addEventListener( 'click', this.handleOverlayTriggerClick, true );
		} );

		document.querySelectorAll( this.props.themeOptions.filterInputSelector ).forEach( element => {
			element.addEventListener( 'click', this.handleFilterInputClick );
		} );
	}

	removeEventListeners() {
		window.removeEventListener( 'popstate', this.handleHistoryNavigation );

		document.querySelectorAll( this.props.themeOptions.searchInputSelector ).forEach( input => {
			input.form.removeEventListener( 'submit', this.handleSubmit );
			input.removeEventListener( 'keyup', this.handleKeyup );
			input.removeEventListener( 'input', this.handleInput );
			input.removeEventListener( 'compositionstart', this.handleCompositionStart );
			input.removeEventListener( 'compositionend', this.handleCompositionEnd );
		} );

		document.querySelectorAll( this.props.themeOptions.overlayTriggerSelector ).forEach( button => {
			button.removeEventListener( 'click', this.handleOverlayTriggerClick, true );
		} );

		document.querySelectorAll( this.props.themeOptions.filterInputSelector ).forEach( element => {
			element.removeEventListener( 'click', this.handleFilterInputClick );
		} );
	}

	handleCompositionStart = () => this.setState( { isComposing: true } );
	handleCompositionEnd = () => this.setState( { isComposing: false } );

	handleFilterInputClick = event => {
		event.preventDefault();
		if ( event.currentTarget.dataset.filterType ) {
			if ( event.currentTarget.dataset.filterType === 'taxonomy' ) {
				this.props.setFilter(
					event.currentTarget.dataset.taxonomy,
					event.currentTarget.dataset.val
				);
			} else {
				this.props.setFilter(
					event.currentTarget.dataset.filterType,
					event.currentTarget.dataset.val
				);
			}
		}
		this.props.showResults();
	};

	handleHistoryNavigation = () => {
		// Treat history navigation as brand new query values; re-initialize.
		// Note that this re-initialization will trigger onChangeQueryString via side effects.
		this.props.initializeQueryValues( { isHistoryNavigation: true } );
	};

	handleInput = debounce( event => {
		// Reference: https://rawgit.com/w3c/input-events/v1/index.html#interface-InputEvent-Attributes
		// NOTE: inputType is not compatible with IE11, so we use optional chaining here. https://caniuse.com/mdn-api_inputevent_inputtype
		if ( event.inputType?.includes( 'format' ) || event.target.value === '' ) {
			return;
		}

		// Is the user still composing input with a CJK language?
		if ( this.state.isComposing ) {
			return;
		}

		if ( this.props.overlayOptions.overlayTrigger === 'submit' ) {
			return;
		}

		this.props.setSearchQuery( event.target.value );

		if ( this.props.overlayOptions.overlayTrigger === 'immediate' ) {
			this.props.showResults();
		}

		if ( this.props.overlayOptions.overlayTrigger === 'results' ) {
			this.props.response?.results && this.props.showResults();
		}
	}, 200 );

	handleKeyup = event => {
		// If user presses enter, propagate the query value and immediately show the results.
		if ( event.key === 'Enter' ) {
			this.props.setSearchQuery( event.target.value );
			this.props.showResults();
		}
	};

	// Treat overlay trigger clicks to be equivalent to setting an empty string search query.
	handleOverlayTriggerClick = event => {
		event.stopImmediatePropagation();
		this.props.setSearchQuery( '' );
		this.props.showResults();
	};

	handleSubmit = event => {
		event.preventDefault();
		this.handleInput.flush();

		// handleInput didn't respawn the overlay. Do it manually -- form submission must spawn an overlay.
		if ( ! this.props.isVisible ) {
			const value = event.target.querySelector( this.props.themeOptions.searchInputSelector )
				?.value;
			// Don't do a falsy check; empty string is an allowed value.
			typeof value === 'string' && this.props.setSearchQuery( value );
			this.props.showResults();
		}
	};

	render() {
		return null;
	}
}
