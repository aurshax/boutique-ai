// Public API — the only entry point into this slice from other slices.
export type { Auth, Passkey, User } from './model/types';
export * from './lib/useInitials';
export { default as UserInfo } from './ui/UserInfo.vue';
