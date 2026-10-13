import { Fill } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
import { __, sprintf } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	Button,
	Notice,
	SelectControl,
	RadioControl,
	TextControl,
} from '@wordpress/components';
import { useSelect, dispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';
import { useState, useCallback } from '@wordpress/element';
import '../styles/gutenberg.scss';

const SLOT = 'EventPluginDocumentSettings';

type Panelist = { name: string; email: string };

type AutoRecording = 'cloud' | 'local' | 'none';

const AUTO_RECORDING_OPTIONS: { label: string; value: AutoRecording }[] = [
	{ label: __('Cloud', 's2j-webinar'), value: 'cloud' },
	{ label: __('Local', 's2j-webinar'), value: 'local' },
	{ label: __('None', 's2j-webinar'), value: 'none' },
];

const normalizeAutoRecording = (value: unknown): AutoRecording => {
	if (value === 'cloud' || value === 'local' || value === 'none') {
		return value;
	}
	return 'cloud';
};

const webinarStatusLabel = (code: unknown): string => {
	const status = typeof code === 'string' ? code : 'not_created';
	switch (status) {
		case 'synced':
			return __('Synced', 's2j-webinar');
		case 'dirty':
			return __('Changes not synced', 's2j-webinar');
		case 'error':
			return __('Error', 's2j-webinar');
		default:
			return __('Not created', 's2j-webinar');
	}
};

/** Display name from site settings (Settings → S2J Webinar). */
const webinarProviderLabel = (): string => {
	const label = window.s2jWebinarConfig?.providerLabel?.trim();
	return label !== undefined && label !== '' ? label : 'Zoom';
};

const WebinarPanel = () => {
	const postId = useSelect((select) => {
		const editor = select('core/editor') as { getCurrentPostId?: () => number };
		return editor.getCurrentPostId?.() ?? 0;
	}, []);

	const postType = useSelect((select) => {
		const editor = select('core/editor') as { getCurrentPostType?: () => string };
		return editor.getCurrentPostType?.() ?? '';
	}, []);

	const [autoRecording, setAutoRecording] = useEntityProp(
		'postType',
		postType,
		'_s2j_webinar_auto_recording',
	);
	const [approvalType, setApprovalType] = useEntityProp(
		'postType',
		postType,
		'_s2j_webinar_approval_type',
	);
	const [panelists, setPanelists] = useEntityProp('postType', postType, '_s2j_webinar_panelists');
	const [webinarStatus] = useEntityProp('postType', postType, '_s2j_webinar_status');
	const [lastError] = useEntityProp('postType', postType, '_s2j_webinar_last_error');

	const panelistList: Panelist[] = Array.isArray(panelists) ? (panelists as Panelist[]) : [];
	const lastErrorText = typeof lastError === 'string' ? lastError.trim() : '';

	const [busy, setBusy] = useState(false);
	const [notice, setNotice] = useState<string | null>(null);
	const connected = window.s2jWebinarConfig?.connected ?? false;

	const panelistCount = panelistList.filter((p) => p.email?.trim()).length;
	const canSync = connected && postId > 0 && panelistCount > 0;

	const runAction = useCallback(
		async (path: string, openUrl?: boolean) => {
			if (!postId) {
				return;
			}
			setBusy(true);
			setNotice(null);
			try {
				const response = (await apiFetch({
					path,
					method: 'POST',
				})) as {
					ok?: boolean;
					message?: string;
					user_messages?: string[];
					start_url?: string;
				};
				if (response.user_messages?.length) {
					setNotice(response.user_messages.join(' '));
				} else if (response.ok) {
					setNotice(__('Request completed.', 's2j-webinar'));
				} else {
					setNotice(__('Request could not complete.', 's2j-webinar'));
				}
				if (openUrl && response.start_url) {
					window.open(response.start_url, '_blank', 'noopener,noreferrer');
				}
			} catch {
				setNotice(__('Request could not complete. Check settings and event fields.', 's2j-webinar'));
			} finally {
				if (postId > 0 && postType !== '') {
					dispatch('core').invalidateResolution('getEntityRecord', ['postType', postType, postId]);
				}
				setBusy(false);
			}
		},
		[postId, postType],
	);

	const updatePanelist = (index: number, field: keyof Panelist, value: string) => {
		const next = [...panelistList];
		while (next.length <= index) {
			next.push({ name: '', email: '' });
		}
		next[index] = { ...next[index], [field]: value };
		setPanelists(next);
	};

	const addPanelist = () => {
		setPanelists([...panelistList, { name: '', email: '' }]);
	};

	const removePanelist = (index: number) => {
		setPanelists(panelistList.filter((_, i) => i !== index));
	};

	const confirmDelete = () => {
		const message = sprintf(
			/* translators: %s: webinar provider name, e.g. Zoom */
			__('Remove this webinar from %s?', 's2j-webinar'),
			webinarProviderLabel(),
		);
		if (!window.confirm(message)) {
			return;
		}
		void runAction(`/s2j-webinar/v1/events/${postId}/delete`);
	};

	return (
		<Fill name={SLOT}>
			<PanelBody title={__('Webinar', 's2j-webinar')} initialOpen={false}>
				{!connected && (
					<Notice status="warning" isDismissible={false}>
						{sprintf(
							/* translators: %s: webinar provider name, e.g. Zoom */
							__(
								'%s is not connected. Open Settings → S2J Webinar (or GatherPress Settings → Webinar).',
								's2j-webinar',
							),
							webinarProviderLabel(),
						)}
					</Notice>
				)}
				<PanelRow>
					<p>
						<strong>{__('Status', 's2j-webinar')}</strong>
						<br />
						{webinarStatusLabel(webinarStatus)}
					</p>
				</PanelRow>
				{lastErrorText !== '' && (
					<PanelRow>
						<Notice status="error" isDismissible={false}>
							{lastErrorText}
						</Notice>
					</PanelRow>
				)}
				<PanelRow>
					<SelectControl
						label={__('Recording', 's2j-webinar')}
						value={normalizeAutoRecording(autoRecording)}
						options={AUTO_RECORDING_OPTIONS}
						onChange={(value) => setAutoRecording(normalizeAutoRecording(value))}
					/>
				</PanelRow>
				<PanelRow>
					<RadioControl
						label={__('Registration', 's2j-webinar')}
						selected={typeof approvalType === 'string' ? approvalType : '0'}
						options={[
							{ label: __('Required — auto approve', 's2j-webinar'), value: '0' },
							{ label: __('Required — manual approve', 's2j-webinar'), value: '1' },
							{ label: __('Not required', 's2j-webinar'), value: '2' },
						]}
						onChange={(value) => setApprovalType(value)}
					/>
				</PanelRow>
				<PanelRow>
					<p>{__('Panelists (at least one required to sync)', 's2j-webinar')}</p>
				</PanelRow>
				{panelistList.map((row, index) => (
					<PanelRow key={`panelist-${index}`}>
						<div className="s2j-webinar-panelist-row">
							<TextControl
								label={__('Name', 's2j-webinar')}
								value={row.name}
								onChange={(value) => updatePanelist(index, 'name', value)}
							/>
							<TextControl
								label={__('Email', 's2j-webinar')}
								type="email"
								value={row.email}
								onChange={(value) => updatePanelist(index, 'email', value)}
							/>
							<Button isDestructive onClick={() => removePanelist(index)} disabled={busy}>
								{__('Remove', 's2j-webinar')}
							</Button>
						</div>
					</PanelRow>
				))}
				<PanelRow>
					<Button onClick={addPanelist} disabled={busy}>
						{__('Add panelist', 's2j-webinar')}
					</Button>
				</PanelRow>
				<PanelRow>
					<Button
						variant="primary"
						onClick={() => runAction(`/s2j-webinar/v1/events/${postId}/sync`)}
						disabled={busy || !canSync}
					>
						{__('Sync', 's2j-webinar')}
					</Button>
					<Button
						onClick={() => runAction(`/s2j-webinar/v1/events/${postId}/open`, true)}
						disabled={busy || !connected || !postId}
					>
						{sprintf(
							/* translators: %s: webinar provider name, e.g. Zoom */
							__('Open in %s', 's2j-webinar'),
							webinarProviderLabel(),
						)}
					</Button>
					<Button isDestructive onClick={confirmDelete} disabled={busy || !connected || !postId}>
						{sprintf(
							/* translators: %s: webinar provider name, e.g. Zoom */
							__('Delete from %s', 's2j-webinar'),
							webinarProviderLabel(),
						)}
					</Button>
				</PanelRow>
				{notice && (
					<PanelRow>
						<p>{notice}</p>
					</PanelRow>
				)}
			</PanelBody>
		</Fill>
	);
};

registerPlugin('s2j-webinar-event-panel', {
	render: WebinarPanel,
});
