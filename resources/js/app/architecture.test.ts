/// <reference types="node" />
/**
 * FSD qoidalari avtomatik tekshiruvi (docs/ARCHITECTURE.md → Frontend):
 *  1. Qatlamlar faqat pastga import qiladi: app → pages → widgets → features → entities → shared.
 *  2. Boshqa slice'ga faqat uning public API'si orqali: '@/features/select-look', ichki fayl emas.
 *  3. Bir qatlamdagi slice'lar bir-birini import qilmaydi (shared va app bundan mustasno).
 * Wayfinder generatsiya qilgan papkalar (actions, routes, wayfinder) shared darajasida hisoblanadi.
 */
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vite-plus/test';

const ROOT = fileURLToPath(new URL('..', import.meta.url));
const RANK: Record<string, number> = {
    shared: 0,
    actions: 0,
    routes: 0,
    wayfinder: 0,
    entities: 1,
    features: 2,
    widgets: 3,
    pages: 4,
    app: 5,
};
const SLICED = new Set(['entities', 'features', 'widgets', 'pages']);
const GENERATED = new Set(['actions', 'routes', 'wayfinder']);

function walk(dir: string): string[] {
    return readdirSync(dir).flatMap((name) => {
        const path = join(dir, name);
        if (statSync(path).isDirectory())
            return GENERATED.has(name) && dir === ROOT ? [] : walk(path);
        return /\.(ts|vue)$/.test(name) && !name.endsWith('.d.ts')
            ? [path]
            : [];
    });
}

type Location = { layer: string; slice: string | null };

function locate(pathFromRoot: string): Location {
    const [layer, slice] = pathFromRoot.split('/');
    return { layer, slice: SLICED.has(layer) ? slice : null };
}

function importsOf(file: string): string[] {
    const code = readFileSync(file, 'utf8');
    return [
        ...code.matchAll(/(?:import|export)\s[^;]*?from\s+['"]([^'"]+)['"]/g),
    ].map((m) => m[1]);
}

const violations: string[] = [];

for (const file of walk(ROOT)) {
    const fromRel = relative(ROOT, file);
    const from = locate(fromRel);

    for (const spec of importsOf(file)) {
        let target: string;
        if (spec.startsWith('@/')) target = spec.slice(2);
        else if (spec.startsWith('.'))
            target = relative(ROOT, resolve(dirname(file), spec));
        else continue;

        const to = locate(target);
        if (!(to.layer in RANK)) continue;

        if (RANK[to.layer] > RANK[from.layer]) {
            violations.push(
                `${fromRel}: yuqori qatlam import qilingan → ${spec}`,
            );
        } else if (
            to.layer === from.layer &&
            SLICED.has(to.layer) &&
            to.slice !== from.slice
        ) {
            violations.push(
                `${fromRel}: bir qatlamdagi boshqa slice → ${spec}`,
            );
        } else if (
            SLICED.has(to.layer) &&
            to.slice !== from.slice &&
            target !== `${to.layer}/${to.slice}`
        ) {
            violations.push(
                `${fromRel}: public API chetlab o'tilgan → ${spec}`,
            );
        }
    }
}

describe('FSD architecture', () => {
    it('respects layer order, slice isolation and public APIs', () => {
        expect(violations).toEqual([]);
    });
});
