import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { Head, Link, usePage } from "@inertiajs/react";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import DeleteConfirmModal from "@/components/delete-confirm-modal";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { create, destroy, edit, index } from "@/routes/users";

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    createdAt: string | null;
};

export default function UsersIndex({ users }: { users: UserRow[] }) {
    const team = useCurrentTeamSlug();
    const { auth } = usePage().props;
    const [userToDelete, setUserToDelete] = useState<UserRow | null>(null);

    const can = (permission: string) => auth.permissions.includes(permission);

    const columns: DataTableColumn<UserRow>[] = [
        {
            id: "name",
            header: "Name",
            searchValue: (user) => user.name,
            cell: (user) => <span className="font-medium">{user.name}</span>,
        },
        {
            id: "email",
            header: "Email",
            searchValue: (user) => user.email,
            cell: (user) => user.email,
        },
        {
            id: "roles",
            header: "Roles",
            searchValue: (user) => user.roles.join(" "),
            cell: (user) => (
                <div className="flex flex-wrap gap-1">
                    {user.roles.map((role) => (
                        <Badge key={role} variant="secondary">
                            {role}
                        </Badge>
                    ))}
                </div>
            ),
        },
        {
            id: "createdAt",
            header: "Created",
            dateValue: (user) => user.createdAt,
            cell: (user) => user.createdAt ?? "-",
        },
        {
            id: "actions",
            header: "Actions",
            className: "text-right",
            cell: (user) => (
                <div className="flex items-center justify-end gap-2">
                    {can("users.update") ? (
                        <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={edit([team, user.id])}
                                aria-label={`Edit ${user.name}`}
                            >
                                <Pencil className="h-4 w-4" />
                            </Link>
                        </Button>
                    ) : null}
                    {can("users.delete") && user.id !== auth.user.id ? (
                        <Button
                            variant="ghost"
                            size="sm"
                            aria-label={`Delete ${user.name}`}
                            onClick={() => setUserToDelete(user)}
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
            <Head title="Users" />

            <DataTable
                title="Users"
                description="Manage user accounts and their roles"
                actions={
                    can("users.create") ? (
                        <Button asChild data-test="users-new-button">
                            <Link href={create(team)}>
                                <Plus /> New user
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={users}
                rowKey={(user) => user.id}
                rowTestId="user-row"
                searchPlaceholder="Search users..."
            />

            <DeleteConfirmModal
                title="Delete user"
                name={userToDelete?.name ?? null}
                url={userToDelete ? destroy.url([team, userToDelete.id]) : null}
                open={userToDelete !== null}
                onOpenChange={(open) => !open && setUserToDelete(null)}
            />
        </>
    );
}

UsersIndex.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Users", href: index(props.currentTeam?.slug ?? "") },
    ],
});
