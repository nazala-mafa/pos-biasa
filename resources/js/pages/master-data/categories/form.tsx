import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataForm from "@/components/master-data-form";
import type { MasterDataField } from "@/components/master-data-form";
import { index, store, update } from "@/routes/master-data/categories";

const fields: MasterDataField[] = [
    { name: "name", label: "Name" },
    {
        name: "description",
        label: "Description",
        type: "textarea",
        required: false,
    },
];

export default function ProductCategoriesForm({
    record,
}: {
    record: ({ id: number } & Record<string, unknown>) | null;
}) {
    const team = useCurrentTeamSlug();

    return (
        <MasterDataForm
            size="md"
            title="Product Categories"
            itemLabel="category"
            record={record}
            fields={fields}
            indexUrl={index(team).url}
            storeForm={store.form(team)}
            updateForm={(id) => update.form([team, id])}
        />
    );
}

ProductCategoriesForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        { title: "Product Categories", href: index(props.currentTeam?.slug ?? "") },
    ],
});
