import React from 'react';
import { AdminBar } from '../types/types';
import { ReactSortable } from 'react-sortablejs';
import { __ } from '@wordpress/i18n';
import AdminBarItem from './AdminBarItem';

interface AdminBarListProps {
	items: AdminBar[];
	setItems: React.Dispatch< React.SetStateAction< AdminBar[] > >;
	level?: number;
	currentTab?: string;
}

const AdminBarList: React.FC< AdminBarListProps > = ( {
	items,
	setItems,
	level = 0,
	currentTab = '',
} ) => {
	// Separate top-secondary from other items (only at top level)
	const topSecondary =
		level === 0
			? items.find( ( item ) => item.id === 'top-secondary' )
			: null;
	const otherItems =
		level === 0
			? items.filter( ( item ) => item.id !== 'top-secondary' )
			: items;

	const handleUpdateItem = ( index: number, updatedItem: AdminBar ) => {
		const newItems = [ ...items ];
		newItems[ index ] = updatedItem;
		setItems( newItems );
	};

	const handleDeleteItem = ( index: number ) => {
		const newItems = items.filter( ( _, i ) => i !== index );
		setItems( newItems );
	};

	const handleSetOtherItems = ( newItems: AdminBar[] ) => {
		// Combine other items with top-secondary at the end
		if ( topSecondary ) {
			setItems( [ ...newItems, topSecondary ] );
		} else {
			setItems( newItems );
		}
	};

	if ( items.length === 0 ) {
		return (
			<div className="min-h-[200px] p-4 bg-gray-50 rounded-lg">
				<div className="text-center text-gray-500 py-8">
					{ __( 'Menu item does not exist.', 'neve-pro-addon' ) }
				</div>
			</div>
		);
	}

	return (
		<>
			<ReactSortable
				list={ level === 0 ? otherItems : items }
				setList={ level === 0 ? handleSetOtherItems : setItems }
				animation={ 200 }
				className="space-y-2"
				handle=".drag-handle"
			>
				{ ( level === 0 ? otherItems : items ).map( ( item, index ) => (
					<AdminBarItem
						key={ item.id || index }
						item={ item }
						index={ index }
						onUpdateItem={ handleUpdateItem }
						onDeleteItem={ handleDeleteItem }
						level={ level }
						currentTab={ currentTab }
					/>
				) ) }
			</ReactSortable>

			{ /* Render top-secondary outside sortable (only at top level) */ }
			{ level === 0 && topSecondary && (
				<>
					{ /* Separator for visual distinction */ }
					<div className="my-4 mb-3 mt-3 border-b-2 border-gray-300"></div>

					<AdminBarItem
						key={ topSecondary.id || 'top-secondary' }
						item={ topSecondary }
						index={ items.length - 1 }
						onUpdateItem={ handleUpdateItem }
						onDeleteItem={ handleDeleteItem }
						level={ level }
						currentTab={ currentTab }
						isNonDraggable={ true }
					/>
				</>
			) }
		</>
	);
};

export default AdminBarList;
