import { StrictMode, render } from '@wordpress/element';
import AdminBar from './AdminBar.tsx';
import '../scss/admin-bar.scss';

const Root = () => (
	<StrictMode>
		<AdminBar />
	</StrictMode>
);
render( <Root />, document.getElementById( 'admin_bar_editor' ) );
