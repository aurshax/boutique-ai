// Inertia sahifa nomi → layout. Sahifa nomi = pages/ ichidagi yo'l (masalan "auth/LoginPage").
import type { Component } from 'vue';
import AppLayout from './AppLayout.vue';
import AuthLayout from './AuthLayout.vue';
import SettingsLayout from './settings/Layout.vue';

/** O'z header'iga ega to'liq ekranli sahifalar (boutique-ai sayti). */
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
