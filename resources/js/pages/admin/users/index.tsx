import { Head, Link, router } from "@inertiajs/react";
import { MailCheck, Pencil, Plus, UserCheck, UserX } from "lucide-react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { dashboard } from "@/routes/admin";
import {
    activate,
    create,
    deactivate,
    edit,
    index,
    verificationNotification,
} from "@/routes/admin/users";
import { useTranslation } from "@/hooks/use-translation";

type UserRow = {
    id: number;
    username: string;
    email: string;
    emailVerifiedAt: string | null;
    createdAt: string | null;
    outlets: { outlet: string; roles: string[] }[];
};

const outletRoleLabels = (user: UserRow): string[] =>
    user.outlets.map((entry) =>
        entry.roles.length > 0
            ? entry.roles.map((role) => `${entry.outlet}:${role}`).join(", ")
            : entry.outlet,
    );

export default function AdminUsersIndex({ users }: { users: UserRow[] }) {
    const { t } = useTranslation();

    const columns: DataTableColumn<UserRow>[] = [
        {
            id: "username",
            header: t("Username"),
            searchValue: (user) => user.username,
            cell: (user) => user.username,
        },
        {
            id: "email",
            header: t("Email"),
            searchValue: (user) => user.email,
            cell: (user) => user.email,
        },
        {
            id: "outletRole",
            header: t("Outlet:Role"),
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
            id: "emailVerifiedAt",
            header: t("Email verified at"),
            searchValue: (user) => user.emailVerifiedAt ?? "",
            cell: (user) =>
                user.emailVerifiedAt ? (
                    user.emailVerifiedAt
                ) : (
                    <Badge variant="outline">{t("Not verified")}</Badge>
                ),
        },
        {
            id: "createdAt",
            header: t("Created"),
            dateValue: (user) => user.createdAt,
            cell: (user) => user.createdAt ?? "-",
        },
        {
            id: "actions",
            header: t("Actions"),
            className: "text-right",
            cell: (user) => (
                <div className="flex items-center justify-end gap-1">
                    {user.emailVerifiedAt ? (
                        <Button
                            variant="ghost"
                            size="sm"
                            aria-label={`${t("Deactivate")} ${user.username}`}
                            title={t("Deactivate")}
                            onClick={() =>
                                router.post(
                                    deactivate.url(user.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <UserX className="h-4 w-4" />
                        </Button>
                    ) : (
                        <>
                            <Button
                                variant="ghost"
                                size="sm"
                                aria-label={`${t("Activate")} ${user.username}`}
                                title={t("Activate")}
                                onClick={() =>
                                    router.post(
                                        activate.url(user.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <UserCheck className="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                aria-label={`${t("Send verification email")} ${user.username}`}
                                title={t("Send verification email")}
                                onClick={() =>
                                    router.post(
                                        verificationNotification.url(user.id),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <MailCheck className="h-4 w-4" />
                            </Button>
                        </>
                    )}
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={edit(user.id)}
                            aria-label={`${t("Edit")} ${user.username}`}
                        >
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title={t("Users")} />
            <DataTable
                size="lg"
                title={t("Users")}
                description={t("All users with their outlet and role")}
                actions={
                    <Button asChild data-test="admin-users-new-button">
                        <Link href={create()}>
                            <Plus /> {t("New user")}
                        </Link>
                    </Button>
                }
                columns={columns}
                rows={users}
                rowKey={(user) => user.id}
                rowTestId="admin-user-row"
                searchPlaceholder={t("Search users...")}
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
