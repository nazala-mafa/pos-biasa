import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { Head, Link, usePage } from "@inertiajs/react";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import DeleteConfirmModal from "@/components/delete-confirm-modal";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { create, destroy, edit, index } from "@/routes/roles";

type RoleRow = {
    id: number;
    name: string;
    usersCount: number;
    permissionsCount: number;
    isProtected: boolean;
    createdAt: string | null;
};

export default function RolesIndex({ roles }: { roles: RoleRow[] }) {
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;
    const [roleToDelete, setRoleToDelete] = useState<RoleRow | null>(null);

    const can = (permission: string) => auth.permissions.includes(permission);

    const columns: DataTableColumn<RoleRow>[] = [
        {
            id: "name",
            header: "Name",
            searchValue: (role) => role.name,
            cell: (role) => (
                <div className="flex items-center gap-2">
                    <span className="font-medium">{role.name}</span>
                    {role.isProtected ? (
                        <Badge variant="secondary">Protected</Badge>
                    ) : null}
                </div>
            ),
        },
        {
            id: "permissions",
            header: "Permissions",
            cell: (role) => role.permissionsCount,
        },
        {
            id: "users",
            header: "Users",
            cell: (role) => role.usersCount,
        },
        {
            id: "createdAt",
            header: "Created",
            dateValue: (role) => role.createdAt,
            cell: (role) => role.createdAt ?? "-",
        },
        {
            id: "actions",
            header: "Actions",
            className: "text-right",
            cell: (role) =>
                !role.isProtected ? (
                    <div className="flex items-center justify-end gap-2">
                        {can("roles.update") ? (
                            <Button variant="ghost" size="sm" asChild>
                                <Link
                                    href={edit([team, role.id])}
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
                                onClick={() => setRoleToDelete(role)}
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        ) : null}
                    </div>
                ) : null,
        },
    ];

    return (
        <>
            <Head title="Roles" />

            <DataTable
                title="Roles"
                description="Manage roles and the permissions they grant"
                actions={
                    can("roles.create") ? (
                        <Button asChild data-test="roles-new-button">
                            <Link href={create(team)}>
                                <Plus /> New role
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={roles}
                rowKey={(role) => role.id}
                rowTestId="role-row"
                searchPlaceholder="Search roles..."
            />

            <DeleteConfirmModal
                title="Delete role"
                name={roleToDelete?.name ?? null}
                url={roleToDelete ? destroy.url([team, roleToDelete.id]) : null}
                open={roleToDelete !== null}
                onOpenChange={(open) => !open && setRoleToDelete(null)}
            />
        </>
    );
}

RolesIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Roles", href: index(props.currentTeam?.slug ?? "") },
    ],
});
