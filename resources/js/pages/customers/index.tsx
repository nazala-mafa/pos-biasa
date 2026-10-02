import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataIndex from "@/components/master-data-index";
import type { MasterDataRow } from "@/components/master-data-index";
import type { DataTableColumn } from "@/components/data-table";
import { create, destroy, edit, index } from "@/routes/customers";

type Row = MasterDataRow & {
    phone: string | null;
    email: string | null;
    address: string | null;
};

const extraColumns: DataTableColumn<Row>[] = [
    {
        id: "phone",
        header: "Phone",
        searchValue: (row) => row.phone ?? "",
        cell: (row) => row.phone ?? "-",
    },
    {
        id: "email",
        header: "Email",
        searchValue: (row) => row.email ?? "",
        cell: (row) => row.email ?? "-",
    },
    {
        id: "address",
        header: "Address",
        searchValue: (row) => row.address ?? "",
        cell: (row) => row.address ?? "-",
        hiddenByDefault: true,
    },
];

export default function CustomersIndex({ rows }: { rows: Row[] }) {
    const team = useCurrentTeamSlug();

    return (
        <MasterDataIndex
            size="lg"
            title="Customers"
            description="Customers who buy from this outlet"
            itemLabel="customer"
            permissionPrefix="customers"
            rows={rows}
            extraColumns={extraColumns}
            createUrl={create(team).url}
            editUrl={(id) => edit([team, id]).url}
            destroyUrl={(id) => destroy.url([team, id])}
        />
    );
}

CustomersIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Customers", href: index(props.currentTeam?.slug ?? "") },
    ],
});
