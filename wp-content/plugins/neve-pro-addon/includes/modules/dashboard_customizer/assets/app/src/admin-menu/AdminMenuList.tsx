import React from 'react';
import { Menu } from '../types/types';
import AdminMenuItem from './AdminMenuItem';
import { ReactSortable } from 'react-sortablejs';
import { __ } from '@wordpress/i18n';

interface AdminMenuListProps {
	items: Record< string, Menu[] >;
	setItems: ( items: Record< string, Menu[] > ) => void;
	level?: number;
	currentTab?: string;
	userRole?: string;
}

const AdminMenuList: React.FC< AdminMenuListProps > = ( {
	items,
	setItems,
	level = 0,
	currentTab = '',
	userRole = '',
} ) => {
	const handleUpdateItem = ( index: number, updatedItem: Menu ) => {
		const newItems = [ ...items[ userRole ] ];
		newItems[ index ] = updatedItem;
		setItems( {
			...items,
			[ userRole ]: newItems,
		} );
	};

	const handleSort = ( newItems: Menu[] ) => {
		const sortedPriorities = newItems
			.map( ( item ) => Number( item.priority ) )
			.sort( ( a, b ) => a - b );

		const updatedItem = newItems.map( ( item, index ) => ( {
			...item,
			priority: sortedPriorities[ index ],
		} ) );

		setItems( {
			...items,
			[ userRole ]: updatedItem,
		} );
	};

	if ( items[ userRole ].length === 0 ) {
		return (
			<div className="min-h-[200px] p-4 bg-gray-50 rounded-lg">
				<div className="text-center text-gray-500 py-8">
					{ __( 'Menu item does not exist.', 'neve-pro-addon' ) }
				</div>
			</div>
		);
	}

	return (
		<ReactSortable
			list={ items[ userRole ] }
			setList={ ( newState ) => handleSort( newState ) }
			animation={ 200 }
			handle=".drag-handle"
		>
			{ items[ userRole ].map( ( item, index ) => (
				<AdminMenuItem
					key={ item.id || index }
					item={ item }
					index={ index }
					onUpdateItem={ handleUpdateItem }
					level={ level }
					currentTab={ currentTab }
					userRole={ userRole }
				/>
			) ) }
		</ReactSortable>
	);
};

export default AdminMenuList;
