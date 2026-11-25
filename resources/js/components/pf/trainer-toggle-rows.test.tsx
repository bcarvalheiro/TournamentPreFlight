import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { TrainerToggleRows } from '@/components/pf/trainer-toggle-rows';

const trainers = [
    { id: 3, name: 'Ana' },
    { id: 4, name: 'Tiago' },
];

function render(goingIds: number[]): string {
    return renderToStaticMarkup(
        <TrainerToggleRows
            eventId={7}
            place="Carta Alta, Sun 29 Nov"
            trainers={trainers}
            goingIds={goingIds}
            reload={['event', 'own']}
        />,
    );
}

/** Each row's button, in order. */
function rows(html: string): string[] {
    return html.match(/<button[^>]*>.*?<\/button>/gs) ?? [];
}

describe('TrainerToggleRows', () => {
    it('shows one row per trainer, saying who is going', () => {
        const [ana, tiago] = rows(render([3]));

        expect(ana).toContain('Ana');
        expect(ana).toContain('Going ✓');
        expect(ana).toContain('aria-pressed="true"');
        expect(tiago).toContain('Tiago');
        expect(tiago).toContain('I&#x27;m going');
        expect(tiago).toContain('aria-pressed="false"');
    });

    it('offers to add a trainer', () => {
        expect(render([])).toMatch(
            /<a[^>]*href="\/profiles\/create"[^>]*>.*Add a trainer/s,
        );
    });
});
