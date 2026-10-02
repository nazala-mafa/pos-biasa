import { Form } from "@inertiajs/react";
import FormLayout from "@/components/form-layout";
import type { PageSize } from "@/components/page-container";
import InputError from "@/components/input-error";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export type MasterDataField = {
    name: string;
    label: string;
    type?: "text" | "number" | "textarea";
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
    const formAction = record && updateForm ? updateForm(record.id) : storeForm;

    return (
        <Form {...formAction}>
            {({ errors, processing }) => (
                <FormLayout
                    size={size}
                    title={record ? `Edit ${itemLabel}` : `New ${itemLabel}`}
                    description={title}
                    processing={processing}
                    cancelHref={indexUrl}
                    saveTestId="master-data-save-button"
                >
                    {fields.map((field) => {
                        const defaultValue = record?.[field.name] as
                            string | number | null | undefined;

                        return (
                            <div key={field.name} className="grid gap-2">
                                <Label htmlFor={field.name}>
                                    {field.label}
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
