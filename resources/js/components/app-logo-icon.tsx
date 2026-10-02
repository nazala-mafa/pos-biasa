import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({
    className,
    alt = '',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            src="/images/pos-biasa-logo.svg"
            alt={alt}
            className={`rounded-[22%] ${className ?? ''}`}
        />
    );
}
