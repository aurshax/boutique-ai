import { describe, expect, it } from 'vite-plus/test';
import { isValidApiKey, maskApiKey } from './apiKey';

describe('apiKey', () => {
    it('accepts sk- keys and trims whitespace', () => {
        expect(isValidApiKey('sk-proj-ABCdef1234567890_xyz-QWE')).toBe(true);
        expect(isValidApiKey('  sk-ABCdef1234567890abcdefgh  ')).toBe(true);
    });

    it('rejects short, foreign or unsafe keys', () => {
        expect(isValidApiKey('sk-short')).toBe(false);
        expect(isValidApiKey('pk-ABCdef1234567890abcdefgh')).toBe(false);
        expect(isValidApiKey('sk-ABCdef1234567890abcdefgh<script>')).toBe(
            false,
        );
        expect(isValidApiKey('')).toBe(false);
    });

    it('masks all but last four characters', () => {
        expect(maskApiKey('sk-proj-ABCdef1234567890a91f')).toBe('sk-…a91f');
        expect(maskApiKey('sk-1')).toBe('');
    });
});
