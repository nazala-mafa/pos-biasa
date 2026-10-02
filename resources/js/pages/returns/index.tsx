import { Head, Link, usePage } from "@inertiajs/react";
import { Eye, Plus } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency } from "@/lib/format";
import { create, index, show } from "@/routes/returns";
import { useTranslation } from "@/hooks/use-translation";

type Row = {
    id: number;
    number: string;
    invoiceNumber: string;
    user: string | null;
    reason: string | null;
    itemsCount: number;
    total: number;
    createdAt: string | null;
};

export default function ReturnsIndex({ rows }: { rows: Row[] }) {
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
            id: "number",
            header: t("Number"),
            searchValue: (row) => row.number,
            cell: (row) => <span className="font-medium">{row.number}</span>,
        },
        {
            id: "invoice",
            header: t("Invoice"),
            searchValue: (row) => row.invoiceNumber,
            cell: (row) => row.invoiceNumber,
        },
        {
            id: "reason",
            header: t("Reason"),
            searchValue: (row) => row.reason ?? "",
            cell: (row) => row.reason ?? "-",
        },
        {
            id: "user",
            header: t("Processed by"),
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
            id: "total",
            header: t("Refund"),
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
            <Head title={t("Returns")} />
            <DataTable
                title={t("Returns")}
                description={t("Goods customers brought back from earlier sales")}
                actions={
                    auth.permissions.includes("returns.create") ? (
                        <Button asChild data-test="returns-new-button">
                            <Link href={create(team).url}>
                                <Plus /> {t("New return")}
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="returns-row"
                searchPlaceholder={t("Search returns...")}
            />
        </>
    );
}

ReturnsIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Returns", href: index(props.currentTeam?.slug ?? "") },
    ],
});
