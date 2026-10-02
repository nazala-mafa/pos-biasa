import { Form, router } from "@inertiajs/react";
import FormLayout from "@/components/form-layout";
import InputError from "@/components/input-error";
import NativeSelect from "@/components/native-select";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency, formatQuantity } from "@/lib/format";
import { create, index, store } from "@/routes/returns";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    sales: { id: number; label: string }[];
    sale: {
        id: number;
        invoiceNumber: string;
        lines: {
            id: number;
            sku: string;
            name: string;
            sold: number;
            returned: number;
            returnable: number;
            unitPrice: number;
        }[];
    } | null;
};

export default function ReturnsForm({ sales, sale }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    const chooseSale = (saleId: string) =>
        router.get(
            create.url(team, { query: saleId ? { sale_id: saleId } : {} }),
            {},
            { preserveScroll: true },
        );

    return (
        <Form {...store.form(team)} key={sale?.id ?? "none"}>
            {({ errors, processing }) => (
                <FormLayout
                    size="md"
                    title={t("New return")}
                    description={t("Choose the sale and enter what comes back")}
                    processing={processing || !sale}
                    cancelHref={index(team).url}
                    saveTestId="return-save-button"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="sale_select">{t("Sale")}</Label>
                        <NativeSelect
                            id="sale_select"
                            value={sale?.id ? String(sale.id) : ""}
                            onChange={(event) => chooseSale(event.target.value)}
                        >
                            <option value="">{t("Select a sale")}</option>
                            {sales.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.label}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={errors.sale_id} />
                    </div>

                    {sale ? (
                        <>
                            <input type="hidden" name="sale_id" value={sale.id} />
                            <div className="space-y-3">
                                <Label>{t("Items")}</Label>
                                {sale.lines
                                    .filter((line) => line.returnable > 0)
                                    .map((line, position) => (
                                    <div
                                        key={line.id}
                                        className="grid items-center gap-2 sm:grid-cols-[minmax(0,1fr)_9rem]"
                                    >
                                        <div className="text-sm">
                                            <div className="font-medium">{line.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {line.sku} · {t("Sold")}: {formatQuantity(line.sold)} ·{" "}
                                                {t("Returned")}: {formatQuantity(line.returned)} ·{" "}
                                                {formatCurrency(line.unitPrice)}
                                            </div>
                                        </div>
                                        <input type="hidden" name={`items[${position}][sale_item_id]`} value={line.id} />
                                        <div className="grid gap-1">
                                            <Input
                                                name={`items[${position}][quantity]`}
                                                aria-label={`${t("Returned")} ${line.name}`}
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max={line.returnable}
                                                defaultValue="0"
                                            />
                                            <InputError message={errors[`items.${position}.quantity`]} />
                                        </div>
                                    </div>
                                ))}
                                <InputError message={errors.items} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reason">{t("Reason")}</Label>
                                <Input id="reason" name="reason" maxLength={255} />
                                <InputError message={errors.reason} />
                            </div>
                        </>
                    ) : null}
                </FormLayout>
            )}
        </Form>
    );
}

ReturnsForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Returns", href: index(props.currentTeam?.slug ?? "") },
    ],
});
