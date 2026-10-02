import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { Head, Link, usePage } from "@inertiajs/react";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import DeleteConfirmModal from "@/components/delete-confirm-modal";
import { Button } from "@/components/ui/button";
import { create, destroy, edit, index } from "@/routes/roles";
import { useTranslation } from "@/hooks/use-translation";

type RoleRow = {
    id: number;
    name: string;
    usersCount: number;
    permissionsCount: number;
    createdAt: string | null;
};

export default function RolesIndex({ roles }: { roles: RoleRow[] }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;
    const [roleToDelete, setRoleToDelete] = useState<RoleRow | null>(null);

    const can = (permission: string) => auth.permissions.includes(permission);

    const columns: DataTableColumn<RoleRow>[] = [
        {
            id: "name",
            header: t("Name"),
            searchValue: (role) => role.name,
            cell: (role) => <span className="font-medium">{role.name}</span>,
        },
        {
            id: "permissions",
            header: t("Permissions"),
            cell: (role) => role.permissionsCount,
        },
        {
            id: "users",
            header: t("Users"),
            cell: (role) => role.usersCount,
        },
        {
            id: "createdAt",
            header: t("Created"),
            dateValue: (role) => role.createdAt,
            cell: (role) => role.createdAt ?? "-",
        },
        {
            id: "actions",
            header: t("Actions"),
            className: "text-right",
            cell: (role) => (
                <div className="flex items-center justify-end gap-2">
                    {can("roles.update") ? (
                        <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={edit([team, role.id])}
                                aria-label={`${t("Edit")} ${role.name}`}
                            >
                                <Pencil className="h-4 w-4" />
                            </Link>
                        </Button>
                    ) : null}
                    {can("roles.delete") ? (
                        <Button
                            variant="ghost"
                            size="sm"
                            aria-label={`${t("Delete")} ${role.name}`}
                            onClick={() => setRoleToDelete(role)}
                        >
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    ) : null}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title={t("Roles")} />

            <DataTable
                size="sm"
                title={t("Roles")}
                description={t("Manage roles and the permissions they grant")}
                actions={
                    can("roles.create") ? (
                        <Button asChild data-test="roles-new-button">
                            <Link href={create(team)}>
                                <Plus /> {t("New role")}
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={roles}
                rowKey={(role) => role.id}
                rowTestId="role-row"
                searchPlaceholder={t("Search roles...")}
            />

            <DeleteConfirmModal
                title={t("Delete role")}
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
