import { ImageIcon } from "lucide-react";
import MasterDataIndex from "@/components/master-data-index";
import type { MasterDataRow } from "@/components/master-data-index";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency } from "@/lib/format";
import { create, destroy, edit, index } from "@/routes/products";

type Row = MasterDataRow & {
    photoUrl: string | null;
    sku: string;
    category: string | null;
    unit: string | null;
    tax: string | null;
    costPrice: number;
    sellingPrice: number;
    isActive: boolean;
};

const extraColumns: DataTableColumn<Row>[] = [
    {
        id: "photo",
        header: "Photo",
        cell: (row) =>
            row.photoUrl ? (
                <img
                    src={row.photoUrl}
                    alt={row.name}
                    className="h-10 w-10 rounded-md object-cover"
                />
            ) : (
                <div className="flex h-10 w-10 items-center justify-center rounded-md bg-muted">
                    <ImageIcon className="h-4 w-4 text-muted-foreground" />
                </div>
            ),
    },
    {
        id: "sku",
        header: "SKU",
        searchValue: (row) => row.sku,
        cell: (row) => row.sku,
    },
    {
        id: "category",
        header: "Category",
        searchValue: (row) => row.category ?? "",
        cell: (row) => row.category ?? "-",
    },
    {
        id: "unit",
        header: "Unit",
        cell: (row) => row.unit ?? "-",
        hiddenByDefault: true,
    },
    {
        id: "tax",
        header: "Tax",
        cell: (row) => row.tax ?? "-",
        hiddenByDefault: true,
    },
    {
        id: "costPrice",
        header: "Cost price",
        className: "text-right",
        cell: (row) => formatCurrency(row.costPrice),
        hiddenByDefault: true,
    },
    {
        id: "sellingPrice",
        header: "Selling price",
        className: "text-right",
        searchValue: (row) => String(row.sellingPrice),
        cell: (row) => formatCurrency(row.sellingPrice),
    },
    {
        id: "status",
        header: "Status",
        cell: (row) => (
            <Badge variant={row.isActive ? "default" : "secondary"}>
                {row.isActive ? "Active" : "Inactive"}
            </Badge>
        ),
    },
];

export default function ProductsIndex({ rows }: { rows: Row[] }) {
    const team = useCurrentTeamSlug();

    return (
        <MasterDataIndex
            size="lg"
            title="Products"
            description="Items sold by this outlet"
            itemLabel="product"
            permissionPrefix="products"
            rows={rows}
            extraColumns={extraColumns}
            createUrl={create(team).url}
            editUrl={(id) => edit([team, id]).url}
            destroyUrl={(id) => destroy.url([team, id])}
        />
    );
}

ProductsIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Products", href: index(props.currentTeam?.slug ?? "") },
    ],
});
