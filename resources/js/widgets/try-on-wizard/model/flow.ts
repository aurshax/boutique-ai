// Try-on sahifasining qadamlar mashinasi. UI qaysi ekranni ko'rsatishi faqat shu yerda hal qilinadi.
//
//   setup ──submit(202)──▶ waiting ──status=done──▶ result
//     ▲                      │                        │
//     └──status=failed/cancelled                      ├──"Boshqa look"──▶ setup
//                                                     └──tkan almashtirish (202)──▶ waiting
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
            return step; // queued/running — holat o'zgarmaydi
        case 'edit':
            return step === 'result' ? 'setup' : step;
        case 'reset':
            return 'setup';
    }
}

export const STEP_LABELS: Record<Step, string> = {
    setup: '1 · Siz va look',
    waiting: '2 · Tikilmoqda',
    result: '3 · Natija',
};
