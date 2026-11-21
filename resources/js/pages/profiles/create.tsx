import { Head } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';

/**
 * Placeholder for "Create your player profile" (M06). The player gate sends
 * signed-in users here until they have a password and a profile; M07 builds
 * the real form.
 */
export default function CreateProfile() {
    return (
        <>
            <Head title="Create your player profile" />
            <div className="mx-auto max-w-xl px-4 py-10">
                <h1 className="font-display text-3xl font-extrabold uppercase">
                    Create your player profile
                </h1>
                <p className="mt-3 text-muted-foreground">
                    Profiles are coming soon. You will add your name, Player ID
                    and birth year here.
                </p>
            </div>
        </>
    );
}

CreateProfile.layout = [PublicLayout, {}];
