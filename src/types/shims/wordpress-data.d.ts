/** Subset of `@wordpress/data` selectors used by this plugin (replaces package `build-types`). */

type DataStoreSelectors = {
	getEditedPostAttribute?: (attributeName: string) => unknown;
	getCurrentPostId?: () => number;
	getCurrentPostType?: () => string;
	getMedia?: (attachmentId: number) => unknown;
	getEditedEntityRecord?: (kind: string, name: string, id: number) => unknown;
	invalidateResolution?: (selectorName: string, args: unknown[]) => void;
};

type StoreSelect = (storeName: string) => DataStoreSelectors;

export function useSelect<T>(mapSelect: (select: StoreSelect) => T, deps?: readonly unknown[]): T;

export function useDispatch(storeName: string): {
	editPost?: (edits: { meta?: Record<string, unknown> }) => void;
};

export function dispatch(storeName: string): {
	invalidateResolution?: (selectorName: string, args: unknown[]) => void;
};
