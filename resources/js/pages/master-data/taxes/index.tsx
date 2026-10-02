import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataIndex from "@/components/master-data-index";
import type { MasterDataRow } from "@/components/master-data-index";
import type { DataTableColumn } from "@/components/data-table";
import { create, destroy, edit, index } from "@/routes/master-data/taxes";

type Row = MasterDataRow & {
    rate: number;
};

const extraColumns: DataTableColumn<Row>[] = [
    {
        id: "rate",
        header: "Rate",
        searchValue: (row) => String(String(row.rate)),
        cell: (row) => `${row.rate}%`,
    },
];

export default function TaxesIndex({ rows }: { rows: Row[] }) {
    const team = useCurrentTeamSlug();

    return (
        <MasterDataIndex
            size="sm"
            title="Taxes"
            description="Tax rates applied to sales"
            itemLabel="tax"
            permissionPrefix="taxes"
            rows={rows}
            extraColumns={extraColumns}
            createUrl={create(team).url}
            editUrl={(id) => edit([team, id]).url}
            destroyUrl={(id) => destroy.url([team, id])}
        />
    );
}

TaxesIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        { title: "Taxes", href: index(props.currentTeam?.slug ?? "") },
    ],
});
