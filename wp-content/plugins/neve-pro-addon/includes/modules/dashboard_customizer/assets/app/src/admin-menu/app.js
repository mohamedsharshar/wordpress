import { StrictMode, render } from '@wordpress/element';
import AdminMenu from './AdminMenu.tsx';
import '../scss/admin-menu.scss';

const Root = () => (
	<StrictMode>
		<AdminMenu />
	</StrictMode>
);
render( <Root />, document.getElementById( 'admin_menu_editor' ) );
