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
        expect(platformBox([p('windows')])).toEqual({
            tone: 'pc',
            items: [{ kind: 'text', text: 'PC' }],
        });
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

    it('uses the neutral case for several families or phones', () => {
        expect(platformBox([p('windows'), p('xbox-series')])).toEqual({
            tone: 'multi',
            items: [
                { kind: 'mark', mark: 'xbox', suffix: 'Series X|S' },
                { kind: 'text', text: 'PC' },
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
