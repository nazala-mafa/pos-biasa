import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataForm from "@/components/master-data-form";
import type { MasterDataField } from "@/components/master-data-form";
import { index, store, update } from "@/routes/master-data/taxes";

const fields: MasterDataField[] = [
    { name: "name", label: "Name" },
    { name: "rate", label: "Rate (%)", type: "number", step: "0.01" },
];

export default function TaxesForm({
    record,
}: {
    record: ({ id: number } & Record<string, unknown>) | null;
}) {
    const team = useCurrentTeamSlug();

    return (
        <MasterDataForm
            title="Taxes"
            itemLabel="tax"
            record={record}
            fields={fields}
            indexUrl={index(team).url}
            storeForm={store.form(team)}
            updateForm={(id) => update.form([team, id])}
        />
    );
}

TaxesForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        { title: "Taxes", href: index(props.currentTeam?.slug ?? "") },
    ],
});
