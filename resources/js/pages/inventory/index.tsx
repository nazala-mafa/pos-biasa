import { Head, Link, usePage } from "@inertiajs/react";
import { History, Plus } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { T } from "@/components/trans";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatQuantity } from "@/lib/format";
import { create, index, movements } from "@/routes/inventory";
import { useTranslation } from "@/hooks/use-translation";

type Row = {
    id: number;
    sku: string;
    name: string;
    unit: string | null;
    stock: number;
    minStock: number;
    isActive: boolean;
    createdAt: string | null;
};

const columns: DataTableColumn<Row>[] = [
    {
        id: "sku",
        header: "SKU",
        searchValue: (row) => row.sku,
        cell: (row) => row.sku,
    },
    {
        id: "name",
        header: "Product",
        searchValue: (row) => row.name,
        cell: (row) => <span className="font-medium">{row.name}</span>,
    },
    {
        id: "stock",
        header: "Stock",
        className: "text-right",
        cell: (row) => `${formatQuantity(row.stock)} ${row.unit ?? ""}`,
    },
    {
        id: "minStock",
        header: "Minimum stock",
        className: "text-right",
        cell: (row) => formatQuantity(row.minStock),
        hiddenByDefault: true,
    },
    {
        id: "status",
        header: "Status",
        cell: (row) =>
            row.stock <= 0 ? (
                <Badge variant="destructive"><T text="Out of stock" /></Badge>
            ) : row.stock <= row.minStock ? (
                <Badge variant="outline"><T text="Low stock" /></Badge>
            ) : (
                <Badge variant="secondary"><T text="In stock" /></Badge>
            ),
    },
    {
        id: "createdAt",
        header: "Created",
        dateValue: (row) => row.createdAt,
        cell: (row) => row.createdAt ?? "-",
        hiddenByDefault: true,
    },
];

export default function InventoryIndex({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;

    return (
        <>
            <Head title={t("Inventory")} />
            <DataTable
                title={t("Inventory")}
                description={t("Current stock level of every product")}
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={movements(team).url}>
                                <History /> {t("History")}
                            </Link>
                        </Button>
                        {auth.permissions.includes("inventory.adjust") ? (
                            <Button asChild data-test="inventory-new-button">
                                <Link href={create(team).url}>
                                    <Plus /> {t("New stock movement")}
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="inventory-row"
                searchPlaceholder={t("Search products...")}
            />
        </>
    );
}

InventoryIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Inventory", href: index(props.currentTeam?.slug ?? "") },
    ],
});
