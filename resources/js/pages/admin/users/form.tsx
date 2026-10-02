import { Form, router } from "@inertiajs/react";
import { MailCheck } from "lucide-react";
import { useState } from "react";
import FormLayout, { SidebarCard } from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { dashboard } from "@/routes/admin";
import {
    index,
    store,
    update,
    verificationNotification,
} from "@/routes/admin/users";
import { useTranslation } from "@/hooks/use-translation";

type OutletOption = { slug: string; name: string; roles: string[] };

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        emailVerifiedAt: string | null;
    } | null;
    outlets: OutletOption[];
};

export default function AdminUserForm({ user, outlets }: Props) {
    const { t } = useTranslation();
    const defaultOutlet =
        outlets.find((outlet) => outlet.name === "pos") ?? outlets[0];
    const [outletSlug, setOutletSlug] = useState(defaultOutlet?.slug ?? "");
    const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
    const [verified, setVerified] = useState(true);
    const formAction = user ? update.form(user.id) : store.form();
    const roles = outlets.find((outlet) => outlet.slug === outletSlug)?.roles ?? [];

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
                    cancelHref={index().url}
                    saveTestId="admin-user-save-button"
                    sidebar={
                        user ? (
                            <SidebarCard title={t("Email verification")}>
                                <p className="text-sm text-muted-foreground">
                                    {user.emailVerifiedAt
                                        ? `${t("Email verified at")}: ${user.emailVerifiedAt}`
                                        : t("Not verified")}
                                </p>
                                {!user.emailVerifiedAt ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full"
                                        onClick={() =>
                                            router.post(
                                                verificationNotification.url(
                                                    user.id,
                                                ),
                                            )
                                        }
                                    >
                                        <MailCheck />{" "}
                                        {t("Send verification email")}
                                    </Button>
                                ) : null}
                            </SidebarCard>
                        ) : (
                            <SidebarCard title={t("Outlet and roles")}>
                                <div className="grid gap-2">
                                    <Label htmlFor="outlet">{t("Outlet")}</Label>
                                    <Select
                                        value={outletSlug}
                                        onValueChange={(value) => {
                                            setOutletSlug(value);
                                            setSelectedRoles([]);
                                        }}
                                    >
                                        <SelectTrigger id="outlet">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {outlets.map((outlet) => (
                                                <SelectItem
                                                    key={outlet.slug}
                                                    value={outlet.slug}
                                                >
                                                    {outlet.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="outlet"
                                        value={outletSlug}
                                    />
                                    <InputError message={errors.outlet} />
                                </div>
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
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="verified"
                                        checked={verified}
                                        onCheckedChange={(checked) =>
                                            setVerified(checked === true)
                                        }
                                    />
                                    <Label htmlFor="verified">
                                        {t("Activate now (mark email as verified)")}
                                    </Label>
                                    <input
                                        type="hidden"
                                        name="verified"
                                        value={verified ? "1" : "0"}
                                    />
                                </div>
                            </SidebarCard>
                        )
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

AdminUserForm.layout = {
    breadcrumbs: [
        { title: "Admin", href: dashboard() },
        { title: "Users", href: index() },
    ],
};
