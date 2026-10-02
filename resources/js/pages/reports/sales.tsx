import { Head } from "@inertiajs/react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import ReportSummary from "@/components/report-summary";
import { useTranslation } from "@/hooks/use-translation";
import { formatCurrency } from "@/lib/format";
import { sales } from "@/routes/reports";

type Row = {
    id: number;
    date: string;
    invoice: string;
    customer: string | null;
    cashier: string | null;
    paymentMethod: string | null;
    itemsCount: number;
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
};

const sum = (rows: Row[], key: "subtotal" | "discount" | "tax" | "total") =>
    rows.reduce((total, row) => total + row[key], 0);

export default function SalesReport({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();

    const money = (key: "subtotal" | "discount" | "tax" | "total") => ({
        className: "text-right",
        searchValue: (row: Row) => String(row[key]),
        exportValue: (row: Row) => row[key],
        cell: (row: Row) => formatCurrency(row[key]),
    });

    const columns: DataTableColumn<Row>[] = [
        {
            id: "date",
            header: t("Date"),
            dateValue: (row) => row.date,
            exportValue: (row) => row.date,
            cell: (row) => row.date,
        },
        {
            id: "invoice",
            header: t("Invoice"),
            searchValue: (row) => row.invoice,
            cell: (row) => <span className="font-medium">{row.invoice}</span>,
        },
        {
            id: "customer",
            header: t("Customer"),
            searchValue: (row) => row.customer ?? "",
            cell: (row) => row.customer ?? "-",
        },
        {
            id: "cashier",
            header: t("Cashier"),
            searchValue: (row) => row.cashier ?? "",
            cell: (row) => row.cashier ?? "-",
        },
        {
            id: "paymentMethod",
            header: t("Payment method"),
            searchValue: (row) => row.paymentMethod ?? "",
            cell: (row) => row.paymentMethod ?? "-",
        },
        {
            id: "items",
            header: t("Items"),
            className: "text-right",
            exportValue: (row) => row.itemsCount,
            cell: (row) => row.itemsCount,
        },
        { id: "subtotal", header: t("Subtotal"), ...money("subtotal"), hiddenByDefault: true },
        { id: "discount", header: t("Discount"), ...money("discount"), hiddenByDefault: true },
        { id: "tax", header: t("Tax"), ...money("tax"), hiddenByDefault: true },
        { id: "total", header: t("Total"), ...money("total") },
    ];

    return (
        <>
            <Head title={t("Sales Report")} />
            <DataTable
                title={t("Sales Report")}
                description={t("Summary of sales transactions")}
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                searchPlaceholder={t("Search sales...")}
                exportFilename="sales-report"
                summary={(filtered) => (
                    <ReportSummary
                        items={[
                            { label: t("Transactions"), value: String(filtered.length) },
                            { label: t("Subtotal"), value: formatCurrency(sum(filtered, "subtotal")) },
                            { label: t("Discount"), value: formatCurrency(sum(filtered, "discount")) },
                            { label: t("Total sales"), value: formatCurrency(sum(filtered, "total")) },
                        ]}
                    />
                )}
            />
        </>
    );
}

SalesReport.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Sales Report", href: sales(props.currentTeam?.slug ?? "") },
    ],
});
