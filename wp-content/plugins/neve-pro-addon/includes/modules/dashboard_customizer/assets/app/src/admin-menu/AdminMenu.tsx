import React from 'react';
import Header from '../layout/Header';
import { Menu } from '../types/types';
import DraggableBox from '../components/common/DraggableBox';
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import Tab from '../components/Tab/Tab';
import cn from 'classnames';
import AdminMenuList from './AdminMenuList';
import Notice from '../components/common/Notice';

const AdminMenu = () => {
	const { menu, url, nonce, roles, assets, version } = window.adminMenu;
	const [ isSaving, setIsSaving ] = useState( false );
	const [ saveStatus, setSaveStatus ] = useState<
		'idle' | 'success' | 'error'
	>( 'idle' );
	const [ isReseting, setIsReseting ] = useState( false );
	const [ resetStatus, setResetStatus ] = useState<
		'idle' | 'success' | 'error'
	>( 'idle' );
	const [ saveMessage, setSaveMessage ] = useState< string >( '' );
	const [ resetMessage, setResetMessage ] = useState< string >( '' );
	const [ userRole, setUserRole ] = useState< string >( 'administrator' );
	const initialItems: Record< string, Menu[] > = menu;
	const [ items, setItems ] = useState< Record< string, Menu[] > >(
		initialItems
	);

	const handleSave = async () => {
		setIsSaving( true );
		setSaveStatus( 'idle' );

		try {
			const response = await fetch( `${ url }save-menu`, {
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
			const errorMessage =
				error instanceof Error
					? error.message
					: 'An unknown error occurred';
			setSaveMessage( errorMessage );
			setSaveStatus( 'error' );
		} finally {
			setTimeout( () => {
				setSaveMessage( '' );
				setSaveStatus( 'idle' );
			}, 3000 );
			setIsSaving( false );
		}
	};

	const handleReset = async () => {
		setIsReseting( true );
		setResetStatus( 'idle' );

		try {
			// Replace with your WordPress REST API endpoint
			const response = await fetch( `${ url }reset-menu`, {
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
			} );

			const data = await response.json();

			setResetMessage( data.data );
			setIsReseting( false );
			// Reload the page after a short delay to show the default menu
			setTimeout( () => {
				window.location.reload();
			}, 1000 );
		} catch ( error ) {
			const errorMessage =
				error instanceof Error
					? error.message
					: 'An unknown error occurred';
			setResetMessage( errorMessage );
			setResetStatus( 'error' );
		} finally {
			setTimeout( () => {
				setResetMessage( '' );
				setResetStatus( 'idle' );
			}, 3000 );
			setIsSaving( false );
		}
	};

	const updateUserRole = ( roleName: string ) => {
		setUserRole( roleName );
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
							__( 'Admin Menu', 'neve-pro-addon' )
						) }
						onSave={ handleSave }
						isSaving={ isSaving }
						onReset={ handleReset }
						isReseting={ isReseting }
					>
						<div className="flex flex-wrap border-b-2 border-gray-500">
							{ Object.entries( roles )?.map(
								( [ roleName, role ] ) => (
									<Tab
										key={ roleName }
										title={ role.name as string }
										onclick={ () =>
											updateUserRole( roleName )
										}
										className={ cn( 'cursor-pointer', {
											'border-b-2 border-gray-600 text-black':
												userRole === roleName,
										} ) }
									/>
								)
							) }
						</div>
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

					<AdminMenuList
						items={ items }
						setItems={ setItems }
						userRole={ userRole }
					/>
				</div>
			</div>
		</>
	);
};

export default AdminMenu;
