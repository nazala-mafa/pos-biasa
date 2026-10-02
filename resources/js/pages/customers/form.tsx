import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataForm from "@/components/master-data-form";
import type { MasterDataField } from "@/components/master-data-form";
import { index, store, update } from "@/routes/customers";

const fields: MasterDataField[] = [
    { name: "name", label: "Name" },
    { name: "phone", label: "Phone", required: false },
    { name: "email", label: "Email", type: "email", required: false },
    { name: "address", label: "Address", type: "textarea", required: false },
];

export default function CustomersForm({
    record,
}: {
    record: ({ id: number } & Record<string, unknown>) | null;
}) {
    const team = useCurrentTeamSlug();

    return (
        <MasterDataForm
            size="md"
            title="Customers"
            itemLabel="customer"
            record={record}
            fields={fields}
            indexUrl={index(team).url}
            storeForm={store.form(team)}
            updateForm={(id) => update.form([team, id])}
        />
    );
}

CustomersForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Customers", href: index(props.currentTeam?.slug ?? "") },
    ],
});
