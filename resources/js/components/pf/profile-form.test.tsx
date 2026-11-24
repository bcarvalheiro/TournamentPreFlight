import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { ProfileForm } from '@/components/pf/profile-form';
import type { OwnProfile } from '@/types';

const form = { action: '/profiles/7', method: 'post' as const };

function render(profile?: OwnProfile): string {
    return renderToStaticMarkup(
        <ProfileForm
            form={form}
            consentFromYear={2013}
            profile={profile}
            submitLabel="Save trainer"
        />,
    );
}

const adult: OwnProfile = {
    id: 7,
    name: 'Ana Silva',
    popId: '1111111',
    birthYear: 1985,
    isChild: false,
};
const child: OwnProfile = {
    id: 8,
    name: 'Rui Silva',
    popId: '2222222',
    birthYear: 2016,
    isChild: true,
};

describe('ProfileForm', () => {
    it('starts empty, with no consent box, for a new trainer', () => {
        const markup = render();

        expect(markup).toMatch(/<form[^>]*action="\/profiles\/7"/);
        expect(markup).toMatch(
            /<input[^>]*name="name"[^>]*value=""|<input[^>]*value=""[^>]*name="name"/,
        );
        expect(markup).not.toContain('name="consent"');
        expect(markup).toContain('Save trainer');
    });

    it('fills in a trainer being edited', () => {
        const markup = render(adult);

        expect(markup).toContain('value="Ana Silva"');
        expect(markup).toContain('value="1111111"');
        expect(markup).toContain('value="1985"');
        expect(markup).not.toContain('parent or guardian');
    });

    // An existing child keeps the consent already given (decisions.md, 24 Nov).
    it('does not ask an existing child for consent again', () => {
        expect(render(child)).not.toContain('parent or guardian');
    });
});
