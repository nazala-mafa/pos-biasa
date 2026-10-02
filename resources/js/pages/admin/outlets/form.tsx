import { Form, Head, Link } from "@inertiajs/react";
import Heading from "@/components/heading";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
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
        <>
            <Head title={outlet ? `Edit ${outlet.name}` : "New outlet"} />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    variant="small"
                    title={outlet ? "Edit outlet" : "New outlet"}
                    description="Give the outlet a name"
                />

                <Form {...formAction} className="space-y-6">
                    {({ errors, processing }) => (
                        <>
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

                            <div className="flex items-center gap-4">
                                <Button
                                    type="submit"
                                    data-test="outlet-save-button"
                                    disabled={processing}
                                >
                                    Save
                                </Button>
                                <Button variant="secondary" asChild>
                                    <Link href={index()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

OutletForm.layout = {
    breadcrumbs: [
        { title: "Admin", href: dashboard() },
        { title: "Outlets", href: index() },
    ],
};
