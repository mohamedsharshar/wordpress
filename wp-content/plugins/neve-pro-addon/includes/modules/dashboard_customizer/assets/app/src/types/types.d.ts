type ParentPage = {
	label: string;
	value: string;
};

interface LocalizedData {
	parentPage: ParentPage[];
	icons: string[];
}

interface AdminMenuData {
	assets: string;
	version: string;
	menu: Record< string, Menu[] >;
	url: string;
	nonce: string;
	roles: Record< string, Record< string, string | string[] > >;
	icons: string[];
}

interface AdminBarData {
	assets: string;
	version: string;
	url: string;
	nonce: string;
	roles: Record< string, Record< string, string | string[] > >;
	menu: AdminBar[];
}

declare global {
	interface Window {
		adminPage: LocalizedData;
		adminMenu: AdminMenuData;
		adminBar: AdminBarData;
	}
}

export type AdminBar = {
	id: number | string;
	priority: number | string;
	title: string;
	titleDefault?: string;
	href: string;
	icon?: string;
	iconType?: 'dashicon' | 'svg';
	submenus?: AdminBar[];
	hide?: boolean;
	hiddenForRole?: string[];
	frontendOnly?: boolean;
	parent?: number | string;
};

export type Menu = {
	id: number;
	priority: number | string;
	capability: string | string[];
	title: string;
	href: string;
	icon?: string;
	iconType?: 'dashicon' | 'svg';
	submenus?: Record< string, Menu[] >;
	hide?: boolean;
};
