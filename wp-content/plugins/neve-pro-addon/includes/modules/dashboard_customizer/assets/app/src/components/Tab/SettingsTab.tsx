import React from 'react';
import { useEffect, useState } from '@wordpress/element';
import { AdminBar, Menu } from '../../types/types';
import IconSelector from '../common/IconSelector';
import { __ } from '@wordpress/i18n';

const SettingsTab: React.FC< {
	item: Menu | AdminBar;
	onUpdate: ( updatedItem: Menu | AdminBar ) => void;
	currentTab?: string;
} > = ( { item, onUpdate, currentTab = '' } ) => {
	const [ iconType, setIconType ] = useState< 'dashicon' | 'svg' >(
		item.iconType || 'dashicon'
	);
	const [ iconValue, setIconValue ] = useState( item.icon || '' );

	useEffect( () => {
		if ( item.icon?.startsWith( 'data:image/svg' ) ) {
			setIconType( 'svg' );
		}
	}, [] );

	const updateValue = ( key: string, value: string ) => {
		onUpdate( {
			...item,
			[ key ]: value,
		} );
	};

	const updateIcon = ( value: string ) => {
		setIconValue( value );
		onUpdate( {
			...item,
			icon: value,
		} );
	};

	return (
		<div className="space-y-4 p-4 bg-gray-50 rounded-lg">
			<div>
				<label
					className="block text-sm font-medium text-gray-700 mb-1"
					id="menu-title"
					htmlFor="menu-title-input"
				>
					{ __( 'Title', 'neve-pro-addon' ) }
				</label>
				<input
					type="text"
					id="menu-title-input"
					value={ item.title }
					onChange={ ( e ) => updateValue( 'title', e.target.value ) }
					className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
				/>
			</div>

			{ item.id !== 'customize' && item.id !== 'edit' && (
				<div>
					<label
						className="block text-sm font-medium text-gray-700 mb-1"
						id="menu-slug"
						htmlFor="menu-slug-input"
					>
						{ __( 'Slug', 'neve-pro-addon' ) }
					</label>
					<input
						type="text"
						id="menu-slug-input"
						value={ item.href }
						onChange={ ( e ) =>
							updateValue( 'href', e.target.value )
						}
						className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
					/>
				</div>
			) }

			{ currentTab !== 'submenu' && iconValue && (
				<IconSelector
					iconType={ iconType }
					iconValue={ iconValue }
					onIconTypeChange={ setIconType }
					onIconValueChange={ updateIcon }
				/>
			) }
		</div>
	);
};

export default SettingsTab;
