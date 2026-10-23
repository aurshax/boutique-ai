// Inertia page name → layout. The page name is the path under pages/ (e.g. "auth/LoginPage").
import type { Component } from 'vue';
import AppLayout from './AppLayout.vue';
import AuthLayout from './AuthLayout.vue';
import SettingsLayout from './settings/Layout.vue';

/** Full-screen pages with their own header (the boutique-ai site). */
const STANDALONE = ['home/', 'try-on/'];

export function resolveLayout(name: string): Component | Component[] | null {
    switch (true) {
        case STANDALONE.some((prefix) => name.startsWith(prefix)):
            return null;
        case name.startsWith('auth/'):
            return AuthLayout;
        case name.startsWith('settings/'):
            return [AppLayout, SettingsLayout];
        default:
            return AppLayout;
    }
}
