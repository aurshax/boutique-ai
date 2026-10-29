<script setup lang="ts">
// The Fitzaro mark: a hanger with two sparkles, the same drawing as the WordPress plugin's
// .wordpress-org/icon.svg. Without the tile, the hanger takes the text colour (currentColor) and the
// sparkles keep the brand coral, as in the plugin banner. With the tile it is the app icon (favicon look).
withDefaults(defineProps<{ tile?: boolean }>(), { tile: false });
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 256 256"
        aria-hidden="true"
        focusable="false"
    >
        <rect v-if="tile" width="256" height="256" rx="56" fill="#16171d" />
        <g
            fill="none"
            :stroke="tile ? '#fff' : 'currentColor'"
            stroke-width="13"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M111 78a17 17 0 1 1 17 17c-4 0-7 3-7 7v8" />
            <path
                d="M121 110 42 166c-8 6-4 18 6 18h160c10 0 14-12 6-18l-79-56c-4-3-10-3-14 0z"
            />
        </g>
        <g fill="#ff6a3d">
            <path
                d="M196 34c3 18 10 25 28 28-18 3-25 10-28 28-3-18-10-25-28-28 18-3 25-10 28-28z"
            />
            <path
                d="M162 88c1.6 8 4.6 11 12.5 12.5-7.9 1.5-10.9 4.5-12.5 12.5-1.6-8-4.6-11-12.5-12.5 7.9-1.5 10.9-4.5 12.5-12.5z"
            />
        </g>
    </svg>
</template>
