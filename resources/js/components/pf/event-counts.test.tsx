import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { EventCounts } from '@/components/pf/event-counts';

function render(goingCount: number, confirmedCount: number): string {
    // Text only: the count and its words sit in separate elements.
    return renderToStaticMarkup(
        <EventCounts goingCount={goingCount} confirmedCount={confirmedCount} />,
    )
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ');
}

describe('EventCounts', () => {
    it('shows the going and confirmed counts (M09 AC1)', () => {
        expect(render(36, 8)).toContain('36 trainers going (8 confirmed)');
    });

    it('leaves out "(0 confirmed)" until confirmation exists (M12)', () => {
        const text = render(12, 0);

        expect(text).toContain('12 trainers going');
        expect(text).not.toContain('confirmed');
    });

    it('says "trainer" for one', () => {
        expect(render(1, 0)).toContain('1 trainer going');
    });

    it('reminds that it is a headcount and shows no names', () => {
        expect(render(3, 0)).toContain(
            'Headcount, not registration · Names never shown',
        );
    });
});
