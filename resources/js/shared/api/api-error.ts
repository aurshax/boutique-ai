// HTTP errors — one type for shared/api. User-facing messages live in the slices, not here.

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

    /** `errors` from a Laravel 422 response (field → messages). */
    get validationErrors(): ValidationErrors {
        return (
            (this.body as { errors?: ValidationErrors } | null)?.errors ?? {}
        );
    }
}

/** Laravel validation errors: { photo: ['...'], height_cm: ['...'] } */
export type ValidationErrors = Partial<Record<string, string[]>>;

export const isAbortError = (error: unknown): boolean =>
    error instanceof DOMException && error.name === 'AbortError';
