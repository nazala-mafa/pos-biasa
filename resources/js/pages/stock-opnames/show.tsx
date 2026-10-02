import { Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import PageContainer from "@/components/page-container";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatQuantity } from "@/lib/format";
import { index } from "@/routes/stock-opnames";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    opname: {
        id: number;
        number: string;
        user: string | null;
        note: string | null;
        countedAt: string;
        items: {
            id: number;
            sku: string;
            name: string;
            unit: string | null;
            systemQuantity: number;
            countedQuantity: number;
            difference: number;
        }[];
    };
};

export default function StockOpnamesShow({ opname }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <>
            <Head title={opname.number} />

            <PageContainer size="md" className="space-y-6 p-4">
                <Card className="gap-0 py-0">
                    <CardHeader className="flex flex-row items-start justify-between border-b px-6 py-4">
                        <div className="space-y-1">
                            <CardTitle>{opname.number}</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                {opname.countedAt} · {t("Counted by")}: {opname.user ?? "-"}
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={index(team).url}>
                                <ArrowLeft /> {t("Stock Opname")}
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4 p-6">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="py-2 font-medium">{t("Product")}</th>
                                    <th className="py-2 text-right font-medium">{t("System stock")}</th>
                                    <th className="py-2 text-right font-medium">{t("Counted")}</th>
                                    <th className="py-2 text-right font-medium">{t("Difference")}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {opname.items.map((item) => (
                                    <tr key={item.id} className="border-b">
                                        <td className="py-2">
                                            <div className="font-medium">{item.name}</div>
                                            <div className="text-xs text-muted-foreground">{item.sku}</div>
                                        </td>
                                        <td className="py-2 text-right">{formatQuantity(item.systemQuantity)} {item.unit ?? ""}</td>
                                        <td className="py-2 text-right">{formatQuantity(item.countedQuantity)} {item.unit ?? ""}</td>
                                        <td className="py-2 text-right">
                                            {item.difference > 0 ? "+" : ""}
                                            {formatQuantity(item.difference)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {opname.note ? (
                            <p className="text-sm text-muted-foreground">
                                {t("Note")}: {opname.note}
                            </p>
                        ) : null}
                    </CardContent>
                </Card>
            </PageContainer>
        </>
    );
}

StockOpnamesShow.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Stock Opname", href: index(props.currentTeam?.slug ?? "") },
    ],
});
