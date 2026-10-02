import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { Form } from "@inertiajs/react";
import { useMemo, useState } from "react";
import FormLayout from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { index, store, update } from "@/routes/roles";

type Props = {
    role: { id: number; name: string; permissions: string[] } | null;
    permissions: string[];
};

export default function RoleForm({ role, permissions }: Props) {
    const team = useCurrentTeamSlug();
    const [selected, setSelected] = useState<string[]>(role?.permissions ?? []);
    const formAction = role ? update.form([team, role.id]) : store.form(team);

    const groups = useMemo(() => {
        const grouped: Record<string, string[]> = {};

        permissions.forEach((permission) => {
            const [group] = permission.split(".");
            (grouped[group] ??= []).push(permission);
        });

        return grouped;
    }, [permissions]);

    const toggle = (permission: string, checked: boolean) => {
        setSelected((current) =>
            checked
                ? [...current, permission]
                : current.filter((p) => p !== permission),
        );
    };

    return (
        <Form {...formAction}>
            {({ errors, processing }) => (
                <FormLayout
                    size="md"
                    title={role ? "Edit role" : "New role"}
                    description="Choose which permissions this role grants"
                    processing={processing}
                    cancelHref={index(team).url}
                    saveTestId="role-save-button"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={role?.name}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-4">
                        <Label>Permissions</Label>
                        {Object.entries(groups).map(([group, items]) => (
                            <div
                                key={group}
                                className="space-y-2 rounded-lg border p-4"
                            >
                                <div className="text-sm font-medium capitalize">
                                    {group}
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
                                                onCheckedChange={(checked) =>
                                                    toggle(
                                                        permission,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <Label htmlFor={permission}>
                                                {permission}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
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
