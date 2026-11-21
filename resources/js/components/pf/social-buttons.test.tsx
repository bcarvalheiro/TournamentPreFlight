import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { SocialButtons } from '@/components/pf/social-buttons';

describe('SocialButtons', () => {
    it('links each provider to its sign-in redirect with a clear label', () => {
        const markup = renderToStaticMarkup(
            <SocialButtons providers={['google', 'discord', 'x']} />,
        );

        expect(markup).toMatch(
            /<a[^>]*href="\/auth\/google\/redirect"[^>]*>.*Continue with Google<\/a>/s,
        );
        expect(markup).toMatch(
            /<a[^>]*href="\/auth\/discord\/redirect"[^>]*>.*Continue with Discord<\/a>/s,
        );
        expect(markup).toMatch(
            /<a[^>]*href="\/auth\/x\/redirect"[^>]*>.*Continue with X<\/a>/s,
        );
    });

    it('shows nothing when no provider is configured', () => {
        expect(renderToStaticMarkup(<SocialButtons providers={[]} />)).toBe('');
    });
});
