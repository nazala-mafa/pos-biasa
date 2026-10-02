import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataIndex from "@/components/master-data-index";
import type { MasterDataRow } from "@/components/master-data-index";
import type { DataTableColumn } from "@/components/data-table";
import { create, destroy, edit, index } from "@/routes/master-data/prices";

type Row = MasterDataRow & {
    description: string | null;
};

const extraColumns: DataTableColumn<Row>[] = [
    {
        id: "description",
        header: "Description",
        searchValue: (row) => String(row.description ?? ""),
        cell: (row) => row.description ?? "-",
    },
];

export default function PricesIndex({ rows }: { rows: Row[] }) {
    const team = useCurrentTeamSlug();

    return (
        <MasterDataIndex
            size="md"
            title="Prices"
            description="Price levels such as retail or wholesale"
            itemLabel="price"
            permissionPrefix="prices"
            rows={rows}
            extraColumns={extraColumns}
            createUrl={create(team).url}
            editUrl={(id) => edit([team, id]).url}
            destroyUrl={(id) => destroy.url([team, id])}
        />
    );
}

PricesIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        { title: "Prices", href: index(props.currentTeam?.slug ?? "") },
    ],
});
