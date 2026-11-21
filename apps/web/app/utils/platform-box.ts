import type { PlatformMarkName } from './platform-marks';

/**
 * The platform band on top of a cover: the color of its platform family and
 * what it shows (official marks, or text where there is no mark). One family
 * takes the look of its newest platform; several families share the neutral
 * band and list one entry per family.
 *
 * A PC or Mac game is not "PC": it is played where it is activated, so the
 * band shows that store (Steam, Epic Games…), and the PC side of an Xbox
 * Play Anywhere key is just Xbox. Programs (licenses) install natively, so
 * they show the operating systems.
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

export interface BandContext {
    type?: { slug: string } | null;
    activation_service?: { slug: string; name: string } | null;
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

// Activation services that are PC stores with an official mark.
const STORE_MARKS = [
    'steam',
    'epic-games',
    'ea',
    'gog',
    'ubisoft',
    'battle-net',
] as const satisfies readonly PlatformMarkName[];

const OS_NAMES: Record<string, string> = {
    windows: 'Windows',
    macos: 'macOS',
};

function names(platforms: PlatformRef[]): string {
    return platforms.map((p) => OS_NAMES[p.slug] ?? p.name).join(' · ');
}

/** What the PC family shows, or nothing when Xbox already covers it. */
function pcItems(
    platforms: PlatformRef[],
    context: BandContext,
    withXbox: boolean,
): BandItem[] {
    const service = context.activation_service;

    if (context.type?.slug === 'license' || !service) {
        return [{ kind: 'text', text: names(platforms) }];
    }
    if (service.slug === 'xbox' && withXbox) return [];

    const mark = STORE_MARKS.find((slug) => slug === service.slug);
    return mark
        ? [{ kind: 'mark', mark, suffix: service.name }]
        : [{ kind: 'text', text: service.name }];
}

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
        default:
            return [{ kind: 'text', text: names(platforms) }];
    }
}

export function platformBox(
    platforms: PlatformRef[],
    context: BandContext = {},
): PlatformBox {
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

    // Each family that shows something, with what it shows.
    const shown = [...families]
        .map(([family, members]) => ({
            family,
            items:
                family === 'pc'
                    ? pcItems(members, context, families.has('xbox'))
                    : familyItems(family, members),
        }))
        .filter((entry) => entry.items.length > 0);

    const items = shown.flatMap((entry) => entry.items);
    if (shown.length !== 1) return { tone: 'multi', items };

    const tone: Record<Family, BoxTone> = {
        playstation: families.get('playstation')?.some((p) => p.slug === 'ps5')
            ? 'ps5'
            : 'ps4',
        xbox: 'xbox',
        nintendo: 'nintendo',
        pc: 'pc',
        mobile: 'multi',
        other: 'multi',
    };
    return { tone: tone[shown[0]!.family], items };
}
