import { describe, expect, it } from 'vite-plus/test';
import { toFormData } from './tryOnApi';
import type { CreateTryOnPayload } from '../model/types';

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

describe('toFormData', () => {
    it('sends the photo and all measurements', () => {
        const form = toFormData(payload());
        expect(form.get('photo')).toBeInstanceOf(File);
        expect(form.get('height_cm')).toBe('178');
        expect(form.get('waist_cm')).toBe('84');
        expect(form.get('size')).toBe('48');
        expect(form.get('look_id')).toBe('5');
        expect(form.get('fabric_id')).toBe('3');
        expect(form.get('consent')).toBe('1');
    });

    it('reuses the previous request instead of re-uploading the photo', () => {
        const form = toFormData(payload({ sourceRequestId: 'abc' }));
        expect(form.get('source_request_id')).toBe('abc');
        expect(form.has('photo')).toBe(false);
    });

    it('omits optional empty fields', () => {
        const form = toFormData(
            payload({ waistCm: null, size: null, fabricId: null }),
        );
        expect(form.has('waist_cm')).toBe(false);
        expect(form.has('size')).toBe(false);
        expect(form.has('fabric_id')).toBe(false);
    });
});
