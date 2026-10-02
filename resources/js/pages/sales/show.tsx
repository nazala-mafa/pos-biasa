import { Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import PageContainer from "@/components/page-container";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency, formatQuantity } from "@/lib/format";
import { index } from "@/routes/sales";

type Props = {
    sale: {
        id: number;
        invoiceNumber: string;
        customer: string | null;
        user: string | null;
        paymentLabel: string;
        subtotal: number;
        discount: number;
        taxTotal: number;
        total: number;
        paid: number;
        change: number;
        note: string | null;
        createdAt: string | null;
        items: {
            id: number;
            sku: string;
            name: string;
            quantity: number;
            unitPrice: number;
            taxAmount: number;
            subtotal: number;
        }[];
    };
};

export default function SalesShow({ sale }: Props) {
    const team = useCurrentTeamSlug();

    const summary: [string, string, boolean?][] = [
        ["Subtotal", formatCurrency(sale.subtotal)],
        ["Tax", formatCurrency(sale.taxTotal)],
        ["Discount", `- ${formatCurrency(sale.discount)}`],
        ["Total", formatCurrency(sale.total), true],
        ["Paid", formatCurrency(sale.paid)],
        ["Change", formatCurrency(sale.change)],
    ];

    return (
        <>
            <Head title={sale.invoiceNumber} />

            <PageContainer size="md" className="space-y-6 p-4">
                <Card className="gap-0 py-0">
                    <CardHeader className="flex flex-row items-start justify-between border-b px-6 py-4">
                        <div className="space-y-1">
                            <CardTitle>{sale.invoiceNumber}</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                {sale.createdAt} · {sale.paymentLabel}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Customer: {sale.customer ?? "-"} · Cashier:{" "}
                                {sale.user ?? "-"}
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={index(team).url}>
                                <ArrowLeft /> Sales
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-6 p-6">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="py-2 font-medium">
                                        Product
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Qty
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Price
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {sale.items.map((item) => (
                                    <tr key={item.id} className="border-b">
                                        <td className="py-2">
                                            <div className="font-medium">
                                                {item.name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {item.sku}
                                            </div>
                                        </td>
                                        <td className="py-2 text-right">
                                            {formatQuantity(item.quantity)}
                                        </td>
                                        <td className="py-2 text-right">
                                            {formatCurrency(item.unitPrice)}
                                        </td>
                                        <td className="py-2 text-right">
                                            {formatCurrency(item.subtotal)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <dl className="ml-auto w-full max-w-xs space-y-1 text-sm">
                            {summary.map(([label, value, strong]) => (
                                <div
                                    key={label}
                                    className={`flex justify-between ${strong ? "border-t pt-1 text-base font-semibold" : ""}`}
                                >
                                    <dt>{label}</dt>
                                    <dd>{value}</dd>
                                </div>
                            ))}
                        </dl>

                        {sale.note ? (
                            <p className="text-sm text-muted-foreground">
                                Note: {sale.note}
                            </p>
                        ) : null}
                    </CardContent>
                </Card>
            </PageContainer>
        </>
    );
}

SalesShow.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Sales", href: index(props.currentTeam?.slug ?? "") },
    ],
});
