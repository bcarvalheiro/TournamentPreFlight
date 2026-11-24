import { Form, Head } from '@inertiajs/react';
import { ProfileForm } from '@/components/pf/profile-form';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { forget } from '@/routes/events/going';
import { store } from '@/routes/profiles';

type Props = {
    /** The player pressed "I'm going" and the gate sent them here. */
    signingInToGo: boolean;
    /** The first birth year that needs a parent's consent (the server's rule). */
    consentFromYear: number;
    /** The account has no profile aged 13 or older yet (it may hold one). */
    canAddAdult: boolean;
    /** The account holds fewer than 3 children. */
    canAddChild: boolean;
    /** Where to write for a fourth child. */
    supportEmail: string;
    /** The account's "I am 13 or older" was cleared by a takeover: ask it again. */
    asksAge: boolean;
};

/** What the account may still add (decisions.md, 24 Nov: one adult, three children). */
function hint(canAddAdult: boolean, canAddChild: boolean): string {
    if (!canAddAdult) {
        return 'Your own profile is in. Add a child under 13.';
    }

    if (!canAddChild) {
        return 'Add yourself. This account already holds 3 children.';
    }

    return 'You, or your child if they are under 13.';
}

/**
 * "Add a trainer" (M07): the account's own profile or a child's, so a parent
 * who does not play can add only their child. The fields and the consent box
 * are the shared ProfileForm. After "I'm
 * going", "Not now" forgets that intent and goes back to the page. An account
 * holds one profile aged 13 or older and three children; when it is full, the
 * page says where to write instead of showing the form.
 */
export default function CreateProfile({
    signingInToGo,
    consentFromYear,
    canAddAdult,
    canAddChild,
    supportEmail,
    asksAge,
}: Props) {
    const isFull = !canAddAdult && !canAddChild;

    return (
        <>
            <Head title="Add a trainer" />
            <div className="mx-auto max-w-xl px-4 py-10">
                <h1 className="font-display text-3xl font-extrabold uppercase">
                    Add a trainer
                </h1>
                {!isFull && (
                    <p className="mt-2 text-muted-foreground">
                        {hint(canAddAdult, canAddChild)}
                    </p>
                )}
                {signingInToGo && (
                    <p className="mt-4 rounded-xl border border-red/20 bg-red/5 px-3.5 py-3 text-sm font-semibold">
                        Add a trainer to say you're going.
                    </p>
                )}

                {isFull ? (
                    <p className="mt-4 text-muted-foreground">
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
                ) : (
                    <ProfileForm
                        form={store.form()}
                        consentFromYear={consentFromYear}
                        asksAge={asksAge}
                        submitLabel="Save trainer"
                    />
                )}

                {signingInToGo && (
                    <Form {...forget.form()} className="mt-3">
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
