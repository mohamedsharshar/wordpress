function run() {
	setMenuType();
	setMenuIcon();
}

function setMenuType() {
	const menuTypes = document.querySelector( '.admin-page-menu-type' );
	const parentMenus = document.querySelector( '#parent-menu' );
	const menuIcons = document.querySelector( '#menu-icons' );

	if ( 'sub' === menuTypes.value ) {
		parentMenus.style.display = 'flex';
		menuIcons.style.display = 'none';
	} else {
		parentMenus.style.display = 'none';
		menuIcons.style.display = 'flex';
	}

	document
		.querySelector( '.admin-page-menu-type' )
		.addEventListener( 'change', function () {
			setMenuType();
		} );
}

function setMenuIcon() {
	const search = document.querySelector( '#search-icon' );
	const icons = document.querySelectorAll( '.icons i' );
	const iconPicker = document.querySelector( '.icon-picker' );
	const iconInput = document.querySelector( '#menu-icon-class' );
	const menuIcon = document.querySelector( 'i#menu-icon' );
	const settingsPage = document.querySelector( '#neve-admin-page-settings' );

	// Search functionality
	search.addEventListener( 'input', function ( event ) {
		const value = event.target.value.toLowerCase().trim();

		icons.forEach( ( icon ) => {
			if ( icon.className.toLowerCase().includes( value ) ) {
				icon.classList.remove( 'hidden' );
			} else {
				icon.classList.add( 'hidden' );
			}
		} );
	} );

	// Icon click to select
	icons.forEach( ( icon ) => {
		icon.addEventListener( 'click', function () {
			const classes = icon.className.trim();

			iconInput.value = classes;
			menuIcon.className = classes;

			// Optional: Highlight the selected icon visually
			icons.forEach( ( i ) => i.classList.remove( 'selected' ) );
			icon.classList.add( 'selected' );
		} );
	} );

	// Show icon picker when input is clicked
	iconInput.addEventListener( 'click', function ( e ) {
		e.stopPropagation();
		iconPicker.classList.remove( 'hidden' );
	} );

	// Hide icon picker when clicking outside
	settingsPage.addEventListener( 'click', function ( e ) {
		// If the click is outside iconPicker and input, close it
		if ( ! iconPicker.contains( e.target ) && e.target !== iconInput ) {
			iconPicker.classList.add( 'hidden' );
		}
	} );
}

window.addEventListener( 'load', function () {
	run();
} );
