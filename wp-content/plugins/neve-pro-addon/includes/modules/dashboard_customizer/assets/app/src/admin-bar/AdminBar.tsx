import React from 'react';
import Header from '../layout/Header';
import { AdminBar } from '../types/types';
import DraggableBox from '../components/common/DraggableBox';
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import AdminBarList from './AdminBarList';
import Notice from '../components/common/Notice';
import AddMenu from './AddMenu';

const AdminMenu = () => {
	const { menu, url, nonce, assets, version } = window.adminBar;
	const [ isSaving, setIsSaving ] = useState( false );
	const [ saveStatus, setSaveStatus ] = useState<
		'idle' | 'success' | 'error'
	>( 'idle' );
	const [ isResetting, setIsResetting ] = useState( false );
	const [ resetStatus, setResetStatus ] = useState<
		'idle' | 'success' | 'error'
	>( 'idle' );
	const [ saveMessage, setSaveMessage ] = useState< string >( '' );
	const [ resetMessage, setResetMessage ] = useState< string >( '' );
	const initialItems: AdminBar[] = menu;
	const [ items, setItems ] = useState< AdminBar[] >( initialItems );

	const handleSave = async () => {
		setIsSaving( true );
		setSaveStatus( 'idle' );

		try {
			const response = await fetch( `${ url }save-top-menu`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify( { menu: items } ),
			} );

			const data = await response.json();

			setSaveStatus( 'success' );
			setSaveMessage( data.data );

			// Reload the page after a short delay to show the updated menu
			setTimeout( () => {
				window.location.reload();
			}, 1000 );
		} catch ( error ) {
			const errorMessage = error instanceof Error ? error.message : '';
			setSaveMessage( errorMessage );
			setSaveStatus( 'error' );
		} finally {
			setIsSaving( false );
			setTimeout( () => {
				setSaveMessage( '' );
				setSaveStatus( 'idle' );
			}, 3000 );
		}
	};

	const handleReset = async () => {
		setIsResetting( true );
		setResetStatus( 'idle' );

		try {
			const response = await fetch( `${ url }reset-admin-bar`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
			} );

			const data = await response.json();

			setResetStatus( 'success' );
			setResetMessage( data.data );

			// Reload the page after a short delay to show the default menu
			setTimeout( () => {
				window.location.reload();
			}, 1000 );
		} catch ( error ) {
			const errorMessage = error instanceof Error ? error.message : '';
			setResetMessage( errorMessage );
			setResetStatus( 'error' );
			setIsResetting( false );
		}
	};

	const handleAddNewMenu = ( menuData: {
		title: string;
		url: string;
		roles: string[];
		showFrontend: boolean;
	} ) => {
		const newItem: AdminBar = {
			id:
				'neve-custom-' +
				menuData.title.replace( /\s+/g, '-' ).toLowerCase(),
			title: menuData.title,
			href: menuData.url,
			priority: 10,
			submenus: [],
			hide: false,
			hiddenForRole: menuData.roles,
			frontendOnly: menuData.showFrontend,
		};

		// Add the new item to the items list (before top-secondary if it exists)
		const topSecondaryIndex = items.findIndex(
			( item ) => item.id === 'top-secondary'
		);
		if ( topSecondaryIndex !== -1 ) {
			// Insert before top-secondary
			const newItems = [ ...items ];
			newItems.splice( topSecondaryIndex, 0, newItem );
			setItems( newItems );
		} else {
			// Add to the end
			setItems( [ ...items, newItem ] );
		}
	};

	return (
		<>
			<Header assets={ assets } version={ version } />
			<div className="min-h-screen from-blue-50 to-indigo-100 py-8">
				<div className="max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-lg border border-gray-200">
					<DraggableBox
						title={ sprintf(
							// translators: %s is title.
							__( '%s Editor', 'neve-pro-addon' ),
							__( 'Admin Bar', 'neve-pro-addon' )
						) }
						isSaving={ isSaving }
						onSave={ handleSave }
						onReset={ handleReset }
						isReseting={ isResetting }
					>
						{ saveMessage !== '' && (
							<Notice
								message={ saveMessage }
								status={ saveStatus }
							/>
						) }
						{ resetMessage !== '' && (
							<Notice
								message={ resetMessage }
								status={ resetStatus }
							/>
						) }
					</DraggableBox>

					<AdminBarList items={ items } setItems={ setItems } />

					<AddMenu onSave={ handleAddNewMenu } />
				</div>
			</div>
		</>
	);
};

export default AdminMenu;
