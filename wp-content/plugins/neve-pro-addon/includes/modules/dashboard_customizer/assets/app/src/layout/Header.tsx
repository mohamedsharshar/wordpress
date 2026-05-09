import React from 'react';
import Container from './Container';
import { __ } from '@wordpress/i18n';

interface HeaderProps {
	assets: string;
	version: string;
}
const Header: React.FC< HeaderProps > = ( { assets, version } ) => {
	const isLicenseValid = true;

	return (
		<div className="border-b border-gray-100">
			<Container>
				<div className="gap-5 py-2 sm:flex-row items-center justify-between">
					<div className="flex items-center space-x-3">
						<img
							className="size-7 rounded-sm"
							src={ assets + 'images/logo.svg' }
							alt={ __( 'Neve Theme Logo', 'neve-pro-addon' ) }
						/>
						<span className="text-sm font-semibold text-gray-900">
							{ __( 'Neve', 'neve-pro-addon' ) }
						</span>
						<span className="g-gray-100 text-gray-700">
							{ isLicenseValid
								? __( 'PRO', 'neve-pro-addon' )
								: __( 'Free', 'neve-pro-addon' ) }
						</span>
						<span className="text-gray-500 font-medium">
							{ version }
						</span>
					</div>
				</div>
			</Container>
		</div>
	);
};

export default Header;
