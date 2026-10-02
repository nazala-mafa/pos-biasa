import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataIndex from "@/components/master-data-index";
import type { MasterDataRow } from "@/components/master-data-index";
import type { DataTableColumn } from "@/components/data-table";
import { create, destroy, edit, index } from "@/routes/master-data/units";
import { useTranslation } from "@/hooks/use-translation";

type Row = MasterDataRow & {
    abbreviation: string;
};

const extraColumns: DataTableColumn<Row>[] = [
    {
        id: "abbreviation",
        header: "Abbreviation",
        searchValue: (row) => String(row.abbreviation),
        cell: (row) => row.abbreviation,
    },
];

export default function UnitsIndex({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <MasterDataIndex
            size="sm"
            title={t("Units")}
            description={t("Units of measure used by products")}
            itemLabel="unit"
            permissionPrefix="units"
            rows={rows}
            extraColumns={extraColumns}
            createUrl={create(team).url}
            editUrl={(id) => edit([team, id]).url}
            destroyUrl={(id) => destroy.url([team, id])}
        />
    );
}

UnitsIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        { title: "Units", href: index(props.currentTeam?.slug ?? "") },
    ],
});
