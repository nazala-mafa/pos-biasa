import { Head, Link, usePage } from "@inertiajs/react";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import DeleteConfirmModal from "@/components/delete-confirm-modal";
import Heading from "@/components/heading";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { create, destroy, edit, index } from "@/routes/roles";

type RoleRow = {
    id: number;
    name: string;
    usersCount: number;
    permissionsCount: number;
    isProtected: boolean;
};

export default function RolesIndex({ roles }: { roles: RoleRow[] }) {
    const { auth } = usePage().props;
    const [roleToDelete, setRoleToDelete] = useState<RoleRow | null>(null);

    const can = (permission: string) => auth.permissions.includes(permission);

    return (
        <>
            <Head title="Roles" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Roles"
                        description="Manage roles and the permissions they grant"
                    />

                    {can("roles.create") ? (
                        <Button asChild data-test="roles-new-button">
                            <Link href={create()}>
                                <Plus /> New role
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="space-y-3">
                    {roles.map((role) => (
                        <div
                            key={role.id}
                            data-test="role-row"
                            className="flex items-center justify-between gap-4 rounded-lg border p-4"
                        >
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium">
                                        {role.name}
                                    </span>
                                    {role.isProtected ? (
                                        <Badge variant="secondary">
                                            Protected
                                        </Badge>
                                    ) : null}
                                </div>
                                <span className="text-sm text-muted-foreground">
                                    {role.permissionsCount} permissions ·{" "}
                                    {role.usersCount} users
                                </span>
                            </div>

                            {!role.isProtected ? (
                                <div className="flex items-center gap-2">
                                    {can("roles.update") ? (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={edit(role.id)}
                                                aria-label={`Edit ${role.name}`}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                    ) : null}
                                    {can("roles.delete") ? (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            aria-label={`Delete ${role.name}`}
                                            onClick={() =>
                                                setRoleToDelete(role)
                                            }
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    ) : null}
                                </div>
                            ) : null}
                        </div>
                    ))}
                </div>
            </div>

            <DeleteConfirmModal
                title="Delete role"
                name={roleToDelete?.name ?? null}
                url={roleToDelete ? destroy.url(roleToDelete.id) : null}
                open={roleToDelete !== null}
                onOpenChange={(open) => !open && setRoleToDelete(null)}
            />
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: "Roles", href: index() }],
};
