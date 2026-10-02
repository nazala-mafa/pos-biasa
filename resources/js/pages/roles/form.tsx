import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { Form } from "@inertiajs/react";
import { useState } from "react";
import FormLayout from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { index, store, update } from "@/routes/roles";
import { useTranslation } from "@/hooks/use-translation";

type PermissionGroup = {
    group: string;
    menus: { menu: string; permissions: string[] }[];
};

type Props = {
    role: { id: number; name: string; permissions: string[] } | null;
    permissions: PermissionGroup[];
};

export default function RoleForm({ role, permissions }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const [selected, setSelected] = useState<string[]>(role?.permissions ?? []);
    const formAction = role ? update.form([team, role.id]) : store.form(team);

    const toggle = (permission: string, checked: boolean) => {
        setSelected((current) =>
            checked
                ? [...current, permission]
                : current.filter((p) => p !== permission),
        );
    };

    const toggleMany = (items: string[], checked: boolean) => {
        setSelected((current) => [
            ...current.filter((p) => !items.includes(p)),
            ...(checked ? items : []),
        ]);
    };

    const selectionState = (items: string[]): boolean | "indeterminate" => {
        const count = items.filter((p) => selected.includes(p)).length;

        if (count === 0) {
            return false;
        }

        return count === items.length ? true : "indeterminate";
    };

    return (
        <Form {...formAction}>
            {({ errors, processing }) => (
                <FormLayout
                    size="md"
                    title={role ? t("Edit role") : t("New role")}
                    description={t("Choose which permissions this role grants")}
                    processing={processing}
                    cancelHref={index(team).url}
                    saveTestId="role-save-button"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t("Name")}</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={role?.name}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-4">
                        <Label>{t("Permissions")}</Label>
                        {permissions.map(({ group, menus }) => (
                            <div key={group} className="space-y-3">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id={`group-${group}`}
                                        aria-label={`${t("Select all")} ${t(group)}`}
                                        checked={selectionState(
                                            menus.flatMap((m) => m.permissions),
                                        )}
                                        onCheckedChange={(checked) =>
                                            toggleMany(
                                                menus.flatMap(
                                                    (m) => m.permissions,
                                                ),
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor={`group-${group}`}
                                        className="font-semibold"
                                    >
                                        {t(group)}
                                    </Label>
                                </div>
                                {menus.map(({ menu, permissions: items }) => (
                                    <div
                                        key={menu}
                                        className="space-y-2 rounded-lg border p-4"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                id={`menu-${group}-${menu}`}
                                                aria-label={`${t("Select all")} ${t(menu)}`}
                                                checked={selectionState(items)}
                                                onCheckedChange={(checked) =>
                                                    toggleMany(
                                                        items,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor={`menu-${group}-${menu}`}
                                                className="font-medium capitalize"
                                            >
                                                {t(menu)}
                                            </Label>
                                        </div>
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {items.map((permission) => (
                                                <div
                                                    key={permission}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Checkbox
                                                        id={permission}
                                                        checked={selected.includes(
                                                            permission,
                                                        )}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            toggle(
                                                                permission,
                                                                checked === true,
                                                            )
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor={permission}
                                                        className="capitalize"
                                                    >
                                                        {t(
                                                            permission.split(
                                                                ".",
                                                            )[1],
                                                        )}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ))}
                        {selected.map((permission) => (
                            <input
                                key={permission}
                                type="hidden"
                                name="permissions[]"
                                value={permission}
                            />
                        ))}
                        <InputError message={errors.permissions} />
                    </div>
                </FormLayout>
            )}
        </Form>
    );
}

RoleForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Roles", href: index(props.currentTeam?.slug ?? "") },
    ],
});
