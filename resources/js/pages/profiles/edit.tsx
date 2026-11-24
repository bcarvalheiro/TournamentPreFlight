import { Head, Link } from '@inertiajs/react';
import { ProfileForm } from '@/components/pf/profile-form';
import PublicLayout from '@/layouts/public-layout';
import { index, update } from '@/routes/profiles';
import type { OwnProfile } from '@/types';

type Props = {
    profile: OwnProfile;
    /** The first birth year that needs a parent's consent (the server's rule). */
    consentFromYear: number;
};

/**
 * Edit a trainer (M07): the same fields as "Add a trainer", prefilled. Only
 * the trainer's own account reaches this page (PlayerProfilePolicy).
 */
export default function EditProfile({ profile, consentFromYear }: Props) {
    return (
        <>
            <Head title={`Edit ${profile.name}`} />
            <div className="mx-auto max-w-xl px-4 py-10">
                <h1 className="font-display text-3xl font-extrabold uppercase">
                    Edit trainer
                </h1>
                <ProfileForm
                    form={update.form(profile.id)}
                    consentFromYear={consentFromYear}
                    profile={profile}
                    submitLabel="Save changes"
                />
                <Link
                    href={index()}
                    className="mt-3 inline-flex min-h-10 items-center text-sm font-semibold underline underline-offset-4"
                >
                    Back to trainers
                </Link>
            </div>
        </>
    );
}

EditProfile.layout = [PublicLayout, {}];
