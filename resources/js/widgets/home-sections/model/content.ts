// Home section structure (numbering, icons, layout). The copy itself is in shared/i18n (home.*).
import { Clock, ListChecks, Lock } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

/** "How it works" step numbers; titles/texts are home.how.steps[i]. */
export const HOW_STEP_NUMBERS = ['01', '02', '03'];

/** Trust block icons; titles/texts are home.trust.items[i]. */
export const TRUST_ICONS: LucideIcon[] = [Clock, Lock, ListChecks];

/** Fabric showcase: bottom offset per card for a staircase effect (md+). */
export const FABRIC_OFFSETS = ['md:mb-0', 'md:mb-12', 'md:mb-24'];
