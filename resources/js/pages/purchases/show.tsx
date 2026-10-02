import { Form, Head, Link, usePage } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";
import InputError from "@/components/input-error";
import PageContainer from "@/components/page-container";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency, formatQuantity } from "@/lib/format";
import { cancel, index, receive } from "@/routes/purchases";
import { useTranslation } from "@/hooks/use-translation";
import { statusVariant } from "./index";

type Props = {
    order: {
        id: number;
        number: string;
        supplier: string;
        user: string | null;
        status: string;
        statusLabel: string;
        isReceivable: boolean;
        canCancel: boolean;
        total: number;
        orderedAt: string;
        expectedAt: string | null;
        note: string | null;
        items: {
            id: number;
            sku: string;
            name: string;
            quantity: number;
            receivedQuantity: number;
            unitCost: number;
            subtotal: number;
        }[];
        receipts: {
            id: number;
            number: string;
            receivedAt: string;
            user: string | null;
            note: string | null;
            quantity: number;
        }[];
    };
};

export default function PurchasesShow({ order }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;
    const can = (permission: string) => auth.permissions.includes(permission);
    const today = new Date().toLocaleDateString("en-CA");
    const showReceiving = order.isReceivable && can("purchases.receive");

    return (
        <>
            <Head title={order.number} />

            <PageContainer size="md" className="space-y-6 p-4">
                <Card className="gap-0 py-0">
                    <CardHeader className="flex flex-row items-start justify-between border-b px-6 py-4">
                        <div className="space-y-1">
                            <CardTitle className="flex items-center gap-2">
                                {order.number}
                                <Badge variant={statusVariant(order.status)}>
                                    {order.statusLabel}
                                </Badge>
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                {t("Supplier")}: {order.supplier} ·{" "}
                                {t("Order date")}: {order.orderedAt}
                                {order.expectedAt
                                    ? ` · ${t("Expected date")}: ${order.expectedAt}`
                                    : ""}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t("Created by")}: {order.user ?? "-"}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            {order.canCancel && can("purchases.cancel") ? (
                                <Form {...cancel.form([team, order.id])}>
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                            data-test="purchase-cancel-button"
                                        >
                                            {t("Cancel order")}
                                        </Button>
                                    )}
                                </Form>
                            ) : null}
                            <Button variant="outline" asChild>
                                <Link href={index(team).url}>
                                    <ArrowLeft /> {t("Purchases")}
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4 p-6">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="py-2 font-medium">{t("Product")}</th>
                                    <th className="py-2 text-right font-medium">{t("Ordered")}</th>
                                    <th className="py-2 text-right font-medium">{t("Received")}</th>
                                    <th className="py-2 text-right font-medium">{t("Unit cost")}</th>
                                    <th className="py-2 text-right font-medium">{t("Subtotal")}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {order.items.map((item) => (
                                    <tr key={item.id} className="border-b">
                                        <td className="py-2">
                                            <div className="font-medium">{item.name}</div>
                                            <div className="text-xs text-muted-foreground">{item.sku}</div>
                                        </td>
                                        <td className="py-2 text-right">{formatQuantity(item.quantity)}</td>
                                        <td className="py-2 text-right">{formatQuantity(item.receivedQuantity)}</td>
                                        <td className="py-2 text-right">{formatCurrency(item.unitCost)}</td>
                                        <td className="py-2 text-right">{formatCurrency(item.subtotal)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="ml-auto flex w-full max-w-xs justify-between border-t pt-1 text-base font-semibold">
                            <span>{t("Total")}</span>
                            <span>{formatCurrency(order.total)}</span>
                        </div>
                        {order.note ? (
                            <p className="text-sm text-muted-foreground">
                                {t("Note")}: {order.note}
                            </p>
                        ) : null}
                    </CardContent>
                </Card>

                {showReceiving ? (
                    <Card className="gap-0 py-0">
                        <CardHeader className="border-b px-6 py-4">
                            <CardTitle>{t("Receive goods")}</CardTitle>
                        </CardHeader>
                        <Form {...receive.form([team, order.id])}>
                            {({ errors, processing }) => (
                                <CardContent className="space-y-4 p-6">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="received_at">{t("Received date")}</Label>
                                            <Input id="received_at" name="received_at" type="date" defaultValue={today} required />
                                            <InputError message={errors.received_at} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="receipt_note">{t("Note")}</Label>
                                            <Input id="receipt_note" name="note" maxLength={255} />
                                            <InputError message={errors.note} />
                                        </div>
                                    </div>
                                    {order.items
                                        .filter((item) => item.quantity > item.receivedQuantity)
                                        .map((item, position) => {
                                            const remaining = item.quantity - item.receivedQuantity;

                                            return (
                                                <div key={item.id} className="grid items-center gap-2 sm:grid-cols-[minmax(0,1fr)_10rem]">
                                                    <div className="text-sm">
                                                        <span className="font-medium">{item.name}</span>{" "}
                                                        <span className="text-muted-foreground">
                                                            ({t("Remaining")}: {formatQuantity(remaining)})
                                                        </span>
                                                    </div>
                                                    <input type="hidden" name={`items[${position}][purchase_order_item_id]`} value={item.id} />
                                                    <Input
                                                        name={`items[${position}][quantity]`}
                                                        aria-label={`${t("Received")} ${item.name}`}
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max={remaining}
                                                        defaultValue={remaining}
                                                        required
                                                    />
                                                </div>
                                            );
                                        })}
                                    <InputError message={errors.items} />
                                    <Button type="submit" disabled={processing} data-test="purchase-receive-button">
                                        {t("Receive goods")}
                                    </Button>
                                </CardContent>
                            )}
                        </Form>
                    </Card>
                ) : null}

                {order.receipts.length > 0 ? (
                    <Card className="gap-0 py-0">
                        <CardHeader className="border-b px-6 py-4">
                            <CardTitle>{t("Goods receipts")}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-6">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 font-medium">{t("Number")}</th>
                                        <th className="py-2 font-medium">{t("Date")}</th>
                                        <th className="py-2 font-medium">{t("Received by")}</th>
                                        <th className="py-2 text-right font-medium">{t("Quantity")}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {order.receipts.map((receipt) => (
                                        <tr key={receipt.id} className="border-b">
                                            <td className="py-2 font-medium">{receipt.number}</td>
                                            <td className="py-2">{receipt.receivedAt}</td>
                                            <td className="py-2">{receipt.user ?? "-"}</td>
                                            <td className="py-2 text-right">{formatQuantity(receipt.quantity)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                ) : null}
            </PageContainer>
        </>
    );
}

PurchasesShow.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Purchases", href: index(props.currentTeam?.slug ?? "") },
    ],
});
