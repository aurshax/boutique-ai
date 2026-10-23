// Try-on so'rovi — server holati. Manba: TryOnController::present() (status JSON).

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
    error: string | null;
    result_url: string | null;
    has_photo: boolean;
    suggested_size: string | null;
    cost_usd: string | null;
    look_id: number | null;
    fabric_id: number | null;
    created_at: string | null;
};

/** POST /try-on/requests tanasi. `sourceRequestId` bo'lsa rasm qayta yuborilmaydi (tkan almashtirish). */
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
