import { Head, Link, usePage } from "@inertiajs/react";
import { Eye, Plus } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency } from "@/lib/format";
import { create, index, show } from "@/routes/purchases";
import { useTranslation } from "@/hooks/use-translation";

type Row = {
    id: number;
    number: string;
    supplier: string;
    user: string | null;
    status: string;
    statusLabel: string;
    itemsCount: number;
    total: number;
    orderedAt: string;
    expectedAt: string | null;
};

export const statusVariant = (status: string) =>
    status === "received"
        ? "default"
        : status === "cancelled"
          ? "destructive"
          : "secondary";

export default function PurchasesIndex({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;

    const columns: DataTableColumn<Row>[] = [
        {
            id: "orderedAt",
            header: t("Date"),
            dateValue: (row) => row.orderedAt,
            cell: (row) => row.orderedAt,
        },
        {
            id: "number",
            header: t("Number"),
            searchValue: (row) => row.number,
            cell: (row) => <span className="font-medium">{row.number}</span>,
        },
        {
            id: "supplier",
            header: t("Supplier"),
            searchValue: (row) => row.supplier,
            cell: (row) => row.supplier,
        },
        {
            id: "expectedAt",
            header: t("Expected"),
            cell: (row) => row.expectedAt ?? "-",
            hiddenByDefault: true,
        },
        {
            id: "user",
            header: t("Created by"),
            searchValue: (row) => row.user ?? "",
            cell: (row) => row.user ?? "-",
            hiddenByDefault: true,
        },
        {
            id: "items",
            header: t("Items"),
            className: "text-right",
            cell: (row) => row.itemsCount,
            hiddenByDefault: true,
        },
        {
            id: "status",
            header: t("Status"),
            searchValue: (row) => row.statusLabel,
            cell: (row) => (
                <Badge variant={statusVariant(row.status)}>
                    {row.statusLabel}
                </Badge>
            ),
        },
        {
            id: "total",
            header: t("Total"),
            className: "text-right",
            searchValue: (row) => String(row.total),
            cell: (row) => formatCurrency(row.total),
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
                            aria-label={`${t("View")} ${row.number}`}
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
            <Head title={t("Purchases")} />
            <DataTable
                title={t("Purchases")}
                description={t("Purchase orders to suppliers and goods receipts")}
                actions={
                    auth.permissions.includes("purchases.create") ? (
                        <Button asChild data-test="purchases-new-button">
                            <Link href={create(team).url}>
                                <Plus /> {t("New purchase order")}
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="purchases-row"
                searchPlaceholder={t("Search purchases...")}
            />
        </>
    );
}

PurchasesIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Purchases", href: index(props.currentTeam?.slug ?? "") },
    ],
});
