import { Head, Link, usePage } from "@inertiajs/react";
import { Eye, Plus } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency } from "@/lib/format";
import { create, index, show } from "@/routes/shifts";
import { useTranslation } from "@/hooks/use-translation";

type Row = {
    id: number;
    user: string;
    isOpen: boolean;
    openedAt: string;
    closedAt: string | null;
    openedDate: string;
    openingCash: number;
    expectedCash: number | null;
    closingCash: number | null;
    difference: number | null;
};

type Props = { rows: Row[]; currentShiftId: number | null };

export default function ShiftsIndex({ rows, currentShiftId }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;
    const money = (value: number | null) =>
        value === null ? "-" : formatCurrency(value);

    const columns: DataTableColumn<Row>[] = [
        {
            id: "openedAt",
            header: t("Opened"),
            dateValue: (row) => row.openedDate,
            cell: (row) => <span className="font-medium">{row.openedAt}</span>,
        },
        {
            id: "closedAt",
            header: t("Closed"),
            cell: (row) => row.closedAt ?? "-",
            hiddenByDefault: true,
        },
        {
            id: "user",
            header: t("Cashier"),
            searchValue: (row) => row.user,
            cell: (row) => row.user,
        },
        {
            id: "status",
            header: t("Status"),
            searchValue: (row) => (row.isOpen ? t("Open") : t("Closed")),
            cell: (row) => (
                <Badge variant={row.isOpen ? "default" : "secondary"}>
                    {row.isOpen ? t("Open") : t("Closed")}
                </Badge>
            ),
        },
        {
            id: "openingCash",
            header: t("Opening cash"),
            className: "text-right",
            cell: (row) => money(row.openingCash),
            hiddenByDefault: true,
        },
        {
            id: "expectedCash",
            header: t("Expected cash"),
            className: "text-right",
            cell: (row) => money(row.expectedCash),
        },
        {
            id: "closingCash",
            header: t("Counted cash"),
            className: "text-right",
            cell: (row) => money(row.closingCash),
        },
        {
            id: "difference",
            header: t("Difference"),
            className: "text-right",
            cell: (row) => money(row.difference),
        },
        {
            id: "actions",
            header: t("Actions"),
            className: "text-right",
            cell: (row) => (
                <div className="flex justify-end">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={show([team, row.id]).url}
                            aria-label={`${t("View")} ${row.openedAt}`}
                        >
                            <Eye className="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title={t("Cashier Shifts")} />
            <DataTable
                title={t("Cashier Shifts")}
                description={t("Open and close the cash drawer per cashier")}
                actions={
                    currentShiftId ? (
                        <Button asChild data-test="shift-current-button">
                            <Link href={show([team, currentShiftId]).url}>
                                {t("Current shift")}
                            </Link>
                        </Button>
                    ) : auth.permissions.includes("shifts.open") ? (
                        <Button asChild data-test="shifts-new-button">
                            <Link href={create(team).url}>
                                <Plus /> {t("Open shift")}
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="shifts-row"
                searchPlaceholder={t("Search shifts...")}
            />
        </>
    );
}

ShiftsIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Cashier Shifts", href: index(props.currentTeam?.slug ?? "") },
    ],
});
