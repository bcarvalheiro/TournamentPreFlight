// Components
import { Form, Head } from '@inertiajs/react';
import { MailAlertIcon } from '@/components/pf/mail-alert-icon';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <Head title="Email verification" />

            {status === 'verification-link-sent' && (
                <div
                    role="status"
                    className="mb-4 text-center text-sm font-semibold text-ink"
                >
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        {/* The pulsing mail badge leads the button, and the button wears the
                            same soft red tint, so they read as one control. */}
                        <Button
                            disabled={processing}
                            variant="secondary"
                            className="h-11 gap-2.5 border border-red/20 bg-red/5 pr-4 pl-1 text-ink hover:bg-red/10"
                        >
                            {processing ? (
                                <span className="grid size-9 place-items-center">
                                    <Spinner />
                                </span>
                            ) : (
                                <MailAlertIcon />
                            )}
                            Resend verification email
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto flex min-h-10 w-fit items-center text-sm"
                        >
                            Sign out
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Email verification',
    description:
        'Please verify your email address by clicking on the link we just emailed to you.',
};
