import { Form, router } from "@inertiajs/react";
import { Download, FileSpreadsheet, Upload } from "lucide-react";
import { useRef, useState } from "react";
import FormLayout, { SidebarCard } from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { dashboard } from "@/routes/admin";
import outletRoutes, { index, store, update } from "@/routes/admin/outlets";

type Props = {
    outlet: { slug: string; name: string; isDefault: boolean } | null;
};

function OutletDataCard({ slug }: { slug: string }) {
    const fileInput = useRef<HTMLInputElement>(null);
    const [importing, setImporting] = useState(false);

    const importFile = (file: File | undefined) => {
        if (!file) {
            return;
        }

        router.post(
            outletRoutes.import.url(slug),
            { file },
            {
                forceFormData: true,
                onStart: () => setImporting(true),
                onFinish: () => {
                    setImporting(false);

                    if (fileInput.current) {
                        fileInput.current.value = "";
                    }
                },
            },
        );
    };

    return (
        <SidebarCard title="Data">
            <p className="text-sm text-muted-foreground">
                Export or import all outlet data. Every menu is its own sheet.
            </p>
            <input
                ref={fileInput}
                type="file"
                accept=".xlsx,.xls"
                className="hidden"
                data-test="outlet-import-input"
                onChange={(event) => importFile(event.target.files?.[0])}
            />
            <Button variant="outline" className="w-full" asChild>
                <a href={outletRoutes.template.url()}>
                    <FileSpreadsheet /> Template
                </a>
            </Button>
            <Button
                type="button"
                variant="outline"
                className="w-full"
                disabled={importing}
                onClick={() => fileInput.current?.click()}
                data-test="outlet-import-button"
            >
                <Upload /> Import
            </Button>
            <Button variant="outline" className="w-full" asChild>
                <a href={outletRoutes.export.url(slug)}>
                    <Download /> Export
                </a>
            </Button>
        </SidebarCard>
    );
}

export default function OutletForm({ outlet }: Props) {
    const formAction = outlet ? update.form(outlet.slug) : store.form();

    return (
        <Form {...formAction}>
            {({ errors, processing }) => (
                <FormLayout
                    size="sm"
                    title={outlet ? "Edit outlet" : "New outlet"}
                    description="Give the outlet a name"
                    processing={processing || outlet?.isDefault === true}
                    cancelHref={index().url}
                    saveTestId="outlet-save-button"
                    sidebar={
                        outlet ? <OutletDataCard slug={outlet.slug} /> : null
                    }
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={outlet?.name}
                            readOnly={outlet?.isDefault}
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
