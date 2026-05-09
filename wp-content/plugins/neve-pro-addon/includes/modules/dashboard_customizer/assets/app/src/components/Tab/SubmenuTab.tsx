import React from 'react';
import { Menu } from '../../types/types';
import AdminMenuList from '../../admin-menu/AdminMenuList';

const SubmenuTab: React.FC< {
	submenus: Record< string, Menu[] >;
	onUpdate: ( submenus: Record< string, Menu[] > ) => void;
	userRole: string;
} > = ( { submenus, onUpdate, userRole } ) => {
	return (
		<div className="p-4 bg-gray-50 rounded-lg">
			<AdminMenuList
				items={ submenus }
				setItems={ onUpdate }
				level={ 1 }
				currentTab="submenu"
				userRole={ userRole }
			/>
		</div>
	);
};

export default SubmenuTab;
