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
    /**
     * The button that opens the dialog. Focus returns to it on close. Optional
     * when the parent opens the dialog itself (`open`), e.g. after sign-in.
     */
    trigger?: ReactElement;
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
                {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
                {/* A flex column at most 85% of the screen tall: only the content scrolls. */}
                <DialogContent
                    className="flex max-h-[85vh] flex-col rounded-sheet border-line sm:max-w-md"
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
                    {children && (
                        <div className="-mx-1 min-h-0 flex-1 overflow-y-auto px-1">
                            {children}
                        </div>
                    )}
                    {footer && <DialogFooter>{footer}</DialogFooter>}
                </DialogContent>
            </Dialog>
        );
    }

    return (
        // autoFocus: vaul defaults it to false, which stops focus moving into the
        // drawer on open, so the focus trap never engages (found by the browser test).
        <Drawer open={isOpen} onOpenChange={setOpen} autoFocus>
            {trigger && <DrawerTrigger asChild>{trigger}</DrawerTrigger>}
            <DrawerContent
                className="rounded-t-sheet border-line data-[vaul-drawer-direction=bottom]:max-h-[85vh]"
                {...descriptionProps}
            >
                <DrawerClose
                    className="absolute top-3 right-3 flex size-10 items-center justify-center rounded-full text-muted hover:text-ink"
                    aria-label="Close"
                >
                    <XIcon className="size-5" />
                </DrawerClose>
                {/* The kit centres bottom-drawer titles with this same variant; ours wins. */}
                <DrawerHeader className="text-left group-data-[vaul-drawer-direction=bottom]/drawer-content:text-left">
                    <DrawerTitle className="font-display text-2xl font-bold">
                        {title}
                    </DrawerTitle>
                    {description && (
                        <DrawerDescription>{description}</DrawerDescription>
                    )}
                </DrawerHeader>
                {children && (
                    <div className="min-h-0 flex-1 overflow-y-auto px-4">
                        {children}
                    </div>
                )}
                {footer && (
                    <DrawerFooter className="grid grid-cols-2">
                        {footer}
                    </DrawerFooter>
                )}
            </DrawerContent>
        </Drawer>
    );
}
