import React, { ReactNode } from 'react';
import cn from 'classnames';

type Props = {
	children: ReactNode;
	className?: string;
};

const Container: React.FC< Props > = ( { children, className } ) => (
	<div
		className={ cn( [
			'max-w-[90vw] w-full lg:container mx-auto px-2 lg:px-6',
			className,
		] ) }
	>
		{ children }
	</div>
);

export default Container;
