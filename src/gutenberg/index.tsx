import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel as PluginDocumentSettingPanelFromEditPost } from '@wordpress/edit-post';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import * as editorPackage from '@wordpress/editor';
import { __, sprintf } from '@wordpress/i18n';
import {
	PanelRow,
	Button,
	Notice,
	SelectControl,
	RadioControl,
	TextControl,
} from '@wordpress/components';
import { useSelect, useDispatch, dispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';
import { doAction } from '@wordpress/hooks';
import type { ReactNode } from 'react';
import { useState, useCallback } from '@wordpress/element';
import '../styles/gutenberg.scss';

const FieldSection = ({ title, children }: { title: string; children: ReactNode }) => (
	<div className="s2j-webinar-field">
		<h3 className="s2j-webinar-field__title">{title}</h3>
		<div className="s2j-webinar-field__content">{children}</div>
	</div>
);

type DocumentSettingPanel = typeof PluginDocumentSettingPanelFromEditPost;

const EDITOR_STORE = 'core/editor';

/** Avoid minifier reusing one-letter vars with inner panel state (breaks Panel component ref). */
const s2jWebinarDocumentSettings = {
	panelComponent:
		(editorPackage as { PluginDocumentSettingPanel?: DocumentSettingPanel })
			.PluginDocumentSettingPanel || PluginDocumentSettingPanelFromEditPost,
};

type Panelist = { name: string; email: string };

type SourceQrDocument = {
	status?: string;
	channels?: { id?: string }[];
	updated_at?: string;
};

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

const normalizeAttachmentId = (value: unknown): number => {
	if (typeof value === 'number' && value > 0) {
		return value;
	}
	if (typeof value === 'string' && value !== '') {
		const parsed = Number.parseInt(value, 10);
		if (parsed > 0) {
			return parsed;
		}
	}
	return 0;
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

const WebinarPanelFields = () => {
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
	const [coverArtId, setCoverArtId] = useEntityProp(
		'postType',
		postType,
		'_s2j_webinar_cover_art_id',
	);
	const [flyerId, setFlyerId] = useEntityProp('postType', postType, '_s2j_webinar_flyer_id');
	const coverArtAttachmentId = normalizeAttachmentId(coverArtId);
	const flyerAttachmentId = normalizeAttachmentId(flyerId);

	const coverArtMedia = useSelect(
		(select) => {
			if (coverArtAttachmentId <= 0) {
				return null;
			}
			const media = select('core').getMedia(coverArtAttachmentId) as {
				source_url?: string;
				alt_text?: string;
			} | null;
			return media;
		},
		[coverArtAttachmentId],
	);

	const flyerMedia = useSelect(
		(select) => {
			if (flyerAttachmentId <= 0) {
				return null;
			}
			const media = select('core').getMedia(flyerAttachmentId) as {
				source_url?: string;
				title?: string;
			} | null;
			return media;
		},
		[flyerAttachmentId],
	);
	const [webinarStatus] = useEntityProp('postType', postType, '_s2j_webinar_status');
	const [lastError] = useEntityProp('postType', postType, '_s2j_webinar_last_error');

	const { editPost } = useDispatch(EDITOR_STORE);
	const meta = useSelect(
		(select) => {
			const getEditedPostAttribute = select(EDITOR_STORE).getEditedPostAttribute;
			if (typeof getEditedPostAttribute !== 'function') {
				return {};
			}
			const edited = getEditedPostAttribute('meta');
			return edited && typeof edited === 'object' ? (edited as Record<string, unknown>) : {};
		},
		[],
	);
	const panelistList: Panelist[] = Array.isArray(meta._s2j_webinar_panelists)
		? (meta._s2j_webinar_panelists as Panelist[])
		: [];
	const setPanelists = useCallback(
		(next: Panelist[]) => {
			editPost?.({ meta: { ...meta, _s2j_webinar_panelists: next } });
		},
		[editPost, meta],
	);
	const webinarLastErrorText = typeof lastError === 'string' ? lastError.trim() : '';

	const [busy, setBusy] = useState(false);
	const [notice, setNotice] = useState<string | null>(null);
	const connected = window.s2jWebinarConfig?.connected ?? false;
	const sourceQrActive = window.s2jWebinarConfig?.sourceQrActive ?? false;
	const sourceQrOpenHook =
		window.s2jWebinarConfig?.sourceQrOpenHook ?? 's2j_webinar.open_source_qr';
	const sourceQrDoc = meta._s2j_webinar_source_qr as SourceQrDocument | undefined;
	const sourceQrSummary = (() => {
		if (!sourceQrDoc || typeof sourceQrDoc !== 'object') {
			return null;
		}
		const status = typeof sourceQrDoc.status === 'string' ? sourceQrDoc.status : '';
		const channelCount = Array.isArray(sourceQrDoc.channels) ? sourceQrDoc.channels.length : 0;
		const updatedAt =
			typeof sourceQrDoc.updated_at === 'string' ? sourceQrDoc.updated_at.trim() : '';
		return { status, channelCount, updatedAt };
	})();

	const openSourceQr = () => {
		if (postId > 0) {
			doAction(sourceQrOpenHook, postId);
		}
	};

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
					dispatch('core').invalidateResolution?.('getEntityRecord', [
						'postType',
						postType,
						postId,
					]);
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
		<>
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
			<FieldSection title={__('Status', 's2j-webinar')}>
				<p className="s2j-webinar-field__value">{webinarStatusLabel(webinarStatus)}</p>
			</FieldSection>
			{webinarLastErrorText !== '' && (
				<PanelRow>
					<Notice status="error" isDismissible={false}>
						{webinarLastErrorText}
					</Notice>
				</PanelRow>
			)}
			<FieldSection title={__('Recording', 's2j-webinar')}>
				<SelectControl
					label={__('Recording', 's2j-webinar')}
					hideLabelFromVision
					value={normalizeAutoRecording(autoRecording)}
					options={AUTO_RECORDING_OPTIONS}
					onChange={(value) => setAutoRecording(normalizeAutoRecording(value))}
				/>
			</FieldSection>
			<FieldSection title={__('CoverArt', 's2j-webinar')}>
				<MediaUploadCheck>
					<MediaUpload
						onSelect={(media) => setCoverArtId(normalizeAttachmentId(media?.id))}
						allowedTypes={['image']}
						value={coverArtAttachmentId > 0 ? coverArtAttachmentId : undefined}
						render={({ open }) => (
							<div className="s2j-webinar-media-field">
								{coverArtAttachmentId > 0 && coverArtMedia?.source_url && (
									<img
										className="s2j-webinar-media-field__preview"
										src={coverArtMedia.source_url}
										alt={coverArtMedia.alt_text ?? ''}
									/>
								)}
								<div className="s2j-webinar-media-field__actions">
									<Button onClick={open} disabled={busy}>
										{coverArtAttachmentId > 0
											? __('Replace CoverArt', 's2j-webinar')
											: __('Select media', 's2j-webinar')}
									</Button>
									{coverArtAttachmentId > 0 && (
										<Button
											variant="link"
											isDestructive
											onClick={() => setCoverArtId(0)}
											disabled={busy}
										>
											{__('Remove CoverArt', 's2j-webinar')}
										</Button>
									)}
								</div>
							</div>
						)}
					/>
				</MediaUploadCheck>
			</FieldSection>
			<FieldSection title={__('Flyer', 's2j-webinar')}>
				<p className="s2j-webinar-field__hint">
					{__(
						'Use a PDF exported from your design tool. PowerPoint files are not accepted.',
						's2j-webinar',
					)}
				</p>
				<MediaUploadCheck>
					<MediaUpload
						onSelect={(media) => setFlyerId(normalizeAttachmentId(media?.id))}
						allowedTypes={['application/pdf']}
						value={flyerAttachmentId > 0 ? flyerAttachmentId : undefined}
						render={({ open }) => (
							<div className="s2j-webinar-media-field">
								{flyerAttachmentId > 0 && flyerMedia?.source_url && (
									<p className="s2j-webinar-field__value">
										<a href={flyerMedia.source_url} target="_blank" rel="noreferrer">
											{flyerMedia.title?.trim() || __('Flyer PDF', 's2j-webinar')}
										</a>
									</p>
								)}
								<div className="s2j-webinar-media-field__actions">
									<Button onClick={open} disabled={busy}>
										{flyerAttachmentId > 0
											? __('Replace flyer', 's2j-webinar')
											: __('Select PDF', 's2j-webinar')}
									</Button>
									{flyerAttachmentId > 0 && (
										<Button
											variant="link"
											isDestructive
											onClick={() => setFlyerId(0)}
											disabled={busy}
										>
											{__('Remove flyer', 's2j-webinar')}
										</Button>
									)}
								</div>
							</div>
						)}
					/>
				</MediaUploadCheck>
			</FieldSection>
			<FieldSection title={__('Registration', 's2j-webinar')}>
				<RadioControl
					label={__('Registration', 's2j-webinar')}
					hideLabelFromVision
					selected={typeof approvalType === 'string' ? approvalType : '0'}
					options={[
						{ label: __('Required — auto approve', 's2j-webinar'), value: '0' },
						{ label: __('Required — manual approve', 's2j-webinar'), value: '1' },
						{ label: __('Not required', 's2j-webinar'), value: '2' },
					]}
					onChange={(value) => setApprovalType(value)}
				/>
			</FieldSection>
			<FieldSection title={__('Source tracking / QR', 's2j-webinar')}>
				{!sourceQrActive && (
					<p className="s2j-webinar-field__hint">
						{__(
							'Install and activate S2J Webinar Source QR to edit registration URLs and QR codes.',
							's2j-webinar',
						)}
					</p>
				)}
				{sourceQrActive && sourceQrSummary && (
					<p className="s2j-webinar-field__value">
						{sourceQrSummary.status !== '' &&
							sprintf(
								/* translators: 1: document status (draft/ready), 2: channel count */
								__('Document: %1$s · Channels: %2$d', 's2j-webinar'),
								sourceQrSummary.status,
								sourceQrSummary.channelCount,
							)}
						{sourceQrSummary.updatedAt !== '' &&
							` · ${sprintf(
								/* translators: %s: ISO datetime from Source QR document */
								__('Updated: %s', 's2j-webinar'),
								sourceQrSummary.updatedAt,
							)}`}
					</p>
				)}
				<Button onClick={openSourceQr} disabled={busy || !postId || !sourceQrActive}>
					{__('Edit source tracking / QR', 's2j-webinar')}
				</Button>
			</FieldSection>
			<FieldSection title={__('Panelists (at least one required to sync)', 's2j-webinar')}>
				{panelistList.map((row, index) => (
					<div key={`panelist-${index}`} className="s2j-webinar-panelist-row">
						<div className="s2j-webinar-panelist-row__toolbar">
							<span className="s2j-webinar-panelist-row__index">
								{sprintf(
									/* translators: %d: 1-based panelist row number in the editor UI. */
									__('Panelist %d', 's2j-webinar'),
									index + 1,
								)}
							</span>
							<Button
								variant="link"
								isDestructive
								className="s2j-webinar-panelist-row__remove"
								onClick={() => removePanelist(index)}
								disabled={busy}
							>
								{__('Remove panelist', 's2j-webinar')}
							</Button>
						</div>
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
					</div>
				))}
				<Button onClick={addPanelist} disabled={busy}>
					{__('Add panelist', 's2j-webinar')}
				</Button>
			</FieldSection>
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
		</>
	);
};

const WebinarPanel = () => {
	const DocumentPanel = s2jWebinarDocumentSettings.panelComponent;
	if (!DocumentPanel) {
		return (
			<Notice status="error" isDismissible={false}>
				{__(
					'PluginDocumentSettingPanel is unavailable in this WordPress version.',
					's2j-webinar',
				)}
			</Notice>
		);
	}

	return (
		<DocumentPanel
			name="s2j-webinar-event-panel"
			title={__('S2J Webinar', 's2j-webinar')}
			className="s2j-webinar-document-panel"
		>
			<WebinarPanelFields />
		</DocumentPanel>
	);
};

registerPlugin('s2j-webinar-event-panel', {
	render: WebinarPanel,
});
