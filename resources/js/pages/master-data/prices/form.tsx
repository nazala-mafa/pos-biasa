import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataForm from "@/components/master-data-form";
import type { MasterDataField } from "@/components/master-data-form";
import { index, store, update } from "@/routes/master-data/prices";
import { useTranslation } from "@/hooks/use-translation";

const fields: MasterDataField[] = [
    { name: "name", label: "Name" },
    {
        name: "description",
        label: "Description",
        type: "textarea",
        required: false,
    },
];

export default function PricesForm({
    record,
}: {
    record: ({ id: number } & Record<string, unknown>) | null;
}) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <MasterDataForm
            size="md"
            title={t("Prices")}
            itemLabel="price"
            record={record}
            fields={fields}
            indexUrl={index(team).url}
            storeForm={store.form(team)}
            updateForm={(id) => update.form([team, id])}
        />
    );
}

PricesForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        { title: "Prices", href: index(props.currentTeam?.slug ?? "") },
    ],
});
