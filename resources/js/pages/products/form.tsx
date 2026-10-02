import { Form } from "@inertiajs/react";
import { ImageIcon } from "lucide-react";
import { useEffect, useState } from "react";
import FormLayout, { SidebarCard } from "@/components/form-layout";
import InputError from "@/components/input-error";
import NativeSelect from "@/components/native-select";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { index, store, update } from "@/routes/products";
import { useTranslation } from "@/hooks/use-translation";

type Option = { id: number; name: string };

type Props = {
    record: {
        id: number;
        sku: string;
        name: string;
        description: string | null;
        photoUrl: string | null;
        categoryId: number | null;
        unitId: number | null;
        taxId: number | null;
        costPrice: number;
        sellingPrice: number;
        minStock: number;
        isActive: boolean;
    } | null;
    categories: Option[];
    units: (Option & { abbreviation: string })[];
    taxes: (Option & { rate: number })[];
};

export default function ProductsForm({
    record,
    categories,
    units,
    taxes,
}: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const [isActive, setIsActive] = useState(record?.isActive ?? true);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [removePhoto, setRemovePhoto] = useState(false);
    const currentPhotoUrl = removePhoto ? null : (record?.photoUrl ?? null);
    const shownPhotoUrl = previewUrl ?? currentPhotoUrl;

    useEffect(
        () => () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        },
        [previewUrl],
    );

    const formAction = record
        ? update.form([team, record.id])
        : store.form(team);

    return (
        <Form {...formAction}>
            {({ errors, processing }) => (
                <FormLayout
                    size="lg"
                    title={record ? t("Edit product") : t("New product")}
                    description={t("Products")}
                    processing={processing}
                    cancelHref={index(team).url}
                    saveTestId="product-save-button"
                    actionFields={
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_active_checkbox"
                                checked={isActive}
                                onCheckedChange={(checked) =>
                                    setIsActive(checked === true)
                                }
                            />
                            <Label htmlFor="is_active_checkbox">{t("Active")}</Label>
                            <input
                                type="hidden"
                                name="is_active"
                                value={isActive ? "1" : "0"}
                            />
                        </div>
                    }
                    sidebar={
                        <>
                            <SidebarCard title={t("Photo")}>
                                <div className="flex aspect-square w-full items-center justify-center overflow-hidden rounded-md border bg-muted">
                                    {shownPhotoUrl ? (
                                        <img
                                            src={shownPhotoUrl}
                                            alt={
                                                record?.name ?? t("Product photo")
                                            }
                                            className="h-full w-full object-cover"
                                        />
                                    ) : (
                                        <ImageIcon className="h-10 w-10 text-muted-foreground" />
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="photo">{t("Upload photo")}</Label>
                                    <Input
                                        id="photo"
                                        name="photo"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={(event) => {
                                            const file =
                                                event.target.files?.[0];

                                            setPreviewUrl(
                                                file
                                                    ? URL.createObjectURL(file)
                                                    : null,
                                            );
                                        }}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        {t("JPG, PNG or WebP, max 2 MB.")}
                                    </p>
                                    <InputError message={errors.photo} />
                                </div>
                                {record?.photoUrl ? (
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="remove_photo_checkbox"
                                            checked={removePhoto}
                                            onCheckedChange={(checked) =>
                                                setRemovePhoto(checked === true)
                                            }
                                        />
                                        <Label htmlFor="remove_photo_checkbox">
                                            {t("Remove current photo")}
                                        </Label>
                                        <input
                                            type="hidden"
                                            name="remove_photo"
                                            value={removePhoto ? "1" : "0"}
                                        />
                                    </div>
                                ) : null}
                            </SidebarCard>
                            <SidebarCard title={t("Classification")}>
                                <div className="grid gap-2">
                                    <Label htmlFor="product_category_id">
                                        {t("Category")}
                                    </Label>
                                    <NativeSelect
                                        id="product_category_id"
                                        name="product_category_id"
                                        defaultValue={record?.categoryId ?? ""}
                                    >
                                        <option value="">-</option>
                                        {categories.map((item) => (
                                            <option
                                                key={item.id}
                                                value={item.id}
                                            >
                                                {item.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError
                                        message={errors.product_category_id}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="unit_id">{t("Unit")}</Label>
                                    <NativeSelect
                                        id="unit_id"
                                        name="unit_id"
                                        defaultValue={record?.unitId ?? ""}
                                    >
                                        <option value="">-</option>
                                        {units.map((item) => (
                                            <option
                                                key={item.id}
                                                value={item.id}
                                            >
                                                {item.name} ({item.abbreviation}
                                                )
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.unit_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="tax_id">{t("Tax")}</Label>
                                    <NativeSelect
                                        id="tax_id"
                                        name="tax_id"
                                        defaultValue={record?.taxId ?? ""}
                                    >
                                        <option value="">-</option>
                                        {taxes.map((item) => (
                                            <option
                                                key={item.id}
                                                value={item.id}
                                            >
                                                {item.name} ({item.rate}%)
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.tax_id} />
                                </div>
                            </SidebarCard>
                        </>
                    }
                >
                    <div className="grid gap-2">
                        <Label htmlFor="sku">{t("SKU")}</Label>
                        <Input
                            id="sku"
                            name="sku"
                            defaultValue={record?.sku}
                            required
                        />
                        <InputError message={errors.sku} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t("Name")}</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={record?.name}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="description">{t("Description")}</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows={3}
                            defaultValue={record?.description ?? ""}
                            className="border-input bg-transparent flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <InputError message={errors.description} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="cost_price">{t("Cost price")}</Label>
                            <Input
                                id="cost_price"
                                name="cost_price"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={record?.costPrice ?? 0}
                                required
                            />
                            <InputError message={errors.cost_price} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="selling_price">{t("Selling price")}</Label>
                            <Input
                                id="selling_price"
                                name="selling_price"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={record?.sellingPrice ?? 0}
                                required
                            />
                            <InputError message={errors.selling_price} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="min_stock">{t("Minimum stock")}</Label>
                            <Input
                                id="min_stock"
                                name="min_stock"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={record?.minStock ?? 0}
                                required
                            />
                            <InputError message={errors.min_stock} />
                        </div>
                    </div>
                </FormLayout>
            )}
        </Form>
    );
}

ProductsForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Products", href: index(props.currentTeam?.slug ?? "") },
    ],
});
