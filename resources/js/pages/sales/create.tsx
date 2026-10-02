import { Head, router } from "@inertiajs/react";
import { ImageIcon, Minus, Plus, Search, Trash2 } from "lucide-react";
import { useMemo, useState } from "react";
import InputError from "@/components/input-error";
import NativeSelect from "@/components/native-select";
import PageContainer from "@/components/page-container";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { formatCurrency, formatQuantity } from "@/lib/format";
import { index, store } from "@/routes/sales";

type Product = {
    id: number;
    sku: string;
    name: string;
    category: string | null;
    unit: string | null;
    photoUrl: string | null;
    price: number;
    taxRate: number;
    stock: number;
};

type Props = {
    products: Product[];
    customers: { id: number; name: string }[];
    paymentMethods: { value: string; label: string }[];
};

export default function SalesCreate({
    products,
    customers,
    paymentMethods,
}: Props) {
    const team = useCurrentTeamSlug();
    const [search, setSearch] = useState("");
    const [cart, setCart] = useState<Record<number, number>>({});
    const [customerId, setCustomerId] = useState("");
    const [paymentMethod, setPaymentMethod] = useState(
        paymentMethods[0]?.value ?? "cash",
    );
    const [discount, setDiscount] = useState("");
    const [paid, setPaid] = useState("");
    const [note, setNote] = useState("");
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const visibleProducts = useMemo(() => {
        const term = search.trim().toLowerCase();

        return products.filter((product) =>
            `${product.sku} ${product.name} ${product.category ?? ""}`
                .toLowerCase()
                .includes(term),
        );
    }, [products, search]);

    const lines = products
        .filter((product) => cart[product.id] > 0)
        .map((product) => {
            const quantity = cart[product.id];
            const subtotal = product.price * quantity;

            return {
                product,
                quantity,
                subtotal,
                tax: (subtotal * product.taxRate) / 100,
            };
        });

    const subtotal = lines.reduce((sum, line) => sum + line.subtotal, 0);
    const tax = lines.reduce((sum, line) => sum + line.tax, 0);
    const discountValue = Number(discount) || 0;
    const total = Math.max(subtotal + tax - discountValue, 0);
    const paidValue = paid === "" ? total : Number(paid);
    const change = paidValue - total;

    const setQuantity = (product: Product, quantity: number) => {
        const clamped = Math.min(Math.max(quantity, 0), product.stock);

        setCart((current) => {
            const { [product.id]: _removed, ...rest } = current;

            return clamped > 0 ? { ...current, [product.id]: clamped } : rest;
        });
    };

    const submit = () => {
        router.post(
            store(team).url,
            {
                customer_id: customerId || null,
                payment_method: paymentMethod,
                discount: discountValue,
                paid: paidValue,
                note: note || null,
                items: lines.map((line) => ({
                    product_id: line.product.id,
                    quantity: line.quantity,
                })),
            },
            {
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (formErrors) => setErrors(formErrors),
            },
        );
    };

    return (
        <>
            <Head title="New sale" />

            <PageContainer
                size="lg"
                className="grid items-start gap-6 p-4 lg:grid-cols-[minmax(0,1fr)_24rem]"
            >
                <Card className="gap-0 py-0">
                    <CardHeader className="space-y-3 border-b px-6 py-4">
                        <div>
                            <CardTitle>New sale</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Pick the products to sell
                            </p>
                        </div>
                        <div className="relative">
                            <Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                className="pl-9"
                                placeholder="Search by name, SKU or category..."
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="p-6">
                        {visibleProducts.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No products found.
                            </p>
                        ) : (
                            <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                                {visibleProducts.map((product) => {
                                    const outOfStock = product.stock <= 0;

                                    return (
                                        <button
                                            key={product.id}
                                            type="button"
                                            data-test="sale-product"
                                            disabled={outOfStock}
                                            onClick={() =>
                                                setQuantity(
                                                    product,
                                                    (cart[product.id] ?? 0) + 1,
                                                )
                                            }
                                            className="flex flex-col overflow-hidden rounded-lg border text-left transition hover:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <div className="flex aspect-square w-full items-center justify-center bg-muted">
                                                {product.photoUrl ? (
                                                    <img
                                                        src={product.photoUrl}
                                                        alt={product.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <ImageIcon className="h-8 w-8 text-muted-foreground" />
                                                )}
                                            </div>
                                            <div className="space-y-0.5 p-3">
                                                <div className="line-clamp-2 text-sm font-medium">
                                                    {product.name}
                                                </div>
                                                <div className="text-sm">
                                                    {formatCurrency(
                                                        product.price,
                                                    )}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {outOfStock
                                                        ? "Out of stock"
                                                        : `Stock: ${formatQuantity(product.stock)} ${product.unit ?? ""}`}
                                                </div>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="gap-0 py-0 lg:sticky lg:top-4">
                    <CardHeader className="border-b px-6 py-4">
                        <CardTitle>Cart</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 p-6">
                        {lines.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                The cart is empty.
                            </p>
                        ) : (
                            <ul className="space-y-3">
                                {lines.map(
                                    ({ product, quantity, subtotal }) => (
                                        <li
                                            key={product.id}
                                            data-test="cart-line"
                                            className="space-y-2"
                                        >
                                            <div className="flex justify-between gap-2 text-sm">
                                                <span className="font-medium">
                                                    {product.name}
                                                </span>
                                                <span>
                                                    {formatCurrency(subtotal)}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    aria-label={`Decrease ${product.name}`}
                                                    onClick={() =>
                                                        setQuantity(
                                                            product,
                                                            quantity - 1,
                                                        )
                                                    }
                                                >
                                                    <Minus className="h-3 w-3" />
                                                </Button>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max={product.stock}
                                                    className="h-8 w-20 text-center"
                                                    aria-label={`Quantity ${product.name}`}
                                                    value={quantity}
                                                    onChange={(event) =>
                                                        setQuantity(
                                                            product,
                                                            Number(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        )
                                                    }
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    aria-label={`Increase ${product.name}`}
                                                    onClick={() =>
                                                        setQuantity(
                                                            product,
                                                            quantity + 1,
                                                        )
                                                    }
                                                >
                                                    <Plus className="h-3 w-3" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="ml-auto h-8 w-8"
                                                    aria-label={`Remove ${product.name}`}
                                                    onClick={() =>
                                                        setQuantity(product, 0)
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </li>
                                    ),
                                )}
                            </ul>
                        )}
                        <InputError message={errors.items} />

                        <div className="grid gap-2">
                            <Label htmlFor="customer_id">Customer</Label>
                            <NativeSelect
                                id="customer_id"
                                value={customerId}
                                onChange={(event) =>
                                    setCustomerId(event.target.value)
                                }
                            >
                                <option value="">Walk-in customer</option>
                                {customers.map((customer) => (
                                    <option
                                        key={customer.id}
                                        value={customer.id}
                                    >
                                        {customer.name}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.customer_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="payment_method">
                                Payment method
                            </Label>
                            <NativeSelect
                                id="payment_method"
                                value={paymentMethod}
                                onChange={(event) =>
                                    setPaymentMethod(event.target.value)
                                }
                            >
                                {paymentMethods.map((method) => (
                                    <option
                                        key={method.value}
                                        value={method.value}
                                    >
                                        {method.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.payment_method} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="discount">Discount</Label>
                            <Input
                                id="discount"
                                type="number"
                                step="0.01"
                                min="0"
                                value={discount}
                                onChange={(event) =>
                                    setDiscount(event.target.value)
                                }
                            />
                            <InputError message={errors.discount} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="note">Note</Label>
                            <Input
                                id="note"
                                maxLength={255}
                                value={note}
                                onChange={(event) =>
                                    setNote(event.target.value)
                                }
                            />
                            <InputError message={errors.note} />
                        </div>

                        <dl className="space-y-1 border-t pt-4 text-sm">
                            <div className="flex justify-between">
                                <dt>Subtotal</dt>
                                <dd>{formatCurrency(subtotal)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt>Tax</dt>
                                <dd>{formatCurrency(tax)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt>Discount</dt>
                                <dd>- {formatCurrency(discountValue)}</dd>
                            </div>
                            <div className="flex justify-between text-base font-semibold">
                                <dt>Total</dt>
                                <dd data-test="sale-total">
                                    {formatCurrency(total)}
                                </dd>
                            </div>
                        </dl>

                        <div className="grid gap-2">
                            <Label htmlFor="paid">Amount paid</Label>
                            <Input
                                id="paid"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder={String(total)}
                                value={paid}
                                onChange={(event) =>
                                    setPaid(event.target.value)
                                }
                            />
                            <p className="text-sm text-muted-foreground">
                                Change: {formatCurrency(Math.max(change, 0))}
                            </p>
                            <InputError message={errors.paid} />
                        </div>
                    </CardContent>
                    <CardFooter className="flex-col gap-2 px-6 py-4">
                        <Button
                            type="button"
                            className="w-full"
                            data-test="sale-save-button"
                            disabled={
                                processing ||
                                lines.length === 0 ||
                                paidValue < total
                            }
                            onClick={submit}
                        >
                            Complete sale
                        </Button>
                        <Button
                            variant="secondary"
                            className="w-full"
                            type="button"
                            onClick={() => router.visit(index(team).url)}
                        >
                            Cancel
                        </Button>
                    </CardFooter>
                </Card>
            </PageContainer>
        </>
    );
}

SalesCreate.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Sales", href: index(props.currentTeam?.slug ?? "") },
    ],
});
