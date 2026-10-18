declare module '@wordpress/data' {
	type StoreSelect = (storeName: string) => {
		getEditedPostAttribute?: (attributeName: string) => unknown;
		invalidateResolution?: (selectorName: string, args: unknown[]) => void;
	};

	export function useSelect<T>(mapSelect: (select: StoreSelect) => T, deps?: readonly unknown[]): T;

	export function useDispatch(storeName: string): {
		editPost?: (edits: { meta?: Record<string, unknown> }) => void;
	};

	export function dispatch(storeName: string): {
		invalidateResolution?: (selectorName: string, args: unknown[]) => void;
	};
}

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
		sourceQrActive?: boolean;
		sourceQrOpenHook?: string;
	};
}

declare module '@wordpress/hooks' {
	export function doAction(hookName: string, ...args: unknown[]): void;
}
