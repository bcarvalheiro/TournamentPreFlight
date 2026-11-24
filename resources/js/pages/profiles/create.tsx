import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import PublicLayout from '@/layouts/public-layout';
import { forget } from '@/routes/events/going';
import { store } from '@/routes/profiles';

type Props = {
    /** The player pressed "I'm going" and the gate sent them here. */
    signingInToGo: boolean;
    /** The first birth year that needs a parent's consent (the server's rule). */
    consentFromYear: number;
};

/**
 * "Add a player" (M07): the account's own profile or a child's, so a parent
 * who does not play can add only their child. The consent box appears as soon
 * as a child birth year is typed; the server checks it again. After "I'm
 * going", "Not now" forgets that intent and goes back to the page.
 */
export default function CreateProfile({
    signingInToGo,
    consentFromYear,
}: Props) {
    const [birthYear, setBirthYear] = useState('');
    const needsConsent = Number(birthYear) >= consentFromYear;

    return (
        <>
            <Head title="Add a player" />
            <div className="mx-auto max-w-xl px-4 py-10">
                <h1 className="font-display text-3xl font-extrabold uppercase">
                    Add a player
                </h1>
                <p className="mt-2 text-muted-foreground">
                    You, or your child if they are under 13.
                </p>
                {signingInToGo && (
                    <p className="mt-4 rounded-xl border border-red/20 bg-red/5 px-3.5 py-3 text-sm font-semibold">
                        Add a player to say you're going.
                    </p>
                )}

                <Form
                    {...store.form()}
                    disableWhileProcessing
                    // The server's messages show under each field, all at once and in
                    // English; the browser's own checks would block them (and speak its language).
                    noValidate
                    className="mt-6 grid gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoComplete="off"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="pop_id">
                                    Pokemon Player ID (POP ID)
                                </Label>
                                <Input
                                    id="pop_id"
                                    name="pop_id"
                                    required
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    maxLength={10}
                                    autoComplete="off"
                                />
                                <InputError message={errors.pop_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="birth_year">Birth year</Label>
                                <Input
                                    id="birth_year"
                                    name="birth_year"
                                    type="number"
                                    required
                                    min={1920}
                                    max={consentFromYear + 13}
                                    value={birthYear}
                                    onChange={(event) =>
                                        setBirthYear(event.target.value)
                                    }
                                    className="w-32"
                                />
                                <InputError message={errors.birth_year} />
                            </div>

                            {needsConsent && (
                                <div className="grid gap-2 rounded-xl border border-red/20 bg-red/5 px-3.5 py-3">
                                    <div className="flex items-center gap-3">
                                        <Checkbox id="consent" name="consent" />
                                        <Label
                                            htmlFor="consent"
                                            className="flex min-h-10 flex-1 items-center"
                                        >
                                            I'm their parent or guardian, and I
                                            agree to add them to my account.
                                        </Label>
                                    </div>
                                    <InputError message={errors.consent} />
                                </div>
                            )}

                            <Button
                                type="submit"
                                className="w-full sm:w-auto sm:justify-self-start"
                            >
                                {processing && <Spinner />}
                                Save player
                            </Button>
                        </>
                    )}
                </Form>

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
