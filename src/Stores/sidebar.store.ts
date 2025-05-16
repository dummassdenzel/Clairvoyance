import { writable } from 'svelte/store';

export const isSidebarMinimized = writable<boolean>(false);