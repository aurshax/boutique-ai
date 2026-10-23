import { describe, expect, it } from 'vite-plus/test';
import { acceptAttribute, checkUpload, describeAllowed } from './upload';

const limits = {
    allowed_mimes: ['image/jpeg', 'image/png', 'image/webp'],
    max_kb: 10240,
};

describe('upload', () => {
    it('accepts allowed image within limit', () => {
        expect(
            checkUpload({ type: 'image/jpeg', size: 2_000_000 }, limits),
        ).toEqual({ ok: true });
    });

    it('rejects wrong type with human message from limits', () => {
        const result = checkUpload({ type: 'image/gif', size: 100 }, limits);
        expect(result).toMatchObject({
            ok: false,
            reason: 'type',
            message: 'Faqat JPEG, PNG yoki WebP rasm.',
        });
    });

    it('rejects files over max_kb', () => {
        const result = checkUpload(
            { type: 'image/png', size: 10240 * 1024 + 1 },
            limits,
        );
        expect(result).toMatchObject({
            ok: false,
            reason: 'size',
            message: 'Rasm 10 MB dan oshmasligi kerak.',
        });
    });

    it('builds accept attribute and description from backend limits', () => {
        expect(acceptAttribute(limits)).toBe('image/jpeg,image/png,image/webp');
        expect(
            describeAllowed({ allowed_mimes: ['image/jpeg'], max_kb: 1 }),
        ).toBe('JPEG');
    });
});
