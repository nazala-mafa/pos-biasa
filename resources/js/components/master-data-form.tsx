import { Form } from "@inertiajs/react";
import { useState } from "react";
import FormLayout from "@/components/form-layout";
import type { PageSize } from "@/components/page-container";
import InputError from "@/components/input-error";
import { useTranslation } from "@/hooks/use-translation";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export type MasterDataField = {
    name: string;
    label: string;
    type?: "text" | "number" | "email" | "textarea" | "checkbox";
    step?: string;
    required?: boolean;
};

type Props = {
    title: string;
    itemLabel: string;
    record: ({ id: number } & Record<string, unknown>) | null;
    fields: MasterDataField[];
    indexUrl: string;
    storeForm: object;
    updateForm: ((id: number) => object) | null;
    size?: PageSize;
};

function CheckboxField({
    field,
    defaultChecked,
    error,
}: {
    field: MasterDataField;
    defaultChecked: boolean;
    error?: string;
}) {
    const { t } = useTranslation();
    const [checked, setChecked] = useState(defaultChecked);

    return (
        <div className="grid gap-2">
            <div className="flex items-center gap-2">
                <Checkbox
                    id={field.name}
                    checked={checked}
                    onCheckedChange={(value) => setChecked(value === true)}
                />
                <Label htmlFor={field.name}>{t(field.label)}</Label>
                <input
                    type="hidden"
                    name={field.name}
                    value={checked ? "1" : "0"}
                />
            </div>
            <InputError message={error} />
        </div>
    );
}

export default function MasterDataForm({
    title,
    itemLabel,
    record,
    fields,
    indexUrl,
    storeForm,
    updateForm,
    size,
}: Props) {
    const { t } = useTranslation();
    const formAction = record && updateForm ? updateForm(record.id) : storeForm;

    return (
        <Form {...formAction}>
            {({ errors, processing }) => (
                <FormLayout
                    size={size}
                    title={t(record ? "Edit :item" : "New :item", {
                        item: t(itemLabel),
                    })}
                    description={title}
                    processing={processing}
                    cancelHref={indexUrl}
                    saveTestId="master-data-save-button"
                >
                    {fields.map((field) => {
                        const defaultValue = record?.[field.name] as
                            string | number | null | undefined;

                        if (field.type === "checkbox") {
                            return (
                                <CheckboxField
                                    key={field.name}
                                    field={field}
                                    defaultChecked={
                                        record ? Boolean(record[field.name]) : true
                                    }
                                    error={errors[field.name]}
                                />
                            );
                        }

                        return (
                            <div key={field.name} className="grid gap-2">
                                <Label htmlFor={field.name}>
                                    {t(field.label)}
                                </Label>
                                {field.type === "textarea" ? (
                                    <textarea
                                        id={field.name}
                                        name={field.name}
                                        defaultValue={defaultValue ?? ""}
                                        rows={3}
                                        className="border-input bg-transparent flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                ) : (
                                    <Input
                                        id={field.name}
                                        name={field.name}
                                        type={field.type ?? "text"}
                                        step={field.step}
                                        defaultValue={defaultValue ?? ""}
                                        required={field.required ?? true}
                                    />
                                )}
                                <InputError message={errors[field.name]} />
                            </div>
                        );
                    })}
                </FormLayout>
            )}
        </Form>
    );
}
