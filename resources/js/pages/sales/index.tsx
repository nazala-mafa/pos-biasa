import { Head, Link, usePage } from "@inertiajs/react";
import { Eye, Plus } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency } from "@/lib/format";
import { create, index, show } from "@/routes/sales";

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
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;

    const columns: DataTableColumn<Row>[] = [
        {
            id: "createdAt",
            header: "Date",
            dateValue: (row) => row.createdAt,
            cell: (row) => row.createdAt ?? "-",
        },
        {
            id: "invoiceNumber",
            header: "Invoice",
            searchValue: (row) => row.invoiceNumber,
            cell: (row) => (
                <span className="font-medium">{row.invoiceNumber}</span>
            ),
        },
        {
            id: "customer",
            header: "Customer",
            searchValue: (row) => row.customer ?? "",
            cell: (row) => row.customer ?? "-",
        },
        {
            id: "user",
            header: "Cashier",
            searchValue: (row) => row.user ?? "",
            cell: (row) => row.user ?? "-",
            hiddenByDefault: true,
        },
        {
            id: "items",
            header: "Items",
            className: "text-right",
            cell: (row) => row.itemsCount,
            hiddenByDefault: true,
        },
        {
            id: "payment",
            header: "Payment",
            searchValue: (row) => row.paymentLabel,
            cell: (row) => (
                <Badge variant="secondary">{row.paymentLabel}</Badge>
            ),
        },
        {
            id: "total",
            header: "Total",
            className: "text-right",
            searchValue: (row) => String(row.total),
            cell: (row) => formatCurrency(row.total),
        },
        {
            id: "actions",
            header: "Actions",
            className: "text-right",
            cell: (row) => (
                <div className="flex justify-end">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={show([team, row.id]).url}
                            aria-label={`View ${row.invoiceNumber}`}
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
            <Head title="Sales" />
            <DataTable
                title="Sales"
                description="Transactions recorded by this outlet"
                actions={
                    auth.permissions.includes("sales.create") ? (
                        <Button asChild data-test="sales-new-button">
                            <Link href={create(team).url}>
                                <Plus /> New sale
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="sales-row"
                searchPlaceholder="Search sales..."
            />
        </>
    );
}

SalesIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Sales", href: index(props.currentTeam?.slug ?? "") },
    ],
});
