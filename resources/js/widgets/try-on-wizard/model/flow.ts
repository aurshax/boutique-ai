// The try-on step machine. Which screen the UI shows is decided here and nowhere else.
//
//   setup ──submit(202)──▶ waiting ──status=done──▶ result
//     ▲                      │                        │
//     └──status=failed/cancelled                      ├──"Another look"──▶ setup
//                                                     └──fabric swap (202)──▶ waiting
import type { TryOnStatus } from '@/entities/try-on-request';

export type Step = 'setup' | 'waiting' | 'result';

export type FlowEvent =
    | { type: 'submitted' }
    | { type: 'status'; status: TryOnStatus }
    | { type: 'edit' }
    | { type: 'reset' };

export function transition(step: Step, event: FlowEvent): Step {
    switch (event.type) {
        case 'submitted':
            return 'waiting';
        case 'status':
            if (event.status === 'done')
                return step === 'waiting' ? 'result' : step;
            if (event.status === 'failed' || event.status === 'cancelled')
                return 'setup';
            return step; // queued/running — no change
        case 'edit':
            return step === 'result' ? 'setup' : step;
        case 'reset':
            return 'setup';
    }
}

/** i18n keys for the step indicator. */
export const STEP_LABEL_KEYS: Record<Step, string> = {
    setup: 'tryOn.steps.setup',
    waiting: 'tryOn.steps.waiting',
    result: 'tryOn.steps.result',
};
