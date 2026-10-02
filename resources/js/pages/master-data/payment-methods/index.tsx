import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import MasterDataIndex from "@/components/master-data-index";
import type { MasterDataRow } from "@/components/master-data-index";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import {
    create,
    destroy,
    edit,
    index,
} from "@/routes/master-data/payment-methods";
import { useTranslation } from "@/hooks/use-translation";

type Row = MasterDataRow & {
    isCash: boolean;
    isActive: boolean;
};

export default function PaymentMethodsIndex({ rows }: { rows: Row[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    const extraColumns: DataTableColumn<Row>[] = [
        {
            id: "isCash",
            header: t("Type"),
            searchValue: (row) => (row.isCash ? t("Cash") : t("Non-cash")),
            cell: (row) => (row.isCash ? t("Cash") : t("Non-cash")),
        },
        {
            id: "isActive",
            header: t("Status"),
            searchValue: (row) => (row.isActive ? t("Active") : t("Inactive")),
            cell: (row) => (
                <Badge variant={row.isActive ? "default" : "secondary"}>
                    {row.isActive ? t("Active") : t("Inactive")}
                </Badge>
            ),
        },
    ];

    return (
        <MasterDataIndex
            size="sm"
            title={t("Payment Methods")}
            description={t("Ways customers can pay at the cashier")}
            itemLabel="payment method"
            permissionPrefix="payment-methods"
            rows={rows}
            extraColumns={extraColumns}
            createUrl={create(team).url}
            editUrl={(id) => edit([team, id]).url}
            destroyUrl={(id) => destroy.url([team, id])}
        />
    );
}

PaymentMethodsIndex.layout = (props: {
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
