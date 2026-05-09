import React from 'react';
import cn from 'classnames';

interface TabProps {
	title: string;
	onclick: React.MouseEventHandler< HTMLButtonElement >;
	className?: string;
	children?: React.ReactNode;
	afterTitle?: string;
}

const Tab: React.FC< TabProps > = ( {
	title,
	className,
	onclick,
	children,
	afterTitle,
} ) => {
	return (
		<button
			type="button"
			onClick={ onclick }
			className={ cn(
				'flex items-center px-4 py-2 text-sm font-medium',
				className
			) }
		>
			{ children }
			{ title } { afterTitle }
		</button>
	);
};

export default Tab;
