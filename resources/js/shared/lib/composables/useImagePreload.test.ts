import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { effectScope, nextTick, ref } from 'vue';
import { imageRatio, useImagePreload } from './useImagePreload';

/** Minimal Image stand-in: the test decides when (and how) each image finishes loading. */
class FakeImage {
    static created: FakeImage[] = [];
    onload: (() => void) | null = null;
    onerror: (() => void) | null = null;
    naturalWidth = 0;
    naturalHeight = 0;
    src = '';
    constructor() {
        FakeImage.created.push(this);
    }
    load(width: number, height: number): void {
        this.naturalWidth = width;
        this.naturalHeight = height;
        this.onload?.();
    }
    fail(): void {
        this.onerror?.();
    }
}

describe('imageRatio', () => {
    it('divides width by height', () => {
        expect(imageRatio(1024, 1536)).toBeCloseTo(2 / 3);
    });

    it('returns null for an image without size', () => {
        expect(imageRatio(0, 0)).toBeNull();
        expect(imageRatio(100, 0)).toBeNull();
    });
});

describe('useImagePreload', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        FakeImage.created = [];
    });

    function setup(initial: string | null) {
        vi.stubGlobal('Image', FakeImage);
        const url = ref<string | null>(initial);
        const scope = effectScope();
        const result = scope.run(() => useImagePreload(url))!;
        return { url, scope, ...result };
    }

    it('is ready with the real ratio only after the image has loaded', () => {
        const { ready, ratio } = setup('/result.jpg');
        expect(ready.value).toBe(false);
        expect(FakeImage.created[0].src).toBe('/result.jpg');

        FakeImage.created[0].load(1024, 1536);

        expect(ready.value).toBe(true);
        expect(ratio.value).toBeCloseTo(2 / 3);
    });

    it('counts a failed load as ready, without a ratio', () => {
        const { ready, ratio } = setup('/broken.jpg');
        FakeImage.created[0].fail();
        expect(ready.value).toBe(true);
        expect(ratio.value).toBeNull();
    });

    it('ignores an older image that finishes after the URL changed', async () => {
        const { url, ready, ratio } = setup('/old.jpg');
        url.value = '/new.jpg';
        await nextTick();

        FakeImage.created[0].load(100, 100);
        expect(ready.value).toBe(false);

        FakeImage.created[1].load(300, 600);
        expect(ready.value).toBe(true);
        expect(ratio.value).toBe(0.5);
    });

    it('does nothing without a URL', () => {
        const { ready } = setup(null);
        expect(FakeImage.created).toHaveLength(0);
        expect(ready.value).toBe(false);
    });

    it('stops listening when the scope is disposed', () => {
        const { scope, ready } = setup('/result.jpg');
        scope.stop();
        FakeImage.created[0].load(100, 100);
        expect(ready.value).toBe(false);
    });
});
