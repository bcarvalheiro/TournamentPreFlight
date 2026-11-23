import { Card } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import type { AuthLayoutProps } from '@/types';

/**
 * Layout for every auth page (sign in, register, password, verification,
 * welcome): the Pre-Flight band and footer, with the form in a card.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <PublicLayout>
            {/* Phones: full width. From 768px: up to 32rem, more air around it. */}
            <div className="mx-auto w-full max-w-md py-4 md:max-w-lg md:py-12">
                {/* Fields and buttons 44px tall with 16px text inside the card. */}
                <Card className="gap-6 px-5 py-8 md:gap-8 md:px-10 md:py-10 [&_[data-slot=button]]:h-11 [&_[data-slot=button]]:text-base [&_[data-slot=input]]:h-11 [&_[data-slot=input]]:text-base">
                    <div className="text-center">
                        <h1 className="font-display text-4xl font-extrabold uppercase md:text-5xl">
                            {title}
                        </h1>
                        <p className="mt-1 text-base text-muted">
                            {description}
                        </p>
                    </div>
                    {children}
                </Card>
            </div>
        </PublicLayout>
    );
}
