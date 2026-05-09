import React from 'react';
import { AdminBar, Menu } from '../types/types';
import { useState, useEffect } from '@wordpress/element';
import SettingsTab from '../components/Tab/SettingsTab';
import { chevronDown, chevronRight, cog, Icon, menu } from '@wordpress/icons';
import cn from 'classnames';
import Tab from '../components/Tab/Tab';
import { __ } from '@wordpress/i18n';
import AdminMenuList from './AdminMenuList';

interface AdminMenuItemProps {
	item: Menu;
	index: number;
	onUpdateItem: ( index: number, updatedItem: Menu ) => void;
	level?: number;
	currentTab?: string;
	userRole: string;
}

const AdminMenuItem: React.FC< AdminMenuItemProps > = ( {
	item,
	index,
	onUpdateItem,
	level = 0,
	currentTab = '',
	userRole,
} ) => {
	const [ isExpanded, setIsExpanded ] = useState< boolean >( false );
	const [ menuStatus, setMenuStatus ] = useState< boolean >(
		item?.hide || true
	);
	const [ activeTab, setActiveTab ] = useState< 'settings' | 'submenu' >(
		'settings'
	);

	// Sync menuStatus with item.hide when item changes (e.g., when switching role tabs)
	useEffect( () => {
		setMenuStatus( item?.hide || false );
	}, [ item?.hide ] );

	const handleUpdateSettings = ( updatedItem: Menu | AdminBar ) => {
		// Type guard: ensure we're working with Menu type
		if ( 'capability' in updatedItem ) {
			onUpdateItem( index, updatedItem as Menu );
		}
	};

	const handleUpdateSubmenus = ( submenus: Record< string, Menu[] > ) => {
		onUpdateItem( index, { ...item, submenus } );
	};

	const hasSubmenus =
		item?.submenus && item?.submenus?.[ userRole ]?.length > 0;
	const isSeparator = item.title.toLowerCase().includes( 'separator' );

	return (
		<div className={ `mb-3 ${ level > 0 ? 'ml-6' : '' }` }>
			<div
				className={ cn(
					'bg-white border-2 border-gray-400 rounded-lg shadow-md hover:shadow-lg transition-all',
					{ isSeparator: 'border-gray-300 bg-gray-50' }
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
						<div className="flex items-center flex-1 drag-handle px-2">
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
									</div>
								</div>
							</div>
						</div>
					</div>
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
										<line x1="1" y1="1" x2="23" y2="23" />
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

				{ isExpanded && (
					<div className="border-t border-gray-200 p-4">
						<div className="flex border-b border-gray-200 mb-4">
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
							{ hasSubmenus && (
								<Tab
									title={ __( 'Submenu', 'neve-pro-addon' ) }
									className={
										activeTab === 'submenu'
											? 'border-b-2 border-blue-500 text-blue-600'
											: 'text-gray-500 hover:text-gray-700'
									}
									onclick={ () => setActiveTab( 'submenu' ) }
									afterTitle={ `(${ item.submenus?.[ userRole ].length })` }
								>
									<Icon
										icon={ menu }
										size={ 16 }
										className="mr-2"
									/>
								</Tab>
							) }
						</div>

						{ activeTab === 'settings' && (
							<SettingsTab
								item={ item }
								onUpdate={ handleUpdateSettings }
								currentTab={ currentTab }
							/>
						) }
						{ activeTab === 'submenu' && hasSubmenus && (
							<div className="p-4 bg-gray-50 rounded-lg">
								<AdminMenuList
									items={ item.submenus || {} }
									setItems={ handleUpdateSubmenus }
									level={ 1 }
									currentTab="submenu"
									userRole={ userRole }
								/>
							</div>
						) }
					</div>
				) }
			</div>
		</div>
	);
};

export default AdminMenuItem;
