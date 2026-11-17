import { useFlashToast } from '@/hooks/use-flash-toast';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

/*
 * Toast (docs/design.md "Toast"): bottom centre, graphite, 2.6s.
 * Sonner announces toasts through an aria-live="polite" region, which screen
 * readers treat like role="status". Show one with toast('Saved') from 'sonner'.
 */
function Toaster({ ...props }: ToasterProps) {
    useFlashToast();

    return (
        <Sonner
            theme="light"
            className="toaster group"
            position="bottom-center"
            duration={2600}
            style={
                {
                    '--normal-bg': 'var(--color-ink)',
                    '--normal-text': '#ffffff',
                    '--normal-border': 'var(--color-ink)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
