import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { Form } from "@inertiajs/react";
import { useState } from "react";
import FormLayout, { SidebarCard } from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { index, store, update } from "@/routes/users";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    user: { id: number; name: string; email: string; roles: string[] } | null;
    roles: string[];
};

export default function UserForm({ user, roles }: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const [selectedRoles, setSelectedRoles] = useState<string[]>(
        user?.roles ?? [],
    );
    const formAction = user ? update.form([team, user.id]) : store.form(team);

    const toggleRole = (role: string, checked: boolean) => {
        setSelectedRoles((current) =>
            checked ? [...current, role] : current.filter((r) => r !== role),
        );
    };

    return (
        <Form {...formAction}>
            {({ errors, processing }) => (
                <FormLayout
                    size="md"
                    title={user ? t("Edit user") : t("New user")}
                    description={t("Set account details and assign roles")}
                    processing={processing}
                    cancelHref={index(team).url}
                    saveTestId="user-save-button"
                    sidebar={
                        <SidebarCard title={t("Roles")}>
                            <div className="grid gap-3">
                                {roles.map((role) => (
                                    <div
                                        key={role}
                                        className="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            id={`role-${role}`}
                                            checked={selectedRoles.includes(
                                                role,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleRole(
                                                    role,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label htmlFor={`role-${role}`}>
                                            {role}
                                        </Label>
                                    </div>
                                ))}
                                {selectedRoles.map((role) => (
                                    <input
                                        key={role}
                                        type="hidden"
                                        name="roles[]"
                                        value={role}
                                    />
                                ))}
                                <InputError message={errors.roles} />
                            </div>
                        </SidebarCard>
                    }
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t("Name")}</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={user?.name}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">{t("Email")}</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            defaultValue={user?.email}
                            required
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">
                            {t("Password")}
                            {user ? ` (${t("leave blank to keep current")})` : ""}
                        </Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            autoComplete="new-password"
                            required={!user}
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            {t("Confirm password")}
                        </Label>
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            required={!user}
                        />
                    </div>
                </FormLayout>
            )}
        </Form>
    );
}

UserForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Users", href: index(props.currentTeam?.slug ?? "") },
    ],
});
