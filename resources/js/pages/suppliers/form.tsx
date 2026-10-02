import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataForm from "@/components/master-data-form";
import type { MasterDataField } from "@/components/master-data-form";
import { index, store, update } from "@/routes/suppliers";

const fields: MasterDataField[] = [
    { name: "name", label: "Name" },
    { name: "contact_person", label: "Contact person", required: false },
    { name: "phone", label: "Phone", required: false },
    { name: "email", label: "Email", type: "email", required: false },
    { name: "address", label: "Address", type: "textarea", required: false },
];

export default function SuppliersForm({
    record,
}: {
    record: ({ id: number } & Record<string, unknown>) | null;
}) {
    const team = useCurrentTeamSlug();

    return (
        <MasterDataForm
            size="md"
            title="Suppliers"
            itemLabel="supplier"
            record={record}
            fields={fields}
            indexUrl={index(team).url}
            storeForm={store.form(team)}
            updateForm={(id) => update.form([team, id])}
        />
    );
}

SuppliersForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Suppliers", href: index(props.currentTeam?.slug ?? "") },
    ],
});
