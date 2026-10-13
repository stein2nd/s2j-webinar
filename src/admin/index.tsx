import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import '../styles/admin.scss';

const root = document.getElementById('s2j-webinar-admin-root');
if (root) {
	createRoot(root).render(
		<p className="s2j-webinar-admin-placeholder">
			{__('S2J Webinar settings are saved above. Event panel controls load in the block editor.', 's2j-webinar')}
		</p>
	);
}
