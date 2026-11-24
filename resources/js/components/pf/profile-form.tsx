import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { OwnProfile } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type ProfileFormProps = {
    /** Where the form posts: `store.form()` to add, `update.form(id)` to edit. */
    form: RouteFormDefinition<'post'>;
    /** The first birth year that needs a parent's consent (the server's rule). */
    consentFromYear: number;
    /** The trainer being edited; none when adding one. */
    profile?: OwnProfile;
    /** Ask "I am 13 or older" again: the account's answer was cleared by a takeover. */
    asksAge?: boolean;
    submitLabel: string;
};

/**
 * A trainer's name, Pokemon Player ID (POP ID) and birth year (M07), shared by
 * "Add a trainer" and the edit page. The consent box appears as soon as the
 * birth year makes a new or adult trainer a child; an existing child keeps the
 * consent already given. The server checks everything again.
 */
export function ProfileForm({
    form,
    consentFromYear,
    profile,
    asksAge = false,
    submitLabel,
}: ProfileFormProps): ReactNode {
    const [birthYear, setBirthYear] = useState(
        profile ? String(profile.birthYear) : '',
    );
    const needsConsent =
        Number(birthYear) >= consentFromYear && !profile?.isChild;

    return (
        <Form
            {...form}
            disableWhileProcessing
            // The server's messages show under each field, all at once and in
            // English; the browser's own checks would block them (and speak its language).
            noValidate
            className="mt-6 grid gap-6"
        >
            {({ processing, errors }) => {
                // Links a field to its error so screen readers announce it, and
                // lets the kit's aria-invalid styles show (WCAG 1.3.1, 3.3.1).
                const describe = (field: string) =>
                    errors[field]
                        ? {
                              'aria-invalid': true,
                              'aria-describedby': `${field}-error`,
                          }
                        : {};

                return (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                {...describe('name')}
                                required
                                autoComplete="off"
                                defaultValue={profile?.name ?? ''}
                            />
                            <InputError id="name-error" message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="pop_id">
                                Pokemon Player ID (POP ID)
                            </Label>
                            <Input
                                id="pop_id"
                                name="pop_id"
                                {...describe('pop_id')}
                                required
                                inputMode="numeric"
                                pattern="[0-9]*"
                                maxLength={10}
                                autoComplete="off"
                                defaultValue={profile?.popId ?? ''}
                            />
                            <InputError
                                id="pop_id-error"
                                message={errors.pop_id}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="birth_year">Birth year</Label>
                            <Input
                                id="birth_year"
                                name="birth_year"
                                {...describe('birth_year')}
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
                            <InputError
                                id="birth_year-error"
                                message={errors.birth_year}
                            />
                        </div>

                        {needsConsent && (
                            <div className="grid gap-2">
                                {/* The error sits below the pink box: red text on the tint fails contrast. */}
                                <div className="flex items-center gap-3 rounded-xl border border-red/20 bg-red/5 px-3.5 py-3">
                                    <Checkbox
                                        id="consent"
                                        name="consent"
                                        {...describe('consent')}
                                    />
                                    <Label
                                        htmlFor="consent"
                                        className="flex min-h-10 flex-1 items-center"
                                    >
                                        I'm their parent or guardian, and I
                                        agree to add them to my account.
                                    </Label>
                                </div>
                                <InputError
                                    id="consent-error"
                                    message={errors.consent}
                                />
                            </div>
                        )}

                        {asksAge && (
                            <div className="grid gap-2">
                                {/* After a takeover the account holder's own answer is asked, not the trainer's. */}
                                <p className="text-sm font-semibold">
                                    First, confirm your own age.
                                </p>
                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="age_confirmed"
                                        name="age_confirmed"
                                        {...describe('age_confirmed')}
                                    />
                                    <Label
                                        htmlFor="age_confirmed"
                                        className="flex min-h-10 flex-1 items-center"
                                    >
                                        I am 13 or older
                                    </Label>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Under 13? A parent or guardian adds you to
                                    their account.
                                </p>
                                <InputError
                                    id="age_confirmed-error"
                                    message={errors.age_confirmed}
                                />
                            </div>
                        )}

                        <Button
                            type="submit"
                            className="w-full sm:w-auto sm:justify-self-start"
                        >
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                    </>
                );
            }}
        </Form>
    );
}
