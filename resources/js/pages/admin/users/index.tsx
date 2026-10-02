import { Head } from "@inertiajs/react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { dashboard } from "@/routes/admin";
import { index } from "@/routes/admin/users";

type UserRow = {
    id: number;
    username: string;
    email: string;
    createdAt: string | null;
    outlets: { outlet: string; roles: string[] }[];
};

const outletRoleLabels = (user: UserRow): string[] =>
    user.outlets.map((entry) =>
        entry.roles.length > 0
            ? entry.roles.map((role) => `${entry.outlet}:${role}`).join(", ")
            : entry.outlet,
    );

const columns: DataTableColumn<UserRow>[] = [
    {
        id: "username",
        header: "Username",
        searchValue: (user) => user.username,
        cell: (user) => user.username,
    },
    {
        id: "email",
        header: "Email",
        searchValue: (user) => user.email,
        cell: (user) => user.email,
    },
    {
        id: "outletRole",
        header: "Outlet:Role",
        searchValue: (user) => outletRoleLabels(user).join(" "),
        cell: (user) => (
            <div className="flex flex-wrap gap-1">
                {outletRoleLabels(user).map((label) => (
                    <Badge key={label} variant="secondary">
                        {label}
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
];

export default function AdminUsersIndex({ users }: { users: UserRow[] }) {
    return (
        <>
            <Head title="Users" />
            <DataTable
                title="Users"
                description="All users with their outlet and role"
                columns={columns}
                rows={users}
                rowKey={(user) => user.id}
                rowTestId="admin-user-row"
                searchPlaceholder="Search users..."
            />
        </>
    );
}

AdminUsersIndex.layout = {
    breadcrumbs: [
        { title: "Admin", href: dashboard() },
        { title: "Users", href: index() },
    ],
};
