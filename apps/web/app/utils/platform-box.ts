import type { PlatformMarkName } from './platform-marks';

/**
 * The game case a cover sits in: the band and frame colors of its platform
 * family and what the band shows (official marks, or text where a platform
 * has no box of its own). One family takes the look of its newest platform;
 * several families share the neutral case and list one entry per family.
 */
export type BoxTone = 'ps5' | 'ps4' | 'xbox' | 'nintendo' | 'pc' | 'multi';

export type BandItem =
    | { kind: 'mark'; mark: PlatformMarkName; suffix?: string }
    | { kind: 'text'; text: string };

export interface PlatformBox {
    tone: BoxTone;
    items: BandItem[];
}

interface PlatformRef {
    slug: string;
    name: string;
}

type Family = 'playstation' | 'xbox' | 'nintendo' | 'pc' | 'mobile' | 'other';

const FAMILY: Record<string, Family> = {
    ps5: 'playstation',
    ps4: 'playstation',
    'xbox-series': 'xbox',
    'xbox-one': 'xbox',
    'switch-2': 'nintendo',
    switch: 'nintendo',
    windows: 'pc',
    macos: 'pc',
    android: 'mobile',
    ios: 'mobile',
};

// Newest first: the order the band lists them in.
const ORDER = Object.keys(FAMILY);

function familyItems(family: Family, platforms: PlatformRef[]): BandItem[] {
    const has = (slug: string) => platforms.some((p) => p.slug === slug);

    switch (family) {
        case 'playstation':
        case 'nintendo':
            return platforms.map((p) => ({
                kind: 'mark',
                mark: p.slug as PlatformMarkName,
            }));
        case 'xbox': {
            const suffix = [
                has('xbox-series') ? 'Series X|S' : null,
                has('xbox-one') ? 'One' : null,
            ]
                .filter(Boolean)
                .join(' · ');
            return [{ kind: 'mark', mark: 'xbox', suffix }];
        }
        case 'pc':
            return [
                {
                    kind: 'text',
                    text: [
                        has('windows') ? 'PC' : null,
                        has('macos') ? 'Mac' : null,
                    ]
                        .filter(Boolean)
                        .join(' · '),
                },
            ];
        default:
            return [
                {
                    kind: 'text',
                    text: platforms.map((p) => p.name).join(' · '),
                },
            ];
    }
}

export function platformBox(platforms: PlatformRef[]): PlatformBox {
    const sorted = [...platforms].sort(
        (a, b) =>
            (ORDER.indexOf(a.slug) + 1 || 99) -
            (ORDER.indexOf(b.slug) + 1 || 99),
    );
    const families = new Map<Family, PlatformRef[]>();
    for (const platform of sorted) {
        const family = FAMILY[platform.slug] ?? 'other';
        families.set(family, [...(families.get(family) ?? []), platform]);
    }

    const items = [...families].flatMap(([family, members]) =>
        familyItems(family, members),
    );

    if (families.size !== 1) return { tone: 'multi', items };

    const [family] = families.keys();
    const tone: Record<Family, BoxTone> = {
        playstation: sorted.some((p) => p.slug === 'ps5') ? 'ps5' : 'ps4',
        xbox: 'xbox',
        nintendo: 'nintendo',
        pc: 'pc',
        mobile: 'multi',
        other: 'multi',
    };
    return { tone: tone[family!], items };
}
