import { Form, Head } from '@inertiajs/react';
import WelcomeController from '@/actions/App/Http/Controllers/Auth/WelcomeController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    name: string | null;
    /** The email the provider certified for; it cannot be changed here. */
    email: string;
    /** The provider's display name: Google, Discord or X. */
    provider: string;
};

/**
 * Welcome page for a new social sign-in (M06): name, the provider's email
 * (read-only) and the age box.
 * Submitting creates the account, signs in and returns to the intended page.
 */
export default function Welcome({ name, email, provider }: Props) {
    return (
        <>
            <Head title="Welcome" />
            <Form
                {...WelcomeController.store.form()}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autoComplete="name"
                                defaultValue={name ?? ''}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                value={email}
                                readOnly
                                aria-describedby="email-hint"
                            />
                            <p
                                id="email-hint"
                                className="text-sm text-muted-foreground"
                            >
                                The email on your {provider} account.
                            </p>
                            <InputError message={errors.email} />
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
                                Under 13? A parent or guardian adds you to their
                                account.
                            </p>
                            <InputError message={errors.age_confirmed} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            Create account
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

Welcome.layout = {
    title: 'Welcome to Pre-Flight',
    description: 'Check your details to create your account',
};
