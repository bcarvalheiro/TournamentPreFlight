import { Copy, Navigation } from 'lucide-react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useClipboard } from '@/hooks/use-clipboard';

type Shop = {
    shopName: string;
    address: string | null;
    district: string | null;
    city: string;
    latitude: number | null;
    longitude: number | null;
};

type EventShopProps = {
    shop: Shop;
};

/** "Rua das Flores 12, Lisboa": what players copy, and what directions search for without coordinates. */
function addressLine(shop: Shop): string {
    return [shop.address, shop.city].filter(Boolean).join(', ');
}

/**
 * Where each app opens directions to the shop (decisions.md, 25 Nov):
 * the coordinates when we have them, else the address text. Formats from
 * Google "Maps URLs", Waze "Deep links" and Apple "Adopting unified Maps URLs".
 */
export function directionLinks(shop: Shop): { label: string; href: string }[] {
    const coordinates =
        shop.latitude !== null && shop.longitude !== null
            ? encodeURIComponent(`${shop.latitude},${shop.longitude}`)
            : null;
    const destination = coordinates ?? encodeURIComponent(addressLine(shop));

    return [
        {
            label: 'Google Maps',
            href: `https://www.google.com/maps/dir/?api=1&destination=${destination}`,
        },
        {
            label: 'Waze',
            href: coordinates
                ? `https://waze.com/ul?ll=${coordinates}&navigate=yes`
                : `https://waze.com/ul?q=${destination}&navigate=yes`,
        },
        {
            label: 'Apple Maps',
            href: `https://maps.apple.com/directions?destination=${destination}`,
        },
    ];
}

/**
 * OpenStreetMap's own embed (the URL its Share → HTML panel builds): a small
 * box around the shop, west,south,east,north, with a pin at latitude,longitude.
 */
function embedUrl(latitude: number, longitude: number): string {
    const box = [
        longitude - 0.004,
        latitude - 0.0025,
        longitude + 0.004,
        latitude + 0.0025,
    ].map((value) => value.toFixed(5));
    const search = new URLSearchParams({
        bbox: box.join(','),
        layer: 'mapnik',
        marker: `${latitude},${longitude}`,
    });

    return `https://www.openstreetmap.org/export/embed.html?${search}`;
}

/**
 * The event page's shop column (design.md, Event page; "shop", never
 * "venue"): a map with a pin when the shop has coordinates, its name,
 * address and district, a Directions menu and Copy address.
 */
export function EventShop({ shop }: EventShopProps): ReactNode {
    const [, copy] = useClipboard();
    const address = addressLine(shop);
    const hasMap = shop.latitude !== null && shop.longitude !== null;

    async function copyAddress(): Promise<void> {
        if (await copy(`${shop.shopName}, ${address}`)) {
            toast('Address copied');
        } else {
            toast.error("Couldn't copy the address.");
        }
    }

    return (
        <section aria-labelledby="event-shop-title">
            <h2
                id="event-shop-title"
                className="mb-2 font-mono text-xs font-semibold tracking-widest text-muted uppercase"
            >
                Shop
            </h2>
            <div className="overflow-hidden rounded-card border border-line bg-bg">
                {/* OpenStreetMap's own footer (attribution, report, donate) stays: a
                    frame from another site cannot be styled, and the tile policy
                    forbids hiding the attribution. Nothing of ours sits on top. */}
                {hasMap && (
                    <iframe
                        title={`Map of ${shop.shopName}`}
                        src={embedUrl(shop.latitude!, shop.longitude!)}
                        loading="lazy"
                        className="block h-60 w-full border-0"
                    />
                )}
                <div className="flex flex-col gap-1 p-4">
                    <p className="text-lg font-bold text-ink">
                        {shop.shopName}
                    </p>
                    <p className="text-muted">{address}</p>
                    {shop.district && (
                        <p className="text-muted">{shop.district} district</p>
                    )}
                    <div className="mt-2 flex flex-wrap gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button>
                                    <Navigation aria-hidden="true" />
                                    Directions
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start">
                                {directionLinks(shop).map((link) => (
                                    <DropdownMenuItem key={link.label} asChild>
                                        <a
                                            href={link.href}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {link.label}
                                        </a>
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <Button variant="outline" onClick={copyAddress}>
                            <Copy aria-hidden="true" />
                            Copy address
                        </Button>
                        {hasMap && (
                            <Button variant="link" asChild>
                                <a
                                    href={`https://www.openstreetmap.org/?mlat=${shop.latitude}&mlon=${shop.longitude}#map=17/${shop.latitude}/${shop.longitude}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Open map
                                </a>
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
