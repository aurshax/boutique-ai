// A try-on request as the server reports it. Source: TryOnController::present() (status JSON).

export type TryOnStatus =
    | 'queued'
    | 'running'
    | 'done'
    | 'failed'
    | 'cancelled';

export type Quality = 'medium' | 'high';

export type TryOnRequest = {
    id: string;
    status: TryOnStatus;
    /** Provider that ran the request: 'mock' (free demo) or 'openai'. */
    provider: string;
    error: string | null;
    result_url: string | null;
    has_photo: boolean;
    suggested_size: string | null;
    cost_usd: string | null;
    look_id: number | null;
    fabric_id: number | null;
    created_at: string | null;
};

/** POST /try-on/requests body. With `sourceRequestId` the photo is not re-uploaded (fabric swap). */
export type CreateTryOnPayload = {
    photo: File | null;
    sourceRequestId: string | null;
    heightCm: number;
    waistCm: number | null;
    size: string | null;
    lookId: number;
    fabricId: number | null;
    quality: Quality;
    consent: boolean;
};
