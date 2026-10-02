import { Head, Link, usePage } from "@inertiajs/react";
import { Eye, Plus } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency } from "@/lib/format";
import { create, index, show } from "@/routes/sales";
import { useTranslation } from "@/hooks/use-translation";

type Row = {
    id: number;
    invoiceNumber: string;
    customer: string | null;
    user: string | null;
    paymentLabel: string;
    itemsCount: number;
    total: number;
    createdAt: string | null;
};

export default function SalesIndex({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;

    const columns: DataTableColumn<Row>[] = [
        {
            id: "createdAt",
            header: t("Date"),
            dateValue: (row) => row.createdAt,
            cell: (row) => row.createdAt ?? "-",
        },
        {
            id: "invoiceNumber",
            header: t("Invoice"),
            searchValue: (row) => row.invoiceNumber,
            cell: (row) => (
                <span className="font-medium">{row.invoiceNumber}</span>
            ),
        },
        {
            id: "customer",
            header: t("Customer"),
            searchValue: (row) => row.customer ?? "",
            cell: (row) => row.customer ?? "-",
        },
        {
            id: "user",
            header: t("Cashier"),
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
            id: "payment",
            header: t("Payment"),
            searchValue: (row) => row.paymentLabel,
            cell: (row) => (
                <Badge variant="secondary">{row.paymentLabel}</Badge>
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
                            aria-label={`${t("View")} ${row.invoiceNumber}`}
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
            <Head title={t("Sales")} />
            <DataTable
                title={t("Sales")}
                description={t("Transactions recorded by this outlet")}
                actions={
                    auth.permissions.includes("sales.create") ? (
                        <Button asChild data-test="sales-new-button">
                            <Link href={create(team).url}>
                                <Plus /> {t("New sale")}
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="sales-row"
                searchPlaceholder={t("Search sales...")}
            />
        </>
    );
}

SalesIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Sales", href: index(props.currentTeam?.slug ?? "") },
    ],
});
