import type { ComponentProps } from "react";
import { cn } from "@/lib/utils";

/** Native <select> styled like <Input>, so it submits with Inertia's <Form>. */
export default function NativeSelect({
    className,
    ...props
}: ComponentProps<"select">) {
    return (
        <select
            className={cn(
                "border-input bg-transparent flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50",
                className,
            )}
            {...props}
        />
    );
}
