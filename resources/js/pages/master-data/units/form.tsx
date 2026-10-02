import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataForm from "@/components/master-data-form";
import type { MasterDataField } from "@/components/master-data-form";
import { index, store, update } from "@/routes/master-data/units";
import { useTranslation } from "@/hooks/use-translation";

const fields: MasterDataField[] = [
    { name: "name", label: "Name" },
    { name: "abbreviation", label: "Abbreviation" },
];

export default function UnitsForm({
    record,
}: {
    record: ({ id: number } & Record<string, unknown>) | null;
}) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <MasterDataForm
            size="sm"
            title={t("Units")}
            itemLabel="unit"
            record={record}
            fields={fields}
            indexUrl={index(team).url}
            storeForm={store.form(team)}
            updateForm={(id) => update.form([team, id])}
        />
    );
}

UnitsForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        { title: "Units", href: index(props.currentTeam?.slug ?? "") },
    ],
});
