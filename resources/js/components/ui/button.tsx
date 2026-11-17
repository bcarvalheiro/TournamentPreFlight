import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

/*
 * Pre-Flight buttons (docs/design.md "Buttons"); shadcn variant names kept so kit pages still work:
 * - default = primary: red fill, white bold text
 * - outline = the design's "ghost": outlined graphite
 * - link    = text link (graphite text, red underline; no red hover: red text would fail contrast at this size)
 * Focus uses the global red outline from app.css. Only text, background and border colours animate,
 * so the red focus ring appears at once instead of fading in.
 */
const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-button text-sm font-bold transition-[color,background-color,border-color] cursor-pointer disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default: "bg-red text-white hover:bg-red/90",
        destructive: "bg-destructive text-white hover:bg-destructive/90",
        outline:
          "border-[1.5px] border-ink bg-transparent text-ink hover:bg-ink hover:text-white",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        ghost: "hover:bg-accent hover:text-accent-foreground",
        link: "font-semibold text-ink underline decoration-red decoration-2 underline-offset-4",
      },
      size: {
        default: "h-10 px-4 py-2 has-[>svg]:px-3",
        sm: "h-8 px-3 has-[>svg]:px-2.5",
        lg: "h-11 px-6 has-[>svg]:px-4",
        icon: "size-10",
      },
    },
    compoundVariants: [
      // A text link has no box: undo the size's height and padding.
      // min-h-10 keeps a 40px touch target (ui-reviewer rule).
      { variant: "link", className: "h-auto min-h-10 px-0 has-[>svg]:px-0" },
    ],
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
