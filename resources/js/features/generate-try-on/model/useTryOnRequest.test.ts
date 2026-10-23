import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { effectScope } from 'vue';
import type {
    CreateTryOnPayload,
    TryOnRequest,
} from '@/entities/try-on-request';
import { HttpError, requestJson } from '@/shared/api';
import { POLL_INTERVAL_MS, useTryOnRequest } from './useTryOnRequest';

// Only the network layer (shared/api) is mocked — tryOnApi and polling are real.
vi.mock('@/shared/api', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@/shared/api')>();
    return { ...actual, requestJson: vi.fn() };
});
const request = vi.mocked(requestJson);

const state = (
    status: TryOnRequest['status'],
    extra: Partial<TryOnRequest> = {},
): TryOnRequest => ({
    id: '11111111-1111-4111-8111-111111111111',
    status,
    error: null,
    result_url: null,
    provider: 'mock',
    has_photo: true,
    suggested_size: '48',
    cost_usd: null,
    look_id: 5,
    fabric_id: 3,
    created_at: null,
    ...extra,
});

const payload = (
    extra: Partial<CreateTryOnPayload> = {},
): CreateTryOnPayload => ({
    photo: new File(['x'], 'me.jpg', { type: 'image/jpeg' }),
    sourceRequestId: null,
    heightCm: 178,
    waistCm: 84,
    size: '48',
    lookId: 5,
    fabricId: 3,
    quality: 'medium',
    consent: true,
    ...extra,
});

function setup() {
    const scope = effectScope();
    const api = scope.run(() => useTryOnRequest())!;
    return { api, scope };
}

describe('useTryOnRequest', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        request.mockReset();
    });
    afterEach(() => vi.useRealTimers());

    it('submits, then polls until done', async () => {
        request
            .mockResolvedValueOnce(state('queued'))
            .mockResolvedValueOnce(state('running'))
            .mockResolvedValueOnce(state('done', { result_url: '/r.png' }));
        const { api, scope } = setup();

        expect(await api.submit(payload())).toBe(true);
        expect(api.current.value?.status).toBe('queued');

        await vi.advanceTimersByTimeAsync(POLL_INTERVAL_MS * 3);
        expect(api.current.value?.status).toBe('done');
        expect(request).toHaveBeenCalledTimes(3);
        scope.stop();
    });

    it('sends the API key only as a header, never in the form body', async () => {
        request.mockResolvedValueOnce(state('queued'));
        const { api, scope } = setup();

        await api.submit(payload(), 'sk-test-SECRET-1234567890abc');

        const [, options] = request.mock.calls[0];
        expect(options?.headers).toEqual({
            'X-OpenAI-Key': 'sk-test-SECRET-1234567890abc',
        });
        const body = options?.body as FormData;
        const textFields = [...body.values()].filter(
            (v): v is string => typeof v === 'string',
        );
        expect(textFields.join('|')).not.toContain('SECRET');
        scope.stop();
    });

    it('ignores a second submit while the first is in flight (no double charge)', async () => {
        request.mockImplementationOnce(() => new Promise(() => {}));
        const { api, scope } = setup();

        void api.submit(payload());
        expect(await api.submit(payload())).toBe(false);
        expect(request).toHaveBeenCalledTimes(1);
        scope.stop();
    });

    it('maps 422 to field errors and 429 to a general message', async () => {
        request.mockRejectedValueOnce(
            new HttpError(422, { errors: { photo: ['Upload a photo.'] } }),
        );
        const { api, scope } = setup();

        expect(await api.submit(payload())).toBe(false);
        expect(api.errors.value.photo).toEqual(['Upload a photo.']);

        request.mockRejectedValueOnce(new HttpError(429, null));
        await api.submit(payload());
        expect(api.generalError.value).toBe('tryOn.errors.tooMany');
        scope.stop();
    });

    it('remove() stops polling and calls DELETE', async () => {
        request.mockResolvedValueOnce(state('queued')).mockResolvedValue(null);
        const { api, scope } = setup();
        await api.submit(payload());

        await api.remove();
        await vi.advanceTimersByTimeAsync(POLL_INTERVAL_MS * 3);

        expect(request).toHaveBeenCalledTimes(2); // POST + DELETE, no polling
        expect(request.mock.calls[1][1]).toMatchObject({ method: 'DELETE' });
        expect(api.current.value).toBeNull();
        scope.stop();
    });

    it('stops polling when the component scope is disposed', async () => {
        request.mockResolvedValue(state('running'));
        const { api, scope } = setup();
        await api.submit(payload());

        scope.stop();
        await vi.advanceTimersByTimeAsync(POLL_INTERVAL_MS * 3);

        expect(request).toHaveBeenCalledTimes(1);
    });
});
