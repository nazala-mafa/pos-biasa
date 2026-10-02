import { Head, Link } from "@inertiajs/react";
import type { ReactNode } from "react";
import PageContainer from "@/components/page-container";
import type { PageSize } from "@/components/page-container";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    title: string;
    description?: string;
    processing: boolean;
    cancelHref: string;
    saveTestId: string;
    children: ReactNode;
    actionFields?: ReactNode;
    sidebar?: ReactNode;
    /** Max width: sm (little data), md (medium), lg (a lot). Defaults to md. */
    size?: PageSize;
};

/**
 * WordPress-style editor layout: main card on the left, an "Action" card
 * (fields, submit, cancel) and optional meta cards on the right.
 * Render it inside an Inertia <Form>.
 */
export default function FormLayout({
    title,
    description,
    processing,
    cancelHref,
    saveTestId,
    children,
    actionFields,
    sidebar,
    size = "md",
}: Props) {
    const { t } = useTranslation();
    return (
        <>
            <Head title={t(title)} />

            <PageContainer
                size={size}
                className="grid items-start gap-6 p-4 lg:grid-cols-[minmax(0,1fr)_20rem]"
            >
                <Card className="gap-0 py-0">
                    <CardHeader className="border-b px-6 py-4">
                        <CardTitle>{t(title)}</CardTitle>
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {t(description)}
                            </p>
                        )}
                    </CardHeader>
                    <CardContent className="space-y-6 p-6">
                        {children}
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card className="gap-0 py-0">
                        <CardHeader className="border-b px-6 py-4">
                            <CardTitle>{t("Action")}</CardTitle>
                        </CardHeader>
                        {actionFields && (
                            <CardContent className="space-y-4 px-6 pt-4">
                                {actionFields}
                            </CardContent>
                        )}
                        <CardFooter className="flex-col gap-2 px-6 py-4">
                            <Button
                                type="submit"
                                className="w-full"
                                data-test={saveTestId}
                                disabled={processing}
                            >
                                {t("Submit")}
                            </Button>
                            <Button
                                variant="secondary"
                                className="w-full"
                                asChild
                            >
                                <Link href={cancelHref}>{t("Cancel")}</Link>
                            </Button>
                        </CardFooter>
                    </Card>

                    {sidebar}
                </div>
            </PageContainer>
        </>
    );
}

export function SidebarCard({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const { t } = useTranslation();

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="border-b px-6 py-4">
                <CardTitle>{t(title)}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 p-6">{children}</CardContent>
        </Card>
    );
}
