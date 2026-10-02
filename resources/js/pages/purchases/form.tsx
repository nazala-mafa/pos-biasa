import { Form } from "@inertiajs/react";
import { Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import FormLayout, { SidebarCard } from "@/components/form-layout";
import InputError from "@/components/input-error";
import NativeSelect from "@/components/native-select";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency } from "@/lib/format";
import { index, store } from "@/routes/purchases";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    suppliers: { id: number; name: string }[];
    products: {
        id: number;
        label: string;
        unit: string | null;
        costPrice: number;
    }[];
};

type Line = { productId: string; quantity: string; unitCost: string };

const emptyLine: Line = { productId: "", quantity: "1", unitCost: "" };

export default function PurchasesForm({ suppliers, products }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const [lines, setLines] = useState<Line[]>([{ ...emptyLine }]);
    const today = new Date().toLocaleDateString("en-CA");

    const updateLine = (position: number, changes: Partial<Line>) =>
        setLines((current) =>
            current.map((line, i) =>
                i === position ? { ...line, ...changes } : line,
            ),
        );

    const pickProduct = (position: number, productId: string) => {
        const product = products.find((item) => String(item.id) === productId);
        updateLine(position, {
            productId,
            unitCost: product ? String(product.costPrice) : "",
        });
    };

    const total = lines.reduce(
        (sum, line) =>
            sum + (Number(line.quantity) || 0) * (Number(line.unitCost) || 0),
        0,
    );

    return (
        <Form {...store.form(team)}>
            {({ errors, processing }) => (
                <FormLayout
                    size="lg"
                    title={t("New purchase order")}
                    description={t("Order goods from a supplier")}
                    processing={processing}
                    cancelHref={index(team).url}
                    saveTestId="purchase-save-button"
                    actionFields={
                        <div className="flex justify-between text-sm font-semibold">
                            <span>{t("Total")}</span>
                            <span>{formatCurrency(total)}</span>
                        </div>
                    }
                    sidebar={
                        <SidebarCard title="Details">
                            <div className="grid gap-2">
                                <Label htmlFor="ordered_at">
                                    {t("Order date")}
                                </Label>
                                <Input
                                    id="ordered_at"
                                    name="ordered_at"
                                    type="date"
                                    defaultValue={today}
                                    required
                                />
                                <InputError message={errors.ordered_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="expected_at">
                                    {t("Expected date")}
                                </Label>
                                <Input
                                    id="expected_at"
                                    name="expected_at"
                                    type="date"
                                />
                                <InputError message={errors.expected_at} />
                            </div>
                        </SidebarCard>
                    }
                >
                    <div className="grid gap-2">
                        <Label htmlFor="supplier_id">{t("Supplier")}</Label>
                        <NativeSelect id="supplier_id" name="supplier_id" required>
                            <option value="">{t("Select a supplier")}</option>
                            {suppliers.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={errors.supplier_id} />
                    </div>

                    <div className="space-y-3">
                        <Label>{t("Items")}</Label>
                        {lines.map((line, position) => (
                            <div
                                key={position}
                                className="grid items-start gap-2 sm:grid-cols-[minmax(0,1fr)_7rem_9rem_auto]"
                            >
                                <div className="grid gap-1">
                                    <NativeSelect
                                        name={`items[${position}][product_id]`}
                                        aria-label={t("Product")}
                                        value={line.productId}
                                        onChange={(event) =>
                                            pickProduct(position, event.target.value)
                                        }
                                        required
                                    >
                                        <option value="">
                                            {t("Select a product")}
                                        </option>
                                        {products.map((item) => (
                                            <option key={item.id} value={item.id}>
                                                {item.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError
                                        message={
                                            errors[`items.${position}.product_id`]
                                        }
                                    />
                                </div>
                                <div className="grid gap-1">
                                    <Input
                                        name={`items[${position}][quantity]`}
                                        aria-label={t("Quantity")}
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={line.quantity}
                                        onChange={(event) =>
                                            updateLine(position, {
                                                quantity: event.target.value,
                                            })
                                        }
                                        required
                                    />
                                    <InputError
                                        message={
                                            errors[`items.${position}.quantity`]
                                        }
                                    />
                                </div>
                                <div className="grid gap-1">
                                    <Input
                                        name={`items[${position}][unit_cost]`}
                                        aria-label={t("Unit cost")}
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={line.unitCost}
                                        onChange={(event) =>
                                            updateLine(position, {
                                                unitCost: event.target.value,
                                            })
                                        }
                                        required
                                    />
                                    <InputError
                                        message={
                                            errors[`items.${position}.unit_cost`]
                                        }
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    aria-label={t("Remove")}
                                    disabled={lines.length === 1}
                                    onClick={() =>
                                        setLines((current) =>
                                            current.filter((_, i) => i !== position),
                                        )
                                    }
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        ))}
                        <InputError message={errors.items} />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setLines((current) => [...current, { ...emptyLine }])
                            }
                        >
                            <Plus /> {t("Add item")}
                        </Button>
                    </div>

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

PurchasesForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Purchases", href: index(props.currentTeam?.slug ?? "") },
    ],
});
