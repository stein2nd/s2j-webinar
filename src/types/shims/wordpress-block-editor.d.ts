import type { ComponentType, ReactNode } from 'react';

type MediaRecord = {
	id?: number;
	source_url?: string;
	alt_text?: string;
	title?: string;
};

export declare const MediaUploadCheck: ComponentType<{ children?: ReactNode }>;

export declare const MediaUpload: ComponentType<{
	onSelect: (media: MediaRecord) => void;
	allowedTypes?: string[];
	value?: number;
	render: (props: { open: () => void }) => ReactNode;
}>;
