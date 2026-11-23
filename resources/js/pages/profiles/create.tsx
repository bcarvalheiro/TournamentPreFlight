import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { forget } from '@/routes/events/going';

type Props = {
    /** The player pressed "I'm going" and the gate sent them here. */
    signingInToGo: boolean;
};

/**
 * Placeholder for "Create your player profile" (M06); M07 builds the form.
 * After "I'm going", the player gate sends a user without a Player ID here;
 * "Not now" forgets that intent and goes back to the page.
 */
export default function CreateProfile({ signingInToGo }: Props) {
    return (
        <>
            <Head title="Create your player profile" />
            <div className="mx-auto max-w-xl px-4 py-10">
                <h1 className="font-display text-3xl font-extrabold uppercase">
                    Create your player profile
                </h1>
                {signingInToGo && (
                    <p className="mt-3 font-semibold">
                        Add your Player ID to say you're going.
                    </p>
                )}
                <p className="mt-3 text-muted-foreground">
                    Profiles are coming soon. You will add your name, Player ID
                    and birth year here.
                </p>
                {signingInToGo && (
                    <Form {...forget.form()} className="mt-6">
                        <Button type="submit" variant="outline">
                            Not now
                        </Button>
                    </Form>
                )}
            </div>
        </>
    );
}

CreateProfile.layout = [PublicLayout, {}];
