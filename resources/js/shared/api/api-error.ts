// HTTP xatolari — shared/api ichida yagona tur. UI xabarlari bu yerda emas (ular slice'larda).

export class HttpError extends Error {
    constructor(
        public readonly status: number,
        public readonly body: unknown,
    ) {
        super(`HTTP ${status}`);
        this.name = 'HttpError';
    }

    get isValidation(): boolean {
        return this.status === 422;
    }

    /** Laravel 422 javobidagi `errors` (maydon → xabarlar). */
    get validationErrors(): ValidationErrors {
        return (
            (this.body as { errors?: ValidationErrors } | null)?.errors ?? {}
        );
    }
}

/** Laravel validatsiya xatolari: { photo: ['...'], height_cm: ['...'] } */
export type ValidationErrors = Partial<Record<string, string[]>>;

export const isAbortError = (error: unknown): boolean =>
    error instanceof DOMException && error.name === 'AbortError';
