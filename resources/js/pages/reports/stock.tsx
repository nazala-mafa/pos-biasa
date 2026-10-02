import { Head } from "@inertiajs/react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import ReportSummary from "@/components/report-summary";
import { Badge } from "@/components/ui/badge";
import { useTranslation } from "@/hooks/use-translation";
import { formatCurrency, formatQuantity } from "@/lib/format";
import { stock } from "@/routes/reports";

type Row = {
    id: number;
    sku: string;
    name: string;
    category: string | null;
    unit: string | null;
    stock: number;
    minStock: number;
    costPrice: number;
    stockValue: number;
    isLow: boolean;
};

export default function StockReport({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();

    const columns: DataTableColumn<Row>[] = [
        { id: "sku", header: "SKU", searchValue: (row) => row.sku, cell: (row) => row.sku },
        {
            id: "name",
            header: t("Product"),
            searchValue: (row) => row.name,
            cell: (row) => <span className="font-medium">{row.name}</span>,
        },
        {
            id: "category",
            header: t("Category"),
            searchValue: (row) => row.category ?? "",
            cell: (row) => row.category ?? "-",
            hiddenByDefault: true,
        },
        {
            id: "stock",
            header: t("Stock"),
            className: "text-right",
            searchValue: (row) => String(row.stock),
            exportValue: (row) => row.stock,
            cell: (row) => `${formatQuantity(row.stock)} ${row.unit ?? ""}`,
        },
        {
            id: "minStock",
            header: t("Minimum stock"),
            className: "text-right",
            exportValue: (row) => row.minStock,
            cell: (row) => formatQuantity(row.minStock),
        },
        {
            id: "costPrice",
            header: t("Cost price"),
            className: "text-right",
            exportValue: (row) => row.costPrice,
            cell: (row) => formatCurrency(row.costPrice),
            hiddenByDefault: true,
        },
        {
            id: "stockValue",
            header: t("Stock value"),
            className: "text-right",
            exportValue: (row) => row.stockValue,
            cell: (row) => formatCurrency(row.stockValue),
        },
        {
            id: "status",
            header: t("Status"),
            searchValue: (row) => (row.isLow ? t("Low stock") : t("In stock")),
            cell: (row) => (
                <Badge variant={row.isLow ? "destructive" : "secondary"}>
                    {row.isLow ? t("Low stock") : t("In stock")}
                </Badge>
            ),
        },
    ];

    return (
        <>
            <Head title={t("Stock Report")} />
            <DataTable
                title={t("Stock Report")}
                description={t("Current stock and stock movements")}
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                searchPlaceholder={t("Search products...")}
                exportFilename="stock-report"
                summary={(filtered) => (
                    <ReportSummary
                        items={[
                            { label: t("Products"), value: String(filtered.length) },
                            {
                                label: t("Low stock"),
                                value: String(filtered.filter((row) => row.isLow).length),
                            },
                            {
                                label: t("Stock value"),
                                value: formatCurrency(filtered.reduce((sum, row) => sum + row.stockValue, 0)),
                            },
                        ]}
                    />
                )}
            />
        </>
    );
}

StockReport.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Stock Report", href: stock(props.currentTeam?.slug ?? "") },
    ],
});
