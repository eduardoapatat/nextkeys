import { describe, expect, it } from 'vitest';
import { platformBox } from '../../app/utils/platform-box';

const p = (slug: string, name = slug) => ({ slug, name });

describe('platform box', () => {
    it('dresses a single platform as its retail case', () => {
        expect(platformBox([p('ps5')])).toEqual({
            tone: 'ps5',
            items: [{ kind: 'mark', mark: 'ps5' }],
        });
        expect(platformBox([p('switch')]).tone).toBe('nintendo');
    });

    it('takes the newest console of a family and lists both, newest first', () => {
        expect(platformBox([p('ps4'), p('ps5')])).toEqual({
            tone: 'ps5',
            items: [
                { kind: 'mark', mark: 'ps5' },
                { kind: 'mark', mark: 'ps4' },
            ],
        });
        expect(platformBox([p('xbox-one'), p('xbox-series')]).items).toEqual([
            { kind: 'mark', mark: 'xbox', suffix: 'Series X|S · One' },
        ]);
    });

    it('shows where a PC game is activated, not "PC"', () => {
        const steam = { slug: 'steam', name: 'Steam' };

        expect(
            platformBox([p('windows'), p('macos')], {
                type: { slug: 'digital_key' },
                activation_service: steam,
            }),
        ).toEqual({
            tone: 'pc',
            items: [{ kind: 'mark', mark: 'steam', suffix: 'Steam' }],
        });
        expect(
            platformBox([p('windows')], {
                activation_service: { slug: 'some-launcher', name: 'Launcher' },
            }).items,
        ).toEqual([{ kind: 'text', text: 'Launcher' }]);
    });

    it('treats the PC side of an Xbox key as Xbox', () => {
        expect(
            platformBox([p('windows'), p('xbox-series')], {
                activation_service: { slug: 'xbox', name: 'Xbox' },
            }),
        ).toEqual({
            tone: 'xbox',
            items: [{ kind: 'mark', mark: 'xbox', suffix: 'Series X|S' }],
        });
    });

    it('names the operating systems of a program', () => {
        expect(
            platformBox([p('windows'), p('macos')], {
                type: { slug: 'license' },
                activation_service: {
                    slug: 'microsoft-account',
                    name: 'Microsoft Account',
                },
            }),
        ).toEqual({
            tone: 'pc',
            items: [{ kind: 'text', text: 'Windows · macOS' }],
        });
    });

    it('uses the neutral band for several families or phones', () => {
        expect(
            platformBox([p('ps5'), p('windows')], {
                activation_service: { slug: 'steam', name: 'Steam' },
            }),
        ).toEqual({
            tone: 'multi',
            items: [
                { kind: 'mark', mark: 'ps5' },
                { kind: 'mark', mark: 'steam', suffix: 'Steam' },
            ],
        });
        expect(platformBox([p('android', 'Android'), p('ios', 'iOS')])).toEqual(
            {
                tone: 'multi',
                items: [{ kind: 'text', text: 'Android · iOS' }],
            },
        );
        expect(platformBox([]).tone).toBe('multi');
    });
});
