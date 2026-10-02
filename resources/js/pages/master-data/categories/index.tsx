import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataIndex from "@/components/master-data-index";
import type { MasterDataRow } from "@/components/master-data-index";
import type { DataTableColumn } from "@/components/data-table";
import { create, destroy, edit, index } from "@/routes/master-data/categories";
import { useTranslation } from "@/hooks/use-translation";

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

export default function ProductCategoriesIndex({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <MasterDataIndex
            size="md"
            title={t("Product Categories")}
            description={t("Group your products into categories")}
            itemLabel="category"
            permissionPrefix="categories"
            rows={rows}
            extraColumns={extraColumns}
            createUrl={create(team).url}
            editUrl={(id) => edit([team, id]).url}
            destroyUrl={(id) => destroy.url([team, id])}
        />
    );
}

ProductCategoriesIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        { title: "Product Categories", href: index(props.currentTeam?.slug ?? "") },
    ],
});
