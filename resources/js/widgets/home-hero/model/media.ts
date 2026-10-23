// Hero fon videolari (public/media/hero, manbalar — public/media/CREDITS.md).
// Desktop — gorizontal 1080p (webm + mp4 fallback), mobil — vertikal.

export type HeroMedia = {
    poster: string;
    sources: { src: string; type: 'video/webm' | 'video/mp4' }[];
};

export const HERO_MEDIA: Record<'desktop' | 'mobile', HeroMedia> = {
    desktop: {
        poster: '/media/hero/hero-fitting-poster.webp',
        sources: [
            { src: '/media/hero/hero-fitting-1080.webm', type: 'video/webm' },
            { src: '/media/hero/hero-fitting-1080.mp4', type: 'video/mp4' },
        ],
    },
    mobile: {
        poster: '/media/hero/hero-chalk-mobile-poster.webp',
        sources: [
            { src: '/media/hero/hero-chalk-mobile.mp4', type: 'video/mp4' },
        ],
    },
};

/** Lenta belgilari (sm) — hero pastidagi o'lchov tasmasi. */
export const TAPE_MARKS = [160, 166, 172, 178, 184, 190, 196];
