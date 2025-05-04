import { writable } from 'svelte/store';

// Read initial theme from localStorage or default to 'light'
const stored = typeof localStorage !== 'undefined' ? localStorage.getItem('theme') : null;
export const theme = writable(stored === 'dark' ? 'dark' : 'light');

// Persist theme changes to localStorage
if (typeof window !== 'undefined') {
    theme.subscribe((value) => {
        localStorage.setItem('theme', value);
        document.documentElement.setAttribute('data-theme', value);
    });
}
