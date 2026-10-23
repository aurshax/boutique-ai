import type { TryOnStatus } from './types';

/** The server is still working — keep polling. */
export const isPending = (status: TryOnStatus | undefined): boolean =>
    status === 'queued' || status === 'running';

export const isFinished = (status: TryOnStatus | undefined): boolean =>
    status === 'done' || status === 'failed' || status === 'cancelled';
