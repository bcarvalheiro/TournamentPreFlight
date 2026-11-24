import { Head, Link } from '@inertiajs/react';
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
import { create } from '@/routes/profiles';
import type { OwnProfile } from '@/types';

type Props = {
    /** The account's own players, oldest first. */
    profiles: OwnProfile[];
};

/**
 * The account's players (M07): the holder's own profile and their
 * children's, or only a child's when the parent does not play. Seen only by
 * the account that owns them.
 */
export default function Profiles({ profiles }: Props) {
    return (
        <>
            <Head title="Players" />
            <div className="mx-auto max-w-xl px-4 py-10">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="font-display text-3xl font-extrabold uppercase">
                        Players
                    </h1>
                    {profiles.length > 0 && (
                        <Button asChild>
                            <Link href={create()}>Add a player</Link>
                        </Button>
                    )}
                </div>

                {profiles.length === 0 ? (
                    <Empty className="mt-6">
                        <EmptyTitle>No players yet</EmptyTitle>
                        <EmptyDescription>
                            Add yourself, or your child if they are under 13.
                        </EmptyDescription>
                        <EmptyContent>
                            <Button asChild>
                                <Link href={create()}>Add a player</Link>
                            </Button>
                        </EmptyContent>
                    </Empty>
                ) : (
                    <ul className="mt-6 grid gap-3">
                        {profiles.map((profile) => (
                            <li key={profile.id}>
                                <Card>
                                    <CardHeader className="flex-row items-center justify-between gap-2">
                                        <CardTitle className="text-xl">
                                            {profile.name}
                                        </CardTitle>
                                        {profile.isChild && (
                                            <Badge variant="outline">
                                                Child
                                            </Badge>
                                        )}
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
            </div>
        </>
    );
}

Profiles.layout = [PublicLayout, {}];
