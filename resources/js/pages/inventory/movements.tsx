import { Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatQuantity } from "@/lib/format";
import { index, movements } from "@/routes/inventory";

type Row = {
    id: number;
    product: string;
    sku: string;
    type: "in" | "out" | "adjustment";
    typeLabel: string;
    quantity: number;
    supplier: string | null;
    user: string | null;
    note: string | null;
    createdAt: string | null;
};

const columns: DataTableColumn<Row>[] = [
    {
        id: "createdAt",
        header: "Date",
        dateValue: (row) => row.createdAt,
        cell: (row) => row.createdAt ?? "-",
    },
    {
        id: "product",
        header: "Product",
        searchValue: (row) => `${row.sku} ${row.product}`,
        cell: (row) => <span className="font-medium">{row.product}</span>,
    },
    {
        id: "type",
        header: "Type",
        searchValue: (row) => row.typeLabel,
        cell: (row) => (
            <Badge variant={row.type === "out" ? "destructive" : "secondary"}>
                {row.typeLabel}
            </Badge>
        ),
    },
    {
        id: "quantity",
        header: "Quantity",
        className: "text-right",
        cell: (row) =>
            `${row.quantity > 0 ? "+" : ""}${formatQuantity(row.quantity)}`,
    },
    {
        id: "supplier",
        header: "Supplier",
        searchValue: (row) => row.supplier ?? "",
        cell: (row) => row.supplier ?? "-",
    },
    {
        id: "user",
        header: "By",
        cell: (row) => row.user ?? "-",
        hiddenByDefault: true,
    },
    {
        id: "note",
        header: "Note",
        searchValue: (row) => row.note ?? "",
        cell: (row) => row.note ?? "-",
    },
];

export default function InventoryMovements({ rows }: { rows: Row[] }) {
    const team = useCurrentTeamSlug();

    return (
        <>
            <Head title="Stock history" />
            <DataTable
                title="Stock history"
                description="Every stock in, stock out and adjustment"
                actions={
                    <Button variant="outline" asChild>
                        <Link href={index(team).url}>
                            <ArrowLeft /> Inventory
                        </Link>
                    </Button>
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId="stock-movement-row"
                searchPlaceholder="Search movements..."
            />
        </>
    );
}

InventoryMovements.layout = (props: {
    currentTeam: { slug: string } | null;
}) => ({
    breadcrumbs: [
        { title: "Inventory", href: index(props.currentTeam?.slug ?? "") },
        {
            title: "History",
            href: movements(props.currentTeam?.slug ?? ""),
        },
    ],
});
