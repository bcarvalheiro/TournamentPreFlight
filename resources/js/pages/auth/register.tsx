import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import type { SocialProviderName } from '@/components/pf/social-buttons';
import { SocialButtons } from '@/components/pf/social-buttons';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
    socialProviders: SocialProviderName[];
    /** A guest pressed "I'm going" and was sent here to sign up. */
    signingInToGo: boolean;
};

export default function Register({
    passwordRules,
    socialProviders,
    signingInToGo,
}: Props) {
    return (
        <>
            <Head title="Create account" />

            {signingInToGo && (
                <p className="mb-4 rounded-xl border border-red/20 bg-red/5 px-3.5 py-3 text-sm">
                    <strong>Sign up to say you're going.</strong> It's a
                    headcount, not a registration.
                </p>
            )}

            <SocialButtons providers={socialProviders} />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Full name"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="age_confirmed"
                                        name="age_confirmed"
                                        required
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
                                <InputError message={errors.age_confirmed} />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink
                                href={login()}
                                className="inline-flex min-h-10 items-center"
                            >
                                Sign in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create account',
    description: 'Join to say you are going to tournaments',
};
