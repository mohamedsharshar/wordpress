import initializeRepeater from './repeater.js';

function initEDDArchiveFocus() {
	wp.customize.section( 'neve_edd_archive', ( section ) => {
		section.expanded.bind( ( isExpanded ) => {
			if ( isExpanded ) {
				wp.customize.previewer.previewUrl.set(
					'/index.php?post_type=download'
				);
			}
		} );
	} );

	wp.customize.section( 'neve_edd_typography', ( section ) => {
		section.expanded.bind( ( isExpanded ) => {
			if ( isExpanded ) {
				wp.customize.previewer.previewUrl.set(
					'/index.php?post_type=download'
				);
			}
		} );
	} );
}

wp.customize.bind( 'ready', function () {
	initializeRepeater();
	initEDDArchiveFocus();
} );
