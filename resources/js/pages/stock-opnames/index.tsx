import { Head, Link, usePage } from "@inertiajs/react";
import { Eye, Plus } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatQuantity } from "@/lib/format";
import { create, index, show } from "@/routes/stock-opnames";
import { useTranslation } from "@/hooks/use-translation";

type Row = {
    id: number;
    number: string;
    user: string | null;
    itemsCount: number;
    differenceTotal: number;
    note: string | null;
    countedAt: string;
};

export default function StockOpnamesIndex({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;

    const columns: DataTableColumn<Row>[] = [
        {
            id: "countedAt",
            header: t("Date"),
            dateValue: (row) => row.countedAt,
            cell: (row) => row.countedAt,
        },
        {
            id: "number",
            header: t("Number"),
            searchValue: (row) => row.number,
            cell: (row) => <span className="font-medium">{row.number}</span>,
        },
        {
            id: "user",
            header: t("Counted by"),
            searchValue: (row) => row.user ?? "",
            cell: (row) => row.user ?? "-",
        },
        {
            id: "items",
            header: t("Items"),
            className: "text-right",
            cell: (row) => row.itemsCount,
        },
        {
            id: "difference",
            header: t("Net difference"),
            className: "text-right",
            cell: (row) =>
                `${row.differenceTotal > 0 ? "+" : ""}${formatQuantity(row.differenceTotal)}`,
        },
        {
            id: "note",
            header: t("Note"),
            searchValue: (row) => row.note ?? "",
            cell: (row) => row.note ?? "-",
            hiddenByDefault: true,
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
            <Head title={t("Stock Opname")} />
            <DataTable
                title={t("Stock Opname")}
                description={t("Physical stock counts that correct the book stock")}
                actions={
                    auth.permissions.includes("stock-opnames.create") ? (
                        <Button asChild data-test="stock-opnames-new-button">
                            <Link href={create(team).url}>
                                <Plus /> {t("New stock opname")}
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="stock-opnames-row"
                searchPlaceholder={t("Search stock opnames...")}
            />
        </>
    );
}

StockOpnamesIndex.layout = (props: {
    currentTeam: { slug: string } | null;
}) => ({
    breadcrumbs: [
        { title: "Stock Opname", href: index(props.currentTeam?.slug ?? "") },
    ],
});
