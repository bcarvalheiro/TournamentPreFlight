import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { TournamentTypeBadge } from '@/components/pf/tournament-type-badge';

/*
 * The ball icon is decoration (aria-hidden), so the type name must always be
 * visible text next to it: that is what screen readers and colour-blind players read.
 */
describe('TournamentTypeBadge', () => {
    it.each([
        ['friendly', 'Friendly'],
        ['league', 'League'],
        ['challenge', 'Challenge'],
        ['cup', 'Cup'],
    ] as const)(
        'shows the %s icon with the visible name "%s"',
        (type, name) => {
            const markup = renderToStaticMarkup(
                <TournamentTypeBadge type={type} />,
            );
            const textOutsideIcon = markup
                .replace(/<svg[\s\S]*?<\/svg>/, '')
                .replace(/<[^>]+>/g, '');

            expect(markup).toContain('aria-hidden="true"');
            expect(textOutsideIcon).toBe(name);
        },
    );
});
