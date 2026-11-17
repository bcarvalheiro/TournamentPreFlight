import { XIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactElement, ReactNode } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/drawer';
import { useIsMobile } from '@/hooks/use-mobile';

type ResponsiveDialogProps = {
    /** The button that opens the dialog. Focus returns to it on close. */
    trigger: ReactElement;
    title: string;
    description?: string;
    children?: ReactNode;
    /** Action buttons, shown at the bottom. */
    footer?: ReactNode;
    /** Optional control from the parent; otherwise the dialog keeps its own open state. */
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
};

/**
 * The design's "Sheet": a bottom drawer on phones, a centred dialog on desktop.
 * Built like shadcn/ui's "Responsive Dialog" example (Dialog + Drawer), switched
 * with the kit's useIsMobile() hook (below 768px). Both close on Escape and on a
 * backdrop click, trap focus while open, and return focus to the trigger.
 */
export function ResponsiveDialog({
    trigger,
    title,
    description,
    children,
    footer,
    open,
    onOpenChange,
}: ResponsiveDialogProps): ReactNode {
    const [ownOpen, setOwnOpen] = useState(false);
    const isOpen = open ?? ownOpen;
    const setOpen = onOpenChange ?? setOwnOpen;
    const isMobile = useIsMobile();
    // Without a description, tell Radix on purpose; otherwise it logs a console warning (Radix Dialog docs).
    const descriptionProps = description
        ? {}
        : { 'aria-describedby': undefined };

    if (!isMobile) {
        return (
            <Dialog open={isOpen} onOpenChange={setOpen}>
                <DialogTrigger asChild>{trigger}</DialogTrigger>
                <DialogContent
                    className="rounded-sheet border-line sm:max-w-md"
                    {...descriptionProps}
                >
                    <DialogHeader>
                        <DialogTitle className="font-display text-2xl font-bold">
                            {title}
                        </DialogTitle>
                        {description && (
                            <DialogDescription>{description}</DialogDescription>
                        )}
                    </DialogHeader>
                    {children}
                    {footer && <DialogFooter>{footer}</DialogFooter>}
                </DialogContent>
            </Dialog>
        );
    }

    return (
        // autoFocus: vaul defaults it to false, which stops focus moving into the
        // drawer on open, so the focus trap never engages (found by the browser test).
        <Drawer open={isOpen} onOpenChange={setOpen} autoFocus>
            <DrawerTrigger asChild>{trigger}</DrawerTrigger>
            <DrawerContent
                className="rounded-t-sheet border-line"
                {...descriptionProps}
            >
                <DrawerClose
                    className="absolute top-3 right-3 rounded-full p-2 text-muted hover:text-ink"
                    aria-label="Close"
                >
                    <XIcon className="size-5" />
                </DrawerClose>
                <DrawerHeader className="text-left">
                    <DrawerTitle className="font-display text-2xl font-bold">
                        {title}
                    </DrawerTitle>
                    {description && (
                        <DrawerDescription>{description}</DrawerDescription>
                    )}
                </DrawerHeader>
                {children && <div className="px-4">{children}</div>}
                {footer && <DrawerFooter>{footer}</DrawerFooter>}
            </DrawerContent>
        </Drawer>
    );
}
