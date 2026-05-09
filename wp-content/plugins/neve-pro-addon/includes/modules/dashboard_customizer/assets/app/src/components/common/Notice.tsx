import React from 'react';
import cn from 'classnames';

const Notice: React.FC< {
	message: string;
	status?: 'idle' | 'success' | 'error';
} > = ( { message, status = 'success' } ) => {
	return (
		<div
			className={ cn( 'rounded-lg p-4 flex items-start gap-3 mt-2', {
				'bg-green-50 border border-green-200': status === 'success',
				'bg-red-50 border border-red-200': status === 'error',
			} ) }
		>
			<div>
				<p className="text-sm text-green-800 font-medium">
					{ message }
				</p>
			</div>
		</div>
	);
};

export default Notice;
