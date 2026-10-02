import { Form, Head, Link } from "@inertiajs/react";
import { useState } from "react";
import Heading from "@/components/heading";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { index, store, update } from "@/routes/users";

type Props = {
    user: { id: number; name: string; email: string; roles: string[] } | null;
    roles: string[];
};

export default function UserForm({ user, roles }: Props) {
    const [selectedRoles, setSelectedRoles] = useState<string[]>(
        user?.roles ?? [],
    );
    const formAction = user ? update.form(user.id) : store.form();

    const toggleRole = (role: string, checked: boolean) => {
        setSelectedRoles((current) =>
            checked ? [...current, role] : current.filter((r) => r !== role),
        );
    };

    return (
        <>
            <Head title={user ? `Edit ${user.name}` : "New user"} />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    variant="small"
                    title={user ? "Edit user" : "New user"}
                    description="Set account details and assign roles"
                />

                <Form {...formAction} className="space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={user?.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
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
                                    Password
                                    {user
                                        ? " (leave blank to keep current)"
                                        : ""}
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
                                    Confirm password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    required={!user}
                                />
                            </div>

                            <div className="grid gap-3">
                                <Label>Roles</Label>
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

                            <div className="flex items-center gap-4">
                                <Button
                                    type="submit"
                                    data-test="user-save-button"
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

UserForm.layout = {
    breadcrumbs: [{ title: "Users", href: index() }],
};
