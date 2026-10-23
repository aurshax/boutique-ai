// Hero background videos (public/media/hero, sources in public/media/CREDITS.md).
// Desktop: landscape 1080p (webm + mp4 fallback); mobile: portrait.

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

/** Tape marks (cm) for the measuring tape at the bottom of the hero. */
export const TAPE_MARKS = [160, 166, 172, 178, 184, 190, 196];
