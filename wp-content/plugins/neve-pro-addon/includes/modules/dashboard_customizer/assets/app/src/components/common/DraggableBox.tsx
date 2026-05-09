import React from 'react';
import { Menu } from '../../types/types';
import { __ } from '@wordpress/i18n';

interface DraggableBoxProps {
	title: string;
	children?: React.ReactNode;
	onSave?: () => void;
	isSaving?: boolean;
	onReset?: () => void;
	isReseting?: boolean;
	currentItems?: Menu[];
}

const DraggableBox: React.FC< DraggableBoxProps > = ( {
	title,
	children,
	onSave,
	isSaving,
	onReset,
	isReseting,
} ) => {
	return (
		<>
			<header className="mb-6 pb-3">
				<div className="flex items-center justify-between">
					<div>
						<h1 className="text-2xl font-bold text-gray-800">
							{ title }
						</h1>
					</div>
					<div className="flex gap-2">
						{ onSave && (
							<button
								onClick={ onSave }
								disabled={ isSaving || isReseting }
								className="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer border-gray-300"
							>
								{ __( 'Save', 'neve-pro-addon' ) }
							</button>
						) }
						{ onReset && (
							<button
								onClick={ onReset }
								disabled={ isSaving || isReseting }
								className="flex items-center gap-2 px-6 py-3 bg-white text-black border rounded-md hover:text-white hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer border-gray-300"
							>
								{ __( 'Reset', 'neve-pro-addon' ) }
							</button>
						) }
					</div>
				</div>
			</header>

			{ children && <div className="mb-6">{ children }</div> }
		</>
	);
};

export default DraggableBox;
