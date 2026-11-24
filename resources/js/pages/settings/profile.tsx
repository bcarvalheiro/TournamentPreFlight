import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { MailAlertIcon } from '@/components/pf/mail-alert-icon';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';
import { send } from '@/routes/verification';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    // A quiet card, not a warning box: a red stripe and a
                                    // softly pinging dot draw the eye without shouting.
                                    <div className="relative -mt-2 overflow-hidden rounded-xl border border-line bg-bg-2 py-3.5 pr-3.5 pl-5">
                                        <span
                                            aria-hidden
                                            className="absolute inset-y-0 left-0 w-1 bg-red"
                                        />
                                        <div className="flex items-start gap-3">
                                            <MailAlertIcon />
                                            <div className="grid flex-1 gap-1">
                                                <p className="text-sm font-semibold">
                                                    Verify your email
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    We sent a link to{' '}
                                                    {auth.user.email}. Open it
                                                    to add trainers and say
                                                    you're going.
                                                </p>
                                                {status ===
                                                'verification-link-sent' ? (
                                                    <p className="mt-1 flex min-h-10 items-center gap-1.5 text-sm font-medium text-green-700 dark:text-green-400">
                                                        <Check
                                                            aria-hidden
                                                            className="size-4"
                                                        />
                                                        New link sent. Check
                                                        your inbox.
                                                    </p>
                                                ) : (
                                                    <Link
                                                        href={send()}
                                                        as="button"
                                                        className="mt-1 inline-flex min-h-10 items-center gap-1.5 justify-self-start text-sm font-semibold underline decoration-red/40 underline-offset-4 transition-colors hover:decoration-red"
                                                    >
                                                        Resend the link
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
