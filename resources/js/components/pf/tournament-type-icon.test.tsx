import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { TournamentTypeIcon } from '@/components/pf/tournament-type-icon';
import type { TournamentType } from '@/lib/tournament-types';

/*
 * The four ball SVGs exactly as written in docs/design.md ("Type icons").
 * "ballTop" is the shared clip id; each rendered icon must use its own id instead.
 */
const designSvg: Record<TournamentType, string> = {
    casual: '<path d="M1 12a11 11 0 0 1 22 0z" fill="#E3350D"/><path d="M1 12a11 11 0 0 0 22 0z" fill="#fff"/><path d="M1 12h22" stroke="#1B1B1B" stroke-width="2"/><circle cx="12" cy="12" r="11" fill="none" stroke="#1B1B1B" stroke-width="1.5"/><circle cx="12" cy="12" r="3.3" fill="#fff" stroke="#1B1B1B" stroke-width="1.6"/>',
    prerelease:
        '<path d="M1 12a11 11 0 0 1 22 0z" fill="#2F6BD8"/><g clip-path="url(#ballTop)" fill="#E3350D"><ellipse cx="5.6" cy="6.4" rx="4" ry="1.9" transform="rotate(-38 5.6 6.4)"/><ellipse cx="18.4" cy="6.4" rx="4" ry="1.9" transform="rotate(38 18.4 6.4)"/></g><path d="M1 12a11 11 0 0 0 22 0z" fill="#fff"/><path d="M1 12h22" stroke="#1B1B1B" stroke-width="2"/><circle cx="12" cy="12" r="11" fill="none" stroke="#1B1B1B" stroke-width="1.5"/><circle cx="12" cy="12" r="3.3" fill="#fff" stroke="#1B1B1B" stroke-width="1.6"/>',
    challenge:
        '<path d="M1 12a11 11 0 0 1 22 0z" fill="#222"/><g clip-path="url(#ballTop)" fill="#F5C518"><rect x="3.2" y="0" width="3.4" height="12"/><rect x="17.4" y="0" width="3.4" height="12"/><rect x="3.2" y="6.2" width="17.6" height="2"/></g><path d="M1 12a11 11 0 0 0 22 0z" fill="#fff"/><path d="M1 12h22" stroke="#1B1B1B" stroke-width="2"/><circle cx="12" cy="12" r="11" fill="none" stroke="#1B1B1B" stroke-width="1.5"/><circle cx="12" cy="12" r="3.3" fill="#fff" stroke="#1B1B1B" stroke-width="1.6"/>',
    cup: '<path d="M1 12a11 11 0 0 1 22 0z" fill="#7B3FA0"/><circle cx="6.3" cy="7.6" r="2.1" fill="#EC5FA6"/><circle cx="17.7" cy="7.6" r="2.1" fill="#EC5FA6"/><path d="M9.2 9V4.4l2.8 2.9 2.8-2.9V9" fill="none" stroke="#fff" stroke-width="1.4" stroke-linejoin="round"/><path d="M1 12a11 11 0 0 0 22 0z" fill="#fff"/><path d="M1 12h22" stroke="#1B1B1B" stroke-width="2"/><circle cx="12" cy="12" r="11" fill="none" stroke="#1B1B1B" stroke-width="1.5"/><circle cx="12" cy="12" r="3.3" fill="#fff" stroke="#1B1B1B" stroke-width="1.6"/>',
};

/** React writes `<path ...></path>`; design.md writes `<path .../>`. Both mean the same in SVG. */
function selfClose(markup: string): string {
    return markup.replace(/<(\w+)([^<>]*)><\/\1>/g, '<$1$2/>');
}

function clipIds(markup: string): string[] {
    return [...markup.matchAll(/<clipPath id="([^"]+)"/g)].map(
        (match) => match[1],
    );
}

function clipReferences(markup: string): string[] {
    return [...markup.matchAll(/clip-path="url\(#([^)]+)\)"/g)].map(
        (match) => match[1],
    );
}

describe('TournamentTypeIcon', () => {
    it.each(Object.keys(designSvg) as TournamentType[])(
        'draws the %s ball exactly as design.md, hidden from screen readers',
        (type) => {
            const markup = selfClose(
                renderToStaticMarkup(<TournamentTypeIcon type={type} />),
            );
            const [clipId] = clipIds(markup);

            expect(markup).toMatch(/^<svg[^>]* viewBox="0 0 24 24"/);
            expect(markup).toMatch(/^<svg[^>]* aria-hidden="true"/);
            expect(markup).toContain(
                designSvg[type].replaceAll('ballTop', clipId),
            );
        },
    );

    it('gives two balls on one page different clip ids, each pointing to its own', () => {
        const markup = renderToStaticMarkup(
            <>
                <TournamentTypeIcon type="prerelease" />
                <TournamentTypeIcon type="prerelease" />
            </>,
        );

        const ids = clipIds(markup);

        expect(ids).toHaveLength(2);
        expect(ids[0]).not.toBe(ids[1]);
        expect(clipReferences(markup)).toEqual(ids);
    });
});
