import { Head } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import AppLogo from '@/components/pf/app-logo';
import { ResponsiveDialog } from '@/components/pf/responsive-dialog';
import { TournamentTypeBadge } from '@/components/pf/tournament-type-badge';
import { TournamentTypeIcon } from '@/components/pf/tournament-type-icon';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyContent, EmptyDescription } from '@/components/ui/empty';
import PublicLayout from '@/layouts/public-layout';
import {
    TOURNAMENT_TYPE_NAMES,
    TOURNAMENT_TYPES,
} from '@/lib/tournament-types';

/** A styleguide section: the app's Card with a title. */
function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    <h2>{title}</h2>
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">{children}</CardContent>
        </Card>
    );
}

/** A visible placeholder that shows where the band's hero slot renders. */
function HeroSlotSample() {
    return (
        <div className="mb-6 rounded-card border-[1.5px] border-dashed border-white/30 p-4 text-sm">
            Hero slot: pages fill it through Inertia layout props.
        </div>
    );
}

/**
 * Developer page (local only, see routes/web.php) showing every shared component.
 */
export default function Styleguide() {
    const [dialogOpen, setDialogOpen] = useState(false);

    return (
        <>
            <Head title="Styleguide" />

            <div className="space-y-6">
                <h1 className="font-display text-4xl font-extrabold uppercase">
                    Styleguide
                </h1>

                <Section title="App logo">
                    <div className="flex flex-wrap gap-4">
                        <span className="rounded-card bg-ink px-4 py-3 text-white">
                            <AppLogo />
                        </span>
                        <span className="rounded-card border border-line px-4 py-3 text-ink">
                            <AppLogo />
                        </span>
                    </div>
                </Section>

                <Section title="Tournament types">
                    <div className="flex flex-wrap gap-2">
                        {TOURNAMENT_TYPES.map((type) => (
                            <TournamentTypeBadge key={type} type={type} />
                        ))}
                    </div>
                    <div className="flex flex-wrap items-end gap-4">
                        {TOURNAMENT_TYPES.map((type) => (
                            <span
                                key={type}
                                className="flex flex-col items-center gap-1 text-sm text-muted"
                            >
                                <TournamentTypeIcon type={type} size={40} />
                                {TOURNAMENT_TYPE_NAMES[type]}
                            </span>
                        ))}
                    </div>
                    <p className="flex items-center gap-2 text-sm text-muted">
                        Common
                        {TOURNAMENT_TYPES.map((type) => (
                            <TournamentTypeIcon
                                key={type}
                                type={type}
                                size={20}
                            />
                        ))}
                        Rare
                    </p>
                </Section>

                <Section title="Buttons">
                    <div className="flex flex-wrap items-center gap-3">
                        <Button>I'm going</Button>
                        <Button variant="outline">Going ✓</Button>
                        <Button variant="link">Add a profile</Button>
                        <Button disabled>Disabled</Button>
                    </div>
                </Section>

                <Section title="Responsive dialog">
                    <p className="text-sm text-muted">
                        A bottom sheet below 768px, a centred dialog above.
                    </p>
                    <ResponsiveDialog
                        open={dialogOpen}
                        onOpenChange={setDialogOpen}
                        trigger={
                            <Button variant="outline" data-testid="open-dialog">
                                Open dialog
                            </Button>
                        }
                        title="Filters"
                        description="Pick what you want to see."
                        footer={
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => setDialogOpen(false)}
                                >
                                    Clear all
                                </Button>
                                <Button onClick={() => setDialogOpen(false)}>
                                    Show 12 events
                                </Button>
                            </>
                        }
                    >
                        <label className="flex flex-col gap-1 text-sm font-semibold">
                            Shop name
                            <input
                                type="text"
                                className="h-10 rounded-button border border-line px-3 font-normal"
                            />
                        </label>
                    </ResponsiveDialog>
                </Section>

                <Section title="Toast">
                    <Button
                        variant="outline"
                        onClick={() => toast('Saved. See you at the table!')}
                    >
                        Show a toast
                    </Button>
                </Section>

                <Section title="Empty state">
                    <Empty>
                        <EmptyDescription>
                            Nothing matches these filters.
                        </EmptyDescription>
                        <EmptyContent>
                            <Button>Clear filters</Button>
                        </EmptyContent>
                    </Empty>
                </Section>
            </div>
        </>
    );
}

/*
 * Static layout props as a tuple (Inertia docs, "Layouts > Static Props"):
 * PublicLayout with the hero slot filled. A tuple, not a bare props object,
 * because Inertia reads an object whose values are elements as named layouts.
 */
Styleguide.layout = [PublicLayout, { hero: <HeroSlotSample /> }];
