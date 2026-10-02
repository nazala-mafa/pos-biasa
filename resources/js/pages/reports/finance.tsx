import { Head } from "@inertiajs/react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import ReportSummary from "@/components/report-summary";
import { useTranslation } from "@/hooks/use-translation";
import { formatCurrency } from "@/lib/format";
import { finance } from "@/routes/reports";

type Row = {
    id: string;
    date: string;
    type: "sale" | "return" | "purchase";
    reference: string;
    party: string | null;
    in: number;
    out: number;
};

export default function FinanceReport({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();

    const typeLabel = (type: Row["type"]) =>
        ({ sale: t("Sale"), return: t("Return"), purchase: t("Purchase") })[type];

    const columns: DataTableColumn<Row>[] = [
        {
            id: "date",
            header: t("Date"),
            dateValue: (row) => row.date,
            exportValue: (row) => row.date,
            cell: (row) => row.date,
        },
        {
            id: "type",
            header: t("Type"),
            searchValue: (row) => typeLabel(row.type),
            cell: (row) => typeLabel(row.type),
        },
        {
            id: "reference",
            header: t("Reference"),
            searchValue: (row) => row.reference,
            cell: (row) => <span className="font-medium">{row.reference}</span>,
        },
        {
            id: "party",
            header: t("Customer / Supplier"),
            searchValue: (row) => row.party ?? "",
            cell: (row) => row.party ?? "-",
        },
        {
            id: "in",
            header: t("Cash in"),
            className: "text-right",
            exportValue: (row) => row.in,
            cell: (row) => (row.in > 0 ? formatCurrency(row.in) : "-"),
        },
        {
            id: "out",
            header: t("Cash out"),
            className: "text-right",
            exportValue: (row) => row.out,
            cell: (row) => (row.out > 0 ? formatCurrency(row.out) : "-"),
        },
    ];

    return (
        <>
            <Head title={t("Financial Report")} />
            <DataTable
                title={t("Financial Report")}
                description={t("Cash in, cash out and payment summary")}
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                searchPlaceholder={t("Search transactions...")}
                exportFilename="financial-report"
                summary={(filtered) => {
                    const cashIn = filtered.reduce((sum, row) => sum + row.in, 0);
                    const cashOut = filtered.reduce((sum, row) => sum + row.out, 0);

                    return (
                        <ReportSummary
                            items={[
                                { label: t("Cash in"), value: formatCurrency(cashIn) },
                                { label: t("Cash out"), value: formatCurrency(cashOut) },
                                { label: t("Net cash"), value: formatCurrency(cashIn - cashOut) },
                            ]}
                        />
                    );
                }}
            />
        </>
    );
}

FinanceReport.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Financial Report", href: finance(props.currentTeam?.slug ?? "") },
    ],
});
