import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataForm from "@/components/master-data-form";
import type { MasterDataField } from "@/components/master-data-form";
import { index, store, update } from "@/routes/master-data/payment-methods";
import { useTranslation } from "@/hooks/use-translation";

const fields: MasterDataField[] = [
    { name: "name", label: "Name" },
    { name: "is_cash", label: "Cash payment (counted in the cash drawer)", type: "checkbox" },
    { name: "is_active", label: "Active", type: "checkbox" },
];

export default function PaymentMethodsForm({
    record,
}: {
    record: ({ id: number } & Record<string, unknown>) | null;
}) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <MasterDataForm
            size="sm"
            title={t("Payment Methods")}
            itemLabel="payment method"
            record={
                record
                    ? {
                          ...record,
                          is_cash: record.isCash,
                          is_active: record.isActive,
                      }
                    : null
            }
            fields={fields}
            indexUrl={index(team).url}
            storeForm={store.form(team)}
            updateForm={(id) => update.form([team, id])}
        />
    );
}

PaymentMethodsForm.layout = (props: {
    currentTeam: { slug: string } | null;
}) => ({
    breadcrumbs: [
        { title: "Master Data", href: index(props.currentTeam?.slug ?? "") },
        {
            title: "Payment Methods",
            href: index(props.currentTeam?.slug ?? ""),
        },
    ],
});
