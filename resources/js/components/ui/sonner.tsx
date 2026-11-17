import { useFlashToast } from '@/hooks/use-flash-toast';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

/*
 * Toast (docs/design.md "Toast"): top right, graphite, 2.6s, a little larger
 * than Sonner's default (420px wide instead of 356px, 15px text instead of 13px).
 * Sonner announces toasts through an aria-live="polite" region, which screen
 * readers treat like role="status". Show one with toast('Saved') from 'sonner'.
 */
function Toaster({ ...props }: ToasterProps) {
    useFlashToast();

    return (
        <Sonner
            theme="light"
            className="toaster group"
            position="top-right"
            duration={2600}
            toastOptions={{
                // "!" = !important: Sonner's own styles would win otherwise (Sonner docs, "Styling").
                classNames: { toast: 'px-5! py-4! text-[15px]!' },
            }}
            style={
                {
                    '--width': '420px',
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
