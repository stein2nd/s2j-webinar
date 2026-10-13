declare module '@wordpress/core-data' {
	export function useEntityProp(
		kind: string,
		name: string,
		prop: string,
	): [unknown, (value: unknown) => void];
}

declare module '@wordpress/plugins' {
	import type { ComponentType, ReactNode } from 'react';

	export interface WPPlugin {
		name?: string;
		icon?: string | ComponentType;
		render: ComponentType | (() => ReactNode);
	}

	export function registerPlugin(name: string, settings: WPPlugin): void;
}

interface Window {
	s2jWebinarConfig?: {
		connected?: boolean;
		restRoot?: string;
		nonce?: string;
		/** Site webinar provider slug (e.g. zoom). Matches Settings → S2J Webinar. */
		providerSlug?: string;
		/** Localized display name for providerSlug. */
		providerLabel?: string;
	};
}
