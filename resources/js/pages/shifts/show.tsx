import { Form, Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import InputError from "@/components/input-error";
import PageContainer from "@/components/page-container";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency } from "@/lib/format";
import { close, index } from "@/routes/shifts";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    shift: {
        id: number;
        user: string;
        isOpen: boolean;
        openedAt: string;
        closedAt: string | null;
        openingCash: number;
        expectedCash: number | null;
        closingCash: number | null;
        difference: number | null;
        note: string | null;
        cashSales: number;
        salesTotal: number;
        salesCount: number;
        liveExpectedCash: number;
        canClose: boolean;
    };
};

export default function ShiftsShow({ shift }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    const summary: [string, string, boolean?][] = [
        [t("Opening cash"), formatCurrency(shift.openingCash)],
        [t("Sales"), `${shift.salesCount} · ${formatCurrency(shift.salesTotal)}`],
        [t("Cash sales"), formatCurrency(shift.cashSales)],
        [
            t("Expected cash"),
            formatCurrency(shift.expectedCash ?? shift.liveExpectedCash),
            true,
        ],
        ...(shift.closingCash !== null
            ? ([
                  [t("Counted cash"), formatCurrency(shift.closingCash)],
                  [t("Difference"), formatCurrency(shift.difference ?? 0), true],
              ] as [string, string, boolean?][])
            : []),
    ];

    return (
        <>
            <Head title={t("Cashier Shifts")} />

            <PageContainer size="md" className="space-y-6 p-4">
                <Card className="gap-0 py-0">
                    <CardHeader className="flex flex-row items-start justify-between border-b px-6 py-4">
                        <div className="space-y-1">
                            <CardTitle className="flex items-center gap-2">
                                {shift.user}
                                <Badge variant={shift.isOpen ? "default" : "secondary"}>
                                    {shift.isOpen ? t("Open") : t("Closed")}
                                </Badge>
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                {t("Opened")}: {shift.openedAt}
                                {shift.closedAt ? ` · ${t("Closed")}: ${shift.closedAt}` : ""}
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={index(team).url}>
                                <ArrowLeft /> {t("Cashier Shifts")}
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4 p-6">
                        <dl className="w-full max-w-sm space-y-1 text-sm">
                            {summary.map(([label, value, strong]) => (
                                <div
                                    key={label}
                                    className={`flex justify-between ${strong ? "border-t pt-1 text-base font-semibold" : ""}`}
                                >
                                    <dt>{label}</dt>
                                    <dd>{value}</dd>
                                </div>
                            ))}
                        </dl>
                        {shift.note ? (
                            <p className="text-sm text-muted-foreground">
                                {t("Note")}: {shift.note}
                            </p>
                        ) : null}
                    </CardContent>
                </Card>

                {shift.canClose ? (
                    <Card className="gap-0 py-0">
                        <CardHeader className="border-b px-6 py-4">
                            <CardTitle>{t("Close shift")}</CardTitle>
                        </CardHeader>
                        <Form {...close.form([team, shift.id])}>
                            {({ errors, processing }) => (
                                <CardContent className="space-y-4 p-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="closing_cash">{t("Counted cash")}</Label>
                                        <Input id="closing_cash" name="closing_cash" type="number" step="0.01" min="0" required />
                                        <InputError message={errors.closing_cash} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="close_note">{t("Note")}</Label>
                                        <Input id="close_note" name="note" maxLength={255} />
                                        <InputError message={errors.note} />
                                    </div>
                                    <Button type="submit" disabled={processing} data-test="shift-close-button">
                                        {t("Close shift")}
                                    </Button>
                                </CardContent>
                            )}
                        </Form>
                    </Card>
                ) : null}
            </PageContainer>
        </>
    );
}

ShiftsShow.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Cashier Shifts", href: index(props.currentTeam?.slug ?? "") },
    ],
});
