import { describe, expect, it } from 'vite-plus/test';
import { HttpError } from './api-error';
import { readXsrfToken } from './http';

describe('readXsrfToken', () => {
    it('reads and decodes the Laravel XSRF cookie', () => {
        expect(readXsrfToken('a=1; XSRF-TOKEN=abc%3D%3D; b=2')).toBe('abc==');
    });

    it('returns null when the cookie is missing', () => {
        expect(readXsrfToken('a=1')).toBeNull();
    });
});

describe('HttpError', () => {
    it('exposes Laravel validation errors for 422', () => {
        const error = new HttpError(422, {
            message: 'x',
            errors: { photo: ['Rasm yuklang.'] },
        });
        expect(error.isValidation).toBe(true);
        expect(error.validationErrors).toEqual({ photo: ['Rasm yuklang.'] });
    });

    it('returns empty errors for other statuses or empty body', () => {
        expect(new HttpError(500, null).validationErrors).toEqual({});
        expect(new HttpError(500, null).isValidation).toBe(false);
    });
});
