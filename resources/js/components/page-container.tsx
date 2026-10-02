import type { ReactNode } from "react";
import { cn } from "@/lib/utils";

/**
 * Max width by amount of data: sm = little, md = medium, lg = a lot.
 */
export type PageSize = "sm" | "md" | "lg";

const sizeClasses: Record<PageSize, string> = {
    sm: "max-w-4xl",
    md: "max-w-6xl",
    lg: "max-w-7xl",
};

export default function PageContainer({
    size = "lg",
    className,
    children,
}: {
    size?: PageSize;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn("w-full", sizeClasses[size], className)}>
            {children}
        </div>
    );
}
