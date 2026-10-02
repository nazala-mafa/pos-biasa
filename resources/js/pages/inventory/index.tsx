import { Head, Link, usePage } from "@inertiajs/react";
import { History, Plus } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatQuantity } from "@/lib/format";
import { create, index, movements } from "@/routes/inventory";

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
                <Badge variant="destructive">Out of stock</Badge>
            ) : row.stock <= row.minStock ? (
                <Badge variant="outline">Low stock</Badge>
            ) : (
                <Badge variant="secondary">In stock</Badge>
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
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Inventory" />
            <DataTable
                title="Inventory"
                description="Current stock level of every product"
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={movements(team).url}>
                                <History /> History
                            </Link>
                        </Button>
                        {auth.permissions.includes("inventory.adjust") ? (
                            <Button asChild data-test="inventory-new-button">
                                <Link href={create(team).url}>
                                    <Plus /> New stock movement
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="inventory-row"
                searchPlaceholder="Search products..."
            />
        </>
    );
}

InventoryIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Inventory", href: index(props.currentTeam?.slug ?? "") },
    ],
});
