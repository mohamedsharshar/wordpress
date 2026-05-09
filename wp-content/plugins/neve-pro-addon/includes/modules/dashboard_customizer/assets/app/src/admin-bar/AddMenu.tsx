import React from 'react';
import { useState } from '@wordpress/element';
import Select from 'react-select';
import { __ } from '@wordpress/i18n';

interface AddMenuProps {
	onSave: ( menuData: {
		title: string;
		url: string;
		roles: string[];
		showFrontend: boolean;
	} ) => void;
	buttonLabel?: string;
	formTitle?: string;
}

const AddMenu: React.FC< AddMenuProps > = ( {
	onSave,
	buttonLabel = __( 'Add Menu', 'neve-pro-addon' ),
	formTitle = __( 'Add New Menu', 'neve-pro-addon' ),
} ) => {
	// State for adding new menu
	const [ isAddingMenu, setIsAddingMenu ] = useState< boolean >( false );
	const [ newMenuTitle, setNewMenuTitle ] = useState< string >( '' );
	const [ newMenuUrl, setNewMenuUrl ] = useState< string >( '' );
	const [ newMenuRoles, setNewMenuRoles ] = useState< string[] >( [] );
	const [ newMenuShowFrontend, setNewMenuShowFrontend ] = useState< boolean >(
		false
	);

	const handleSaveNewMenu = () => {
		if ( ! newMenuTitle ) return;

		// Call the onSave callback with the menu data
		onSave( {
			title: newMenuTitle,
			url: newMenuUrl,
			roles: newMenuRoles,
			showFrontend: newMenuShowFrontend,
		} );

		// Reset and close
		setNewMenuTitle( '' );
		setNewMenuUrl( '' );
		setNewMenuRoles( [] );
		setNewMenuShowFrontend( false );
		setIsAddingMenu( false );
	};

	const handleCancelNewMenu = () => {
		setNewMenuTitle( '' );
		setNewMenuUrl( '' );
		setNewMenuRoles( [] );
		setNewMenuShowFrontend( false );
		setIsAddingMenu( false );
	};

	return (
		<div className="mt-4 text-end">
			{ ! isAddingMenu ? (
				<button
					type="button"
					onClick={ () => setIsAddingMenu( true ) }
					className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
				>
					{ buttonLabel }
				</button>
			) : (
				<div className="p-4 bg-white border border-gray-300 rounded-lg mt-2 text-start">
					<h4 className="font-medium mb-3">{ formTitle }</h4>
					<div className="space-y-3">
						<div>
							<label
								className="block text-sm font-medium text-gray-700 mb-1"
								htmlFor="new-menu-title"
							>
								{ __( 'Title', 'neve-pro-addon' ) }
							</label>
							<input
								type="text"
								id="new-menu-title"
								value={ newMenuTitle }
								onChange={ ( e ) =>
									setNewMenuTitle( e.target.value )
								}
								className="w-full px-3 py-2 border border-gray-300 rounded-md"
							/>
						</div>
						<div>
							<label
								className="block text-sm font-medium text-gray-700 mb-1"
								htmlFor="new-menu-url"
							>
								{ __( 'URL', 'neve-pro-addon' ) }
							</label>
							<input
								type="text"
								id="new-menu-url"
								value={ newMenuUrl }
								onChange={ ( e ) =>
									setNewMenuUrl( e.target.value )
								}
								className="w-full px-3 py-2 border border-gray-300 rounded-md"
							/>
						</div>
						<div>
							<label
								className="block text-sm font-medium text-gray-700 mb-1"
								htmlFor="new-menu-roles"
							>
								{ __(
									'Hide for User Roles',
									'neve-pro-addon'
								) }
							</label>
							<Select
								inputId="new-menu-roles"
								options={ Object.entries(
									window.adminBar.roles
								).map( ( [ key, role ] ) => ( {
									value: key,
									label: role.name,
								} ) ) }
								isMulti={ true }
								value={ newMenuRoles.map( ( role ) => ( {
									value: role,
									label:
										window.adminBar.roles[ role ]?.name ||
										role,
								} ) ) }
								onChange={ ( selectedOptions ) => {
									const selectedRoles = selectedOptions
										? selectedOptions.map(
												( option ) => option.value
										  )
										: [];
									setNewMenuRoles( selectedRoles );
								} }
							/>
						</div>
						<div className="flex items-center mt-2">
							<input
								type="checkbox"
								id="new-menu-frontend-only"
								checked={ newMenuShowFrontend }
								onChange={ ( e ) =>
									setNewMenuShowFrontend( e.target.checked )
								}
								className="mr-2"
							/>
							<label
								htmlFor="new-menu-frontend-only"
								className="text-sm font-medium text-gray-700"
							>
								{ __( 'Show on Frontend', 'neve-pro-addon' ) }
							</label>
						</div>
						<div className="flex gap-2 mt-4">
							<button
								type="button"
								onClick={ handleSaveNewMenu }
								className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
							>
								{ __( 'Save', 'neve-pro-addon' ) }
							</button>
							<button
								type="button"
								onClick={ handleCancelNewMenu }
								className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
							>
								{ __( 'Cancel', 'neve-pro-addon' ) }
							</button>
						</div>
					</div>
				</div>
			) }
		</div>
	);
};

export default AddMenu;
