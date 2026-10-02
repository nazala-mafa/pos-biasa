import { Form } from "@inertiajs/react";
import { useState } from "react";
import FormLayout from "@/components/form-layout";
import InputError from "@/components/input-error";
import NativeSelect from "@/components/native-select";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatQuantity } from "@/lib/format";
import { index, store } from "@/routes/inventory";

type Props = {
    products: {
        id: number;
        label: string;
        unit: string | null;
        stock: number;
    }[];
    suppliers: { id: number; name: string }[];
    types: { value: string; label: string }[];
};

export default function InventoryForm({ products, suppliers, types }: Props) {
    const team = useCurrentTeamSlug();
    const [productId, setProductId] = useState("");
    const [type, setType] = useState("in");
    const product = products.find((item) => String(item.id) === productId);

    return (
        <Form {...store.form(team)}>
            {({ errors, processing }) => (
                <FormLayout
                    size="md"
                    title="New stock movement"
                    description="Record stock in, stock out or an adjustment"
                    processing={processing}
                    cancelHref={index(team).url}
                    saveTestId="stock-movement-save-button"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="product_id">Product</Label>
                        <NativeSelect
                            id="product_id"
                            name="product_id"
                            value={productId}
                            onChange={(event) =>
                                setProductId(event.target.value)
                            }
                            required
                        >
                            <option value="">Select a product</option>
                            {products.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.label}
                                </option>
                            ))}
                        </NativeSelect>
                        {product ? (
                            <p className="text-sm text-muted-foreground">
                                Current stock: {formatQuantity(product.stock)}{" "}
                                {product.unit ?? ""}
                            </p>
                        ) : null}
                        <InputError message={errors.product_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="type">Type</Label>
                        <NativeSelect
                            id="type"
                            name="type"
                            value={type}
                            onChange={(event) => setType(event.target.value)}
                            required
                        >
                            {types.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={errors.type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="quantity">
                            {type === "adjustment"
                                ? "Quantity (negative to reduce)"
                                : "Quantity"}
                        </Label>
                        <Input
                            id="quantity"
                            name="quantity"
                            type="number"
                            step="0.01"
                            min={type === "adjustment" ? undefined : "0.01"}
                            required
                        />
                        <InputError message={errors.quantity} />
                    </div>

                    {type === "in" ? (
                        <div className="grid gap-2">
                            <Label htmlFor="supplier_id">Supplier</Label>
                            <NativeSelect id="supplier_id" name="supplier_id">
                                <option value="">-</option>
                                {suppliers.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.supplier_id} />
                        </div>
                    ) : null}

                    <div className="grid gap-2">
                        <Label htmlFor="note">Note</Label>
                        <Input id="note" name="note" maxLength={255} />
                        <InputError message={errors.note} />
                    </div>
                </FormLayout>
            )}
        </Form>
    );
}

InventoryForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Inventory", href: index(props.currentTeam?.slug ?? "") },
    ],
});
