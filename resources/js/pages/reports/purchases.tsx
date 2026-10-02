import { Head } from "@inertiajs/react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import ReportSummary from "@/components/report-summary";
import { Badge } from "@/components/ui/badge";
import { useTranslation } from "@/hooks/use-translation";
import { formatCurrency } from "@/lib/format";
import { purchases } from "@/routes/reports";

type Row = {
    id: number;
    date: string;
    number: string;
    supplier: string | null;
    status: string;
    statusLabel: string;
    itemsCount: number;
    total: number;
};

export default function PurchasesReport({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();

    const columns: DataTableColumn<Row>[] = [
        {
            id: "date",
            header: t("Date"),
            dateValue: (row) => row.date,
            exportValue: (row) => row.date,
            cell: (row) => row.date,
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
            searchValue: (row) => row.supplier ?? "",
            cell: (row) => row.supplier ?? "-",
        },
        {
            id: "status",
            header: t("Status"),
            searchValue: (row) => row.statusLabel,
            cell: (row) => (
                <Badge
                    variant={
                        row.status === "received"
                            ? "default"
                            : row.status === "cancelled"
                              ? "destructive"
                              : "secondary"
                    }
                >
                    {row.statusLabel}
                </Badge>
            ),
        },
        {
            id: "items",
            header: t("Items"),
            className: "text-right",
            exportValue: (row) => row.itemsCount,
            cell: (row) => row.itemsCount,
        },
        {
            id: "total",
            header: t("Total"),
            className: "text-right",
            searchValue: (row) => String(row.total),
            exportValue: (row) => row.total,
            cell: (row) => formatCurrency(row.total),
        },
    ];

    return (
        <>
            <Head title={t("Purchase Report")} />
            <DataTable
                title={t("Purchase Report")}
                description={t("Summary of purchase orders")}
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                searchPlaceholder={t("Search purchases...")}
                exportFilename="purchase-report"
                summary={(filtered) => {
                    const active = filtered.filter((row) => row.status !== "cancelled");

                    return (
                        <ReportSummary
                            items={[
                                { label: t("Purchase orders"), value: String(filtered.length) },
                                {
                                    label: t("Total purchases"),
                                    value: formatCurrency(active.reduce((sum, row) => sum + row.total, 0)),
                                },
                            ]}
                        />
                    );
                }}
            />
        </>
    );
}

PurchasesReport.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Purchase Report", href: purchases(props.currentTeam?.slug ?? "") },
    ],
});
