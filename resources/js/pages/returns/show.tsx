import { Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import PageContainer from "@/components/page-container";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency, formatQuantity } from "@/lib/format";
import { index } from "@/routes/returns";
import { show as showSale } from "@/routes/sales";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    saleReturn: {
        id: number;
        number: string;
        saleId: number;
        invoiceNumber: string;
        user: string | null;
        reason: string | null;
        total: number;
        createdAt: string | null;
        items: {
            id: number;
            sku: string;
            name: string;
            quantity: number;
            amount: number;
        }[];
    };
};

export default function ReturnsShow({ saleReturn }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <>
            <Head title={saleReturn.number} />

            <PageContainer size="md" className="space-y-6 p-4">
                <Card className="gap-0 py-0">
                    <CardHeader className="flex flex-row items-start justify-between border-b px-6 py-4">
                        <div className="space-y-1">
                            <CardTitle>{saleReturn.number}</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                {saleReturn.createdAt} · {t("Invoice")}:{" "}
                                <Link
                                    className="underline"
                                    href={showSale([team, saleReturn.saleId]).url}
                                >
                                    {saleReturn.invoiceNumber}
                                </Link>
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t("Processed by")}: {saleReturn.user ?? "-"}
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={index(team).url}>
                                <ArrowLeft /> {t("Returns")}
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4 p-6">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="py-2 font-medium">{t("Product")}</th>
                                    <th className="py-2 text-right font-medium">{t("Qty")}</th>
                                    <th className="py-2 text-right font-medium">{t("Refund")}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {saleReturn.items.map((item) => (
                                    <tr key={item.id} className="border-b">
                                        <td className="py-2">
                                            <div className="font-medium">{item.name}</div>
                                            <div className="text-xs text-muted-foreground">{item.sku}</div>
                                        </td>
                                        <td className="py-2 text-right">{formatQuantity(item.quantity)}</td>
                                        <td className="py-2 text-right">{formatCurrency(item.amount)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="ml-auto flex w-full max-w-xs justify-between border-t pt-1 text-base font-semibold">
                            <span>{t("Refund")}</span>
                            <span>{formatCurrency(saleReturn.total)}</span>
                        </div>
                        {saleReturn.reason ? (
                            <p className="text-sm text-muted-foreground">
                                {t("Reason")}: {saleReturn.reason}
                            </p>
                        ) : null}
                    </CardContent>
                </Card>
            </PageContainer>
        </>
    );
}

ReturnsShow.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Returns", href: index(props.currentTeam?.slug ?? "") },
    ],
});
