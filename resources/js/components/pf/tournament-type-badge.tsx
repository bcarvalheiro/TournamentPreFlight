import type { ReactNode } from 'react';
import { TournamentTypeIcon } from '@/components/pf/tournament-type-icon';
import { Badge } from '@/components/ui/badge';
import { TOURNAMENT_TYPE_NAMES } from '@/lib/tournament-types';
import type { TournamentType } from '@/lib/tournament-types';
import { cn } from '@/lib/utils';

type TournamentTypeBadgeProps = {
    type: TournamentType;
    className?: string;
};

/**
 * Ball icon + type name ("CASUAL"). The name is real text, so screen readers
 * and colour-blind players get the type even though the icon is hidden from them.
 * Cup (the rarest) is filled graphite; the others are outlined.
 */
export function TournamentTypeBadge({
    type,
    className,
}: TournamentTypeBadgeProps): ReactNode {
    return (
        <Badge
            variant="outline"
            className={cn(
                'gap-1 rounded-[4px] border-[1.5px] border-ink px-2 py-px font-display text-[0.85rem] font-bold tracking-[0.06em] text-ink uppercase [&>svg]:size-[1.15em]',
                type === 'cup' && 'bg-ink text-white',
                className,
            )}
        >
            <TournamentTypeIcon type={type} />
            {TOURNAMENT_TYPE_NAMES[type]}
        </Badge>
    );
}
