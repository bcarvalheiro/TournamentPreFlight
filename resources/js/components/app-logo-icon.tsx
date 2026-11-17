import type { SVGAttributes } from 'react';

/**
 * Pre-Flight mark: three fanned card outlines (graphite, outlined, red).
 * Decorative; AppLogo shows the app name as text next to it.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            viewBox="0 0 24 22"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
            focusable="false"
            {...props}
        >
            <rect
                x="2"
                y="4"
                width="11"
                height="15"
                rx="2"
                transform="rotate(-14 7 12)"
                fill="none"
                stroke="#8B97A3"
                strokeWidth="1.6"
            />
            <rect
                x="7"
                y="3"
                width="11"
                height="15"
                rx="2"
                fill="#1F2933"
                stroke="#fff"
                strokeWidth="1.6"
            />
            <rect
                x="11"
                y="4"
                width="11"
                height="15"
                rx="2"
                transform="rotate(14 16 12)"
                fill="#E63946"
            />
        </svg>
    );
}
