import { useId } from 'react';
import type { ReactNode, SVGProps } from 'react';

/** Play! Pokémon tournament types, from common to rare. */
export const TOURNAMENT_TYPES = [
    'friendly',
    'league',
    'challenge',
    'cup',
] as const;

export type TournamentType = (typeof TOURNAMENT_TYPES)[number];

export const TOURNAMENT_TYPE_NAMES: Record<TournamentType, string> = {
    friendly: 'Friendly',
    league: 'League',
    challenge: 'Challenge',
    cup: 'Cup',
};

type TournamentTypeIconProps = Omit<
    SVGProps<SVGSVGElement>,
    'type' | 'children'
> & {
    type: TournamentType;
    /** Width and height in px (the icon is square). Defaults to 1.15em, so it follows the text size. */
    size?: number | string;
};

/** The white bottom half, the band, the outline and the button: the same on every ball. */
function BallBottom(): ReactNode {
    return (
        <>
            <path d="M1 12a11 11 0 0 0 22 0z" fill="#fff" />
            <path d="M1 12h22" stroke="#1B1B1B" strokeWidth="2" />
            <circle
                cx="12"
                cy="12"
                r="11"
                fill="none"
                stroke="#1B1B1B"
                strokeWidth="1.5"
            />
            <circle
                cx="12"
                cy="12"
                r="3.3"
                fill="#fff"
                stroke="#1B1B1B"
                strokeWidth="1.6"
            />
        </>
    );
}

/** The coloured top half of each ball, exactly as in docs/design.md ("Type icons"). */
function BallTop({
    type,
    clipId,
}: {
    type: TournamentType;
    clipId: string;
}): ReactNode {
    switch (type) {
        case 'friendly':
            return <path d="M1 12a11 11 0 0 1 22 0z" fill="#E3350D" />;
        case 'league':
            return (
                <>
                    <path d="M1 12a11 11 0 0 1 22 0z" fill="#2F6BD8" />
                    <g clipPath={`url(#${clipId})`} fill="#E3350D">
                        <ellipse
                            cx="5.6"
                            cy="6.4"
                            rx="4"
                            ry="1.9"
                            transform="rotate(-38 5.6 6.4)"
                        />
                        <ellipse
                            cx="18.4"
                            cy="6.4"
                            rx="4"
                            ry="1.9"
                            transform="rotate(38 18.4 6.4)"
                        />
                    </g>
                </>
            );
        case 'challenge':
            return (
                <>
                    <path d="M1 12a11 11 0 0 1 22 0z" fill="#222" />
                    <g clipPath={`url(#${clipId})`} fill="#F5C518">
                        <rect x="3.2" y="0" width="3.4" height="12" />
                        <rect x="17.4" y="0" width="3.4" height="12" />
                        <rect x="3.2" y="6.2" width="17.6" height="2" />
                    </g>
                </>
            );
        case 'cup':
            return (
                <>
                    <path d="M1 12a11 11 0 0 1 22 0z" fill="#7B3FA0" />
                    <circle cx="6.3" cy="7.6" r="2.1" fill="#EC5FA6" />
                    <circle cx="17.7" cy="7.6" r="2.1" fill="#EC5FA6" />
                    <path
                        d="M9.2 9V4.4l2.8 2.9 2.8-2.9V9"
                        fill="none"
                        stroke="#fff"
                        strokeWidth="1.4"
                        strokeLinejoin="round"
                    />
                </>
            );
    }
}

/**
 * The ball icon for a tournament type (Bruno's decision, docs/design.md).
 * Decorative: always show the type name as text next to it (TournamentTypeBadge does).
 */
export function TournamentTypeIcon({
    type,
    size = '1.15em',
    ...props
}: TournamentTypeIconProps): ReactNode {
    // Two balls on one page must not share a clip id, or the second one draws wrongly.
    const clipId = `ball-top-${useId()}`;

    return (
        <svg
            viewBox="0 0 24 24"
            width={size}
            height={size}
            aria-hidden="true"
            focusable="false"
            {...props}
        >
            <defs>
                <clipPath id={clipId}>
                    <path d="M1 12a11 11 0 0 1 22 0z" />
                </clipPath>
            </defs>
            <BallTop type={type} clipId={clipId} />
            <BallBottom />
        </svg>
    );
}
