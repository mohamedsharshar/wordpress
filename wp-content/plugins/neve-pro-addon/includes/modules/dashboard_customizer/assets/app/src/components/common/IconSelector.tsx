import React from 'react';
import { useState } from '@wordpress/element';
import cn from 'classnames';
import { __ } from '@wordpress/i18n';

const IconSelector: React.FC< {
	iconType: 'dashicon' | 'svg';
	iconValue: string;
	onIconTypeChange: ( type: 'dashicon' | 'svg' ) => void;
	onIconValueChange: ( value: string ) => void;
} > = ( { iconType, iconValue, onIconTypeChange, onIconValueChange } ) => {
	const [ showDashicons, setShowDashicons ] = useState( false );
	const { icons } = window.adminMenu;

	return (
		<div className="space-y-3">
			<label
				className="block text-sm font-medium text-gray-700"
				id="menu-icon"
				htmlFor="menu-icon-input"
			>
				{ __( 'Icon', 'neve-pro-addon' ) }
			</label>

			<div className="flex border-b border-gray-200">
				<button
					type="button"
					onClick={ () => onIconTypeChange( 'dashicon' ) }
					className={ `px-4 py-2 text-sm font-medium ${
						iconType === 'dashicon'
							? 'border-b-2 border-blue-500 text-blue-600'
							: 'text-gray-500 hover:text-gray-700'
					}` }
				>
					{ __( 'Dashicons', 'neve-pro-addon' ) }
				</button>
				<button
					type="button"
					onClick={ () => onIconTypeChange( 'svg' ) }
					className={ `px-4 py-2 text-sm font-medium ${
						iconType === 'svg'
							? 'border-b-2 border-blue-500 text-blue-600'
							: 'text-gray-500 hover:text-gray-700'
					}` }
				>
					{ __( 'SVG Code', 'neve-pro-addon' ) }
				</button>
			</div>

			{ iconType === 'dashicon' && (
				<div className="relative">
					<input
						type="text"
						id="menu-icon-input"
						value={
							iconType === 'dashicon' &&
							iconValue.startsWith( 'dashicon' )
								? iconValue
								: ''
						}
						onChange={ ( e ) =>
							onIconValueChange( e.target.value )
						}
						onClick={ () => setShowDashicons( ! showDashicons ) }
						className="w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500"
					/>

					{ showDashicons && (
						<div className="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto">
							<div className="grid grid-cols-4 gap-2 p-2">
								{ icons.map( ( icon ) => (
									<button
										type="button"
										key={ icon }
										onClick={ () => {
											onIconValueChange(
												icon.replace( 'dashicons ', '' )
											);
											setShowDashicons( false );
										} }
										className={ `p-2 text-center hover:bg-blue-50 rounded ${
											iconValue === icon
												? 'bg-blue-100'
												: ''
										}` }
										title={ icon }
									>
										<span
											className={ cn(
												'dashicons',
												icon
											) }
										></span>
									</button>
								) ) }
							</div>
						</div>
					) }
				</div>
			) }

			{ iconType === 'svg' && (
				<textarea
					value={
						iconType === 'svg' &&
						iconValue.startsWith( 'data:image/svg' )
							? iconValue
							: ''
					}
					onChange={ ( e ) => onIconValueChange( e.target.value ) }
					className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
					rows={ 6 }
				/>
			) }
		</div>
	);
};

export default IconSelector;
