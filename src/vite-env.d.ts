/// <reference types="vite/client" />
/// <reference path="./types/wordpress-shims.d.ts" />

declare module '*.scss' {
	const content: string;
	export default content;
}
