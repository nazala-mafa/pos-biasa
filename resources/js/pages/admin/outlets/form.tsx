import { Form } from "@inertiajs/react";
import FormLayout from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { dashboard } from "@/routes/admin";
import { index, store, update } from "@/routes/admin/outlets";

type Props = {
    outlet: { slug: string; name: string } | null;
};

export default function OutletForm({ outlet }: Props) {
    const formAction = outlet ? update.form(outlet.slug) : store.form();

    return (
        <Form {...formAction}>
            {({ errors, processing }) => (
                <FormLayout
                    size="sm"
                    title={outlet ? "Edit outlet" : "New outlet"}
                    description="Give the outlet a name"
                    processing={processing}
                    cancelHref={index().url}
                    saveTestId="outlet-save-button"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={outlet?.name}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                </FormLayout>
            )}
        </Form>
    );
}

OutletForm.layout = {
    breadcrumbs: [
        { title: "Admin", href: dashboard() },
        { title: "Outlets", href: index() },
    ],
};
