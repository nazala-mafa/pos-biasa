import { Form, router } from "@inertiajs/react";
import PageContainer from "@/components/page-container";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Download, FileSpreadsheet, Upload } from "lucide-react";
import { useRef, useState } from "react";
import FormLayout, { SidebarCard } from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { dashboard } from "@/routes/admin";
import outletRoutes, { index, store, update } from "@/routes/admin/outlets";
import { useTranslation } from "@/hooks/use-translation";

type OutletUser = { id: number; name: string; email: string };

type Props = {
    outlet: { slug: string; name: string; isDefault: boolean } | null;
    users?: OutletUser[];
    memberIds?: number[];
};

function OutletUsersForm({
    outlet,
    users,
    memberIds,
}: {
    outlet: { slug: string; isDefault: boolean };
    users: OutletUser[];
    memberIds: number[];
}) {
    const { t } = useTranslation();
    const [selected, setSelected] = useState<number[]>(memberIds);
    const [processing, setProcessing] = useState(false);
    const allSelected = users.length > 0 && selected.length === users.length;

    const toggle = (id: number, checked: boolean) =>
        setSelected((current) =>
            checked ? [...current, id] : current.filter((item) => item !== id),
        );

    const submit = () => {
        router.put(
            outletRoutes.users.sync.url(outlet.slug),
            { user_ids: selected },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <PageContainer size="sm" className="p-4 pt-0">
            <Card className="gap-0 py-0">
                <CardHeader className="border-b px-6 py-4">
                    <CardTitle>{t("Outlet users")}</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {t("Choose the users that belong to this outlet")}
                    </p>
                </CardHeader>
                <CardContent className="space-y-3 p-6">
                    <label className="flex items-center gap-2 text-sm font-medium">
                        <Checkbox
                            checked={allSelected}
                            disabled={outlet.isDefault}
                            onCheckedChange={(checked) =>
                                setSelected(
                                    checked === true
                                        ? users.map((user) => user.id)
                                        : [],
                                )
                            }
                        />
                        {t("Select all")}
                    </label>
                    <div className="grid gap-2 sm:grid-cols-2">
                        {users.map((user) => (
                            <label
                                key={user.id}
                                className="flex items-center gap-2 rounded-md border p-3 text-sm"
                            >
                                <Checkbox
                                    checked={selected.includes(user.id)}
                                    disabled={outlet.isDefault}
                                    onCheckedChange={(checked) =>
                                        toggle(user.id, checked === true)
                                    }
                                    data-test={`outlet-user-${user.id}`}
                                />
                                <span className="grid">
                                    <span className="font-medium">
                                        {user.name}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {user.email}
                                    </span>
                                </span>
                            </label>
                        ))}
                    </div>
                </CardContent>
                <CardFooter className="border-t px-6 py-4">
                    <Button
                        type="button"
                        disabled={processing || outlet.isDefault}
                        onClick={submit}
                        data-test="outlet-users-save-button"
                    >
                        {t("Save")}
                    </Button>
                </CardFooter>
            </Card>
        </PageContainer>
    );
}

function OutletDataCard({ slug }: { slug: string }) {
    const { t } = useTranslation();
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
        <SidebarCard title={t("Data")}>
            <p className="text-sm text-muted-foreground">
                {t(
                    "Export or import all outlet data. Every menu is its own sheet.",
                )}
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
                    <FileSpreadsheet /> {t("Template")}
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
                <Upload /> {t("Import")}
            </Button>
            <Button variant="outline" className="w-full" asChild>
                <a href={outletRoutes.export.url(slug)}>
                    <Download /> {t("Export")}
                </a>
            </Button>
        </SidebarCard>
    );
}

export default function OutletForm({
    outlet,
    users = [],
    memberIds = [],
}: Props) {
    const { t } = useTranslation();
    const formAction = outlet ? update.form(outlet.slug) : store.form();

    return (
        <>
            <Form {...formAction}>
                {({ errors, processing }) => (
                    <FormLayout
                        size="sm"
                        title={outlet ? t("Edit outlet") : t("New outlet")}
                        description={t("Give the outlet a name")}
                        processing={processing || outlet?.isDefault === true}
                        cancelHref={index().url}
                        saveTestId="outlet-save-button"
                        sidebar={
                            outlet ? (
                                <OutletDataCard slug={outlet.slug} />
                            ) : null
                        }
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">{t("Name")}</Label>
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
            {outlet && (
                <OutletUsersForm
                    outlet={outlet}
                    users={users}
                    memberIds={memberIds}
                />
            )}
        </>
    );
}

OutletForm.layout = {
    breadcrumbs: [
        { title: "Admin", href: dashboard() },
        { title: "Outlets", href: index() },
    ],
};
