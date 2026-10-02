import { Form } from "@inertiajs/react";
import { useState } from "react";
import FormLayout from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatQuantity } from "@/lib/format";
import { index, store } from "@/routes/stock-opnames";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    products: {
        id: number;
        sku: string;
        name: string;
        unit: string | null;
        stock: number;
    }[];
};

export default function StockOpnamesForm({ products }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const today = new Date().toLocaleDateString("en-CA");
    const [counts, setCounts] = useState<Record<number, string>>({});
    const [search, setSearch] = useState("");

    const visible = products.filter((product) =>
        `${product.sku} ${product.name}`
            .toLowerCase()
            .includes(search.toLowerCase()),
    );
    const counted = products.filter((product) => counts[product.id] !== undefined && counts[product.id] !== "");

    return (
        <Form {...store.form(team)}>
            {({ errors, processing }) => (
                <FormLayout
                    size="lg"
                    title={t("New stock opname")}
                    description={t("Enter the counted quantity of each product; leave blank to skip a product")}
                    processing={processing}
                    cancelHref={index(team).url}
                    saveTestId="stock-opname-save-button"
                    actionFields={
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="counted_at">{t("Date")}</Label>
                                <Input id="counted_at" name="counted_at" type="date" defaultValue={today} required />
                                <InputError message={errors.counted_at} />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {t(":count products counted", { count: counted.length })}
                            </p>
                        </div>
                    }
                >
                    <Input
                        type="search"
                        placeholder={t("Search products...")}
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />

                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th className="py-2 font-medium">{t("Product")}</th>
                                <th className="py-2 text-right font-medium">{t("System stock")}</th>
                                <th className="w-36 py-2 text-right font-medium">{t("Counted")}</th>
                                <th className="py-2 text-right font-medium">{t("Difference")}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {visible.map((product) => {
                                const value = counts[product.id] ?? "";
                                const difference = value === "" ? null : Number(value) - product.stock;

                                return (
                                    <tr key={product.id} className="border-b">
                                        <td className="py-2">
                                            <div className="font-medium">{product.name}</div>
                                            <div className="text-xs text-muted-foreground">{product.sku}</div>
                                        </td>
                                        <td className="py-2 text-right">
                                            {formatQuantity(product.stock)} {product.unit ?? ""}
                                        </td>
                                        <td className="py-2">
                                            <Input
                                                aria-label={`${t("Counted")} ${product.name}`}
                                                className="text-right"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={value}
                                                onChange={(event) =>
                                                    setCounts((current) => ({
                                                        ...current,
                                                        [product.id]: event.target.value,
                                                    }))
                                                }
                                            />
                                        </td>
                                        <td className="py-2 text-right">
                                            {difference === null
                                                ? "-"
                                                : `${difference > 0 ? "+" : ""}${formatQuantity(difference)}`}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>

                    {counted.map((product, position) => (
                        <div key={product.id}>
                            <input type="hidden" name={`items[${position}][product_id]`} value={product.id} />
                            <input type="hidden" name={`items[${position}][counted_quantity]`} value={counts[product.id]} />
                            <InputError message={errors[`items.${position}.counted_quantity`]} />
                        </div>
                    ))}
                    <InputError message={errors.items} />

                    <div className="grid gap-2">
                        <Label htmlFor="note">{t("Note")}</Label>
                        <Input id="note" name="note" maxLength={255} />
                        <InputError message={errors.note} />
                    </div>
                </FormLayout>
            )}
        </Form>
    );
}

StockOpnamesForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Stock Opname", href: index(props.currentTeam?.slug ?? "") },
    ],
});
