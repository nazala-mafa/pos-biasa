import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataIndex from "@/components/master-data-index";
import type { MasterDataRow } from "@/components/master-data-index";
import type { DataTableColumn } from "@/components/data-table";
import { create, destroy, edit, index } from "@/routes/suppliers";
import { useTranslation } from "@/hooks/use-translation";

type Row = MasterDataRow & {
    contactPerson: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
};

const extraColumns: DataTableColumn<Row>[] = [
    {
        id: "contactPerson",
        header: "Contact person",
        searchValue: (row) => row.contactPerson ?? "",
        cell: (row) => row.contactPerson ?? "-",
    },
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

export default function SuppliersIndex({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <MasterDataIndex
            size="lg"
            title={t("Suppliers")}
            description={t("Suppliers that provide stock to this outlet")}
            itemLabel="supplier"
            permissionPrefix="suppliers"
            rows={rows}
            extraColumns={extraColumns}
            createUrl={create(team).url}
            editUrl={(id) => edit([team, id]).url}
            destroyUrl={(id) => destroy.url([team, id])}
        />
    );
}

SuppliersIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Suppliers", href: index(props.currentTeam?.slug ?? "") },
    ],
});
