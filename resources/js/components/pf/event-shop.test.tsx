import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { directionLinks, EventShop } from '@/components/pf/event-shop';

const carta = {
    shopName: 'Carta Alta',
    address: 'Rua das Flores 12',
    district: 'Lisboa',
    city: 'Lisboa',
    latitude: 38.7578,
    longitude: -9.25036,
};

const noCoordinates = { ...carta, latitude: null, longitude: null };

/** HTML-escaped hrefs, as they appear in the markup. */
const html = (shop: typeof carta | typeof noCoordinates) =>
    renderToStaticMarkup(<EventShop shop={shop} />);

describe('directionLinks', () => {
    it("sends every app to the shop's coordinates", () => {
        expect(directionLinks(carta)).toEqual([
            {
                label: 'Google Maps',
                href: 'https://www.google.com/maps/dir/?api=1&destination=38.7578%2C-9.25036',
            },
            {
                label: 'Waze',
                href: 'https://waze.com/ul?ll=38.7578%2C-9.25036&navigate=yes',
            },
            {
                label: 'Apple Maps',
                href: 'https://maps.apple.com/directions?destination=38.7578%2C-9.25036',
            },
        ]);
    });

    it('falls back to the address when the shop has no coordinates', () => {
        const address = 'Rua%20das%20Flores%2012%2C%20Lisboa';

        expect(directionLinks(noCoordinates).map((link) => link.href)).toEqual([
            `https://www.google.com/maps/dir/?api=1&destination=${address}`,
            `https://waze.com/ul?q=${address}&navigate=yes`,
            `https://maps.apple.com/directions?destination=${address}`,
        ]);
    });
});

describe('EventShop', () => {
    it('shows the shop, its address and district', () => {
        const markup = html(carta);

        expect(markup).toContain('Carta Alta');
        expect(markup).toContain('Rua das Flores 12, Lisboa');
        expect(markup).toContain('Lisboa district');
    });

    it('embeds a lazy, titled map with a pin when the shop has coordinates', () => {
        const markup = html(carta);

        expect(markup).toMatch(/<iframe[^>]*title="Map of Carta Alta"/);
        expect(markup).toMatch(/<iframe[^>]*loading="lazy"/);
        // Out of the Tab order: its own controls took 7 stops with no visible focus;
        // Directions, Copy address and Open map serve the keyboard (review).
        expect(markup).toMatch(/<iframe[^>]*tabindex="-1"/i);
        expect(markup).toContain(
            'https://www.openstreetmap.org/export/embed.html?bbox=',
        );
        expect(markup).toContain('marker=38.7578%2C-9.25036');
        expect(markup).toContain(
            'href="https://www.openstreetmap.org/?mlat=38.7578&amp;mlon=-9.25036#map=17/38.7578/-9.25036"',
        );
    });

    it('shows no map without coordinates', () => {
        const markup = html(noCoordinates);

        expect(markup).not.toContain('<iframe');
        expect(markup).not.toContain('Open map');
    });

    it('offers directions and copying the address', () => {
        const markup = html(carta);

        expect(markup).toMatch(/<button[^>]*>.*Directions/s);
        expect(markup).toMatch(/<button[^>]*>.*Copy address/s);
    });
});
