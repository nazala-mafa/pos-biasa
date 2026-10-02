import { Head } from "@inertiajs/react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import ReportSummary from "@/components/report-summary";
import { useTranslation } from "@/hooks/use-translation";
import { formatCurrency } from "@/lib/format";
import { profitLoss } from "@/routes/reports";

type Row = {
    date: string;
    revenue: number;
    returns: number;
    cost: number;
    profit: number;
};

type Key = "revenue" | "returns" | "cost" | "profit";

const sum = (rows: Row[], key: Key) =>
    rows.reduce((total, row) => total + row[key], 0);

export default function ProfitLossReport({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();

    const money = (id: Key, header: string): DataTableColumn<Row> => ({
        id,
        header,
        className: "text-right",
        exportValue: (row) => row[id],
        cell: (row) => formatCurrency(row[id]),
    });

    const columns: DataTableColumn<Row>[] = [
        {
            id: "date",
            header: t("Date"),
            searchValue: (row) => row.date,
            dateValue: (row) => row.date,
            exportValue: (row) => row.date,
            cell: (row) => row.date,
        },
        money("revenue", t("Net sales")),
        money("returns", t("Returns")),
        money("cost", t("Cost of goods")),
        {
            ...money("profit", t("Profit/Loss")),
            cell: (row) => (
                <span
                    className={
                        row.profit < 0 ? "font-medium text-destructive" : "font-medium"
                    }
                >
                    {formatCurrency(row.profit)}
                </span>
            ),
        },
    ];

    return (
        <>
            <Head title={t("Profit/Loss Report")} />
            <DataTable
                title={t("Profit/Loss Report")}
                description={t("Revenue, cost and profit summary")}
                columns={columns}
                rows={rows}
                rowKey={(row) => row.date}
                searchPlaceholder={t("Search date...")}
                exportFilename="profit-loss-report"
                summary={(filtered) => (
                    <ReportSummary
                        items={[
                            { label: t("Net sales"), value: formatCurrency(sum(filtered, "revenue")) },
                            { label: t("Returns"), value: formatCurrency(sum(filtered, "returns")) },
                            { label: t("Cost of goods"), value: formatCurrency(sum(filtered, "cost")) },
                            { label: t("Profit/Loss"), value: formatCurrency(sum(filtered, "profit")) },
                        ]}
                    />
                )}
            />
        </>
    );
}

ProfitLossReport.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Profit/Loss Report", href: profitLoss(props.currentTeam?.slug ?? "") },
    ],
});
