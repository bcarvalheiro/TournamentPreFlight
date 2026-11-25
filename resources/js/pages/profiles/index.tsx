import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { ResponsiveDialog } from '@/components/pf/responsive-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyTitle,
} from '@/components/ui/empty';
import PublicLayout from '@/layouts/public-layout';
import { create, destroy, edit } from '@/routes/profiles';
import type { OwnProfile } from '@/types';

type Props = {
    /** The account's own players, oldest first. */
    profiles: OwnProfile[];
    /** One profile aged 13 or older and 3 children: nothing more can be added. */
    isFull: boolean;
    /** Where to write for a fourth child. */
    supportEmail: string;
};

/**
 * "Delete" on a trainer card: asks first (design.md "Sheet"), and only
 * "Delete trainer" removes them, with their "Going?" answers.
 */
function DeleteTrainer({ profile }: { profile: OwnProfile }) {
    const [open, setOpen] = useState(false);

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={setOpen}
            trigger={
                <button
                    type="button"
                    className="inline-flex min-h-10 cursor-pointer items-center px-1 text-sm font-semibold underline underline-offset-4"
                    aria-label={`Delete ${profile.name}`}
                >
                    Delete
                </button>
            }
            title={`Delete ${profile.name}?`}
            description="Their going answers go too. This can't be undone."
            footer={
                <>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Cancel
                    </Button>
                    <Form {...destroy.form(profile.id)} className="grid">
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                Delete trainer
                            </Button>
                        )}
                    </Form>
                </>
            }
        />
    );
}

/**
 * The account's trainers (M07): the holder's own profile and their
 * children's, or only a child's when the parent does not play. Seen only by
 * the account that owns them.
 */
export default function Profiles({ profiles, isFull, supportEmail }: Props) {
    return (
        <>
            <Head title="Trainers" />
            <div className="mx-auto max-w-xl py-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="font-display text-3xl font-extrabold uppercase">
                        Trainers
                    </h1>
                    {profiles.length > 0 && !isFull && (
                        <Button asChild>
                            <Link href={create()}>Add a trainer</Link>
                        </Button>
                    )}
                </div>

                {profiles.length === 0 ? (
                    <Empty className="mt-6">
                        <EmptyTitle>No trainers yet</EmptyTitle>
                        <EmptyDescription>
                            Add yourself, or your child if they are under 13.
                        </EmptyDescription>
                        <EmptyContent>
                            <Button asChild>
                                <Link href={create()}>Add a trainer</Link>
                            </Button>
                        </EmptyContent>
                    </Empty>
                ) : (
                    <ul className="mt-6 grid gap-3">
                        {profiles.map((profile) => (
                            <li key={profile.id}>
                                <Card>
                                    <CardHeader className="flex-row items-center justify-between gap-2">
                                        {/* A heading, so screen readers can jump between trainers. */}
                                        <CardTitle
                                            role="heading"
                                            aria-level={2}
                                            className="text-xl"
                                        >
                                            {profile.name}
                                        </CardTitle>
                                        <div className="flex items-center gap-2">
                                            {profile.isChild && (
                                                <Badge
                                                    variant="outline"
                                                    className="rounded-full"
                                                >
                                                    Child
                                                </Badge>
                                            )}
                                            <Link
                                                href={edit(profile.id)}
                                                className="inline-flex min-h-10 items-center px-1 text-sm font-semibold underline underline-offset-4"
                                                aria-label={`Edit ${profile.name}`}
                                            >
                                                Edit
                                            </Link>
                                            <DeleteTrainer profile={profile} />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground">
                                        <span>
                                            POP ID{' '}
                                            <span className="font-mono text-foreground">
                                                {profile.popId}
                                            </span>
                                        </span>
                                        <span>
                                            Born{' '}
                                            <span className="text-foreground">
                                                {profile.birthYear}
                                            </span>
                                        </span>
                                    </CardContent>
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}

                {isFull && (
                    <p className="mt-6 text-sm text-muted-foreground">
                        This account is full: your profile and 3 children. To
                        add more, email us at{' '}
                        <a
                            href={`mailto:${supportEmail}`}
                            className="font-semibold text-foreground underline"
                        >
                            {supportEmail}
                        </a>
                        .
                    </p>
                )}
            </div>
        </>
    );
}

Profiles.layout = [PublicLayout, {}];
