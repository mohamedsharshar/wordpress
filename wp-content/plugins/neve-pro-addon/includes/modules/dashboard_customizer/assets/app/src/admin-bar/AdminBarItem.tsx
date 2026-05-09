import React from 'react';
import { AdminBar, Menu } from '../types/types';
import { useState } from '@wordpress/element';
import SettingsTab from '../components/Tab/SettingsTab';
import {
	chevronDown,
	chevronRight,
	cog,
	Icon,
	menu,
	trash,
} from '@wordpress/icons';
import cn from 'classnames';
import Tab from '../components/Tab/Tab';
import { __ } from '@wordpress/i18n';
import AdminBarList from './AdminBarList';
import Select from 'react-select';
import AddMenu from './AddMenu';

interface AdminBarItemProps {
	item: AdminBar;
	index: number;
	onUpdateItem: ( index: number, updatedItem: AdminBar ) => void;
	onDeleteItem?: ( index: number ) => void;
	level?: number;
	currentTab?: string;
	isNonDraggable?: boolean;
}

const AdminBarItem: React.FC< AdminBarItemProps > = ( {
	item,
	index,
	onUpdateItem,
	onDeleteItem,
	level = 0,
	currentTab = '',
	isNonDraggable = false,
} ) => {
	const [ isExpanded, setIsExpanded ] = useState< boolean >( false );
	const [ menuStatus, setMenuStatus ] = useState< boolean >(
		item?.hide || false
	);
	const [ activeTab, setActiveTab ] = useState< 'settings' | 'submenu' >(
		'settings'
	);

	const { roles } = window.adminBar;
	const userRoles = Object.entries( roles )?.map( ( [ key, role ] ) => ( {
		value: key,
		label: role.name,
	} ) );

	const handleUpdateSettings = ( updatedItem: Menu | AdminBar ) => {
		onUpdateItem( index, updatedItem as AdminBar );
	};

	const handleUpdateSubmenus = (
		submenus: React.SetStateAction< AdminBar[] >
	) => {
		const newSubmenus =
			typeof submenus === 'function'
				? submenus( item.submenus || [] )
				: submenus;
		onUpdateItem( index, { ...item, submenus: newSubmenus } );
	};

	const handleAddSubmenu = ( menuData: {
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
			parent: item.id,
		};

		const currentSubmenus = item.submenus || [];
		const updatedSubmenus = [ ...currentSubmenus, newItem ];

		onUpdateItem( index, { ...item, submenus: updatedSubmenus } );
	};

	const handleDeleteMenu = () => {
		if ( onDeleteItem ) {
			onDeleteItem( index );
		}
	};

	const hasSubmenus = item?.submenus && item?.submenus?.length > 0;
	const isSeparator = item.title.toLowerCase().includes( 'separator' );
	const isCustomMenu =
		typeof item.id === 'string' && item.id.startsWith( 'neve-custom-' );

	return (
		<div className={ `mb-3 ${ level > 0 ? 'ml-6' : '' }` }>
			<div
				className={ cn(
					'bg-white border border-gray-400 rounded-lg shadow-md hover:shadow-lg transition-all border-gray-300',
					{ 'bg-gray-50': isSeparator }
				) }
			>
				<div className="flex items-center justify-between px-4 py-4 cursor-pointer">
					<div
						onClick={ () => setIsExpanded( ! isExpanded ) }
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' || e.key === ' ' ) {
								setIsExpanded( ! isExpanded );
							}
						} }
						role="button"
						tabIndex={ 0 }
						className="flex items-center flex-1"
					>
						{ ! item.title.startsWith( 'separator' ) && (
							<button
								type="button"
								className="mr-2 text-gray-500"
							>
								{ isExpanded ? (
									<Icon icon={ chevronDown } size={ 20 } />
								) : (
									<Icon icon={ chevronRight } size={ 20 } />
								) }
							</button>
						) }
						<div
							className={ cn( 'flex items-center flex-1 px-2', {
								'drag-handle': ! isNonDraggable,
							} ) }
						>
							<div className="flex-1">
								<div className="flex items-center gap-2">
									<div className="flex gap-2 flex-1 items-center">
										{ item.icon?.startsWith(
											'data:image/svg'
										) ? (
											<div
												className="custom-icon"
												style={ {
													backgroundImage: `url(${ item.icon })`,
													backgroundSize: 'contain',
													backgroundRepeat:
														'no-repeat',
													width: '24px',
													height: '24px',
													backgroundColor: '#000',
												} }
											></div>
										) : (
											<>
												{ item.icon?.startsWith(
													'dashicons'
												) ? (
													<span
														className={ cn(
															'dashicons-before',
															item.icon
														) }
													></span>
												) : (
													<span
														className={ item.icon }
													></span>
												) }
											</>
										) }
										<h3
											className={ cn(
												'font-semibold text-base',
												{
													'text-gray-500': isSeparator,
													'text-gray-800': ! isSeparator,
												}
											) }
										>
											{ item.title }
										</h3>
										{ item.frontendOnly && (
											<span className="ml-2 px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
												{ __(
													'Frontend',
													'neve-pro-addon'
												) }
											</span>
										) }
									</div>
								</div>
							</div>
						</div>
					</div>
					<div className="flex items-center gap-2">
						{ isCustomMenu && (
							<button
								type="button"
								onClick={ handleDeleteMenu }
								className="cursor-pointer"
							>
								<Icon icon={ trash } size={ 20 } />
							</button>
						) }
						<button
							type="button"
							onClick={ () => {
								setMenuStatus( ! menuStatus );
								handleUpdateSettings( {
									...item,
									hide: ! menuStatus,
								} );
							} }
							className="cursor-pointer"
						>
							{ menuStatus ? (
								<Icon
									icon={
										<svg
											viewBox="0 0 24 24"
											fill="none"
											stroke="#000"
										>
											<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
											<line
												x1="1"
												y1="1"
												x2="23"
												y2="23"
											/>
										</svg>
									}
									size={ 20 }
								/>
							) : (
								<Icon
									icon={
										<svg
											viewBox="0 0 24 24"
											fill="none"
											stroke="#000"
										>
											<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
											<circle cx="12" cy="12" r="3" />
										</svg>
									}
									size={ 20 }
								/>
							) }
						</button>
					</div>
				</div>

				{ isExpanded && (
					<div className="border-t border-gray-200 p-4">
						<div className="flex border-b border-gray-300 mb-4">
							<Tab
								title={ __( 'Settings', 'neve-pro-addon' ) }
								className={
									activeTab === 'settings'
										? 'border-b-2 border-blue-500 text-blue-600'
										: 'text-gray-500 hover:text-gray-700'
								}
								onclick={ () => setActiveTab( 'settings' ) }
							>
								<Icon
									icon={ cog }
									size={ 16 }
									className="mr-2"
								/>
							</Tab>
							<Tab
								title={ __( 'Submenu', 'neve-pro-addon' ) }
								className={
									activeTab === 'submenu'
										? 'border-b-2 border-blue-500 text-blue-600'
										: 'text-gray-500 hover:text-gray-700'
								}
								onclick={ () => setActiveTab( 'submenu' ) }
								afterTitle={ `(${ item.submenus?.length })` }
							>
								<Icon
									icon={ menu }
									size={ 16 }
									className="mr-2"
								/>
							</Tab>
						</div>

						{ activeTab === 'settings' && (
							<>
								{ item.id !== 'search' && (
									<SettingsTab
										item={ item }
										onUpdate={ handleUpdateSettings }
										currentTab={ currentTab }
									/>
								) }

								<div className="p-4 bg-gray-50 rounded-lg mt-4">
									<label
										className="block text-sm font-medium text-gray-700 mb-1"
										htmlFor={ `hide-for-user-roles-${ item.id }` }
									>
										{ __(
											'Hide for User Roles',
											'neve-pro-addon'
										) }
									</label>
									<Select
										inputId={ `hide-for-user-roles-${ item.id }` }
										placeholder={ __(
											'Search user role…',
											'neve-pro-addon'
										) }
										options={ userRoles }
										isMulti={ true }
										value={
											item.hiddenForRole
												? item.hiddenForRole.map(
														( role: string ) => ( {
															value: role,
															label:
																userRoles.find(
																	( r: {
																		value: string;
																		label:
																			| string
																			| string[];
																	} ) =>
																		r.value ===
																		role
																)?.label ||
																role,
														} )
												  )
												: []
										}
										onChange={ (
											selectedOptions: readonly {
												value: string;
												label: string | string[];
											}[]
										) => {
											const selectedRoles = selectedOptions
												? selectedOptions.map(
														( option: {
															value: string;
															label:
																| string
																| string[];
														} ) => option.value
												  )
												: [];
											handleUpdateSettings( {
												...item,
												hiddenForRole: selectedRoles,
											} );
										} }
									/>
								</div>
							</>
						) }
						{ activeTab === 'submenu' && hasSubmenus && (
							<div className="p-4 bg-gray-50 rounded-lg">
								<AdminBarList
									items={ item.submenus || [] }
									setItems={ handleUpdateSubmenus }
									level={ 1 }
									currentTab="submenu"
								/>
							</div>
						) }
						{ activeTab === 'submenu' && (
							<AddMenu onSave={ handleAddSubmenu } />
						) }
					</div>
				) }
			</div>
		</div>
	);
};

export default AdminBarItem;
