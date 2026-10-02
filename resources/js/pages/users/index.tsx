import { Head, Link, usePage } from "@inertiajs/react";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import DeleteConfirmModal from "@/components/delete-confirm-modal";
import Heading from "@/components/heading";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { create, destroy, edit, index } from "@/routes/users";

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
};

export default function UsersIndex({ users }: { users: UserRow[] }) {
    const { auth } = usePage().props;
    const [userToDelete, setUserToDelete] = useState<UserRow | null>(null);

    const can = (permission: string) => auth.permissions.includes(permission);

    return (
        <>
            <Head title="Users" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Users"
                        description="Manage user accounts and their roles"
                    />

                    {can("users.create") ? (
                        <Button asChild data-test="users-new-button">
                            <Link href={create()}>
                                <Plus /> New user
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="space-y-3">
                    {users.map((user) => (
                        <div
                            key={user.id}
                            data-test="user-row"
                            className="flex items-center justify-between gap-4 rounded-lg border p-4"
                        >
                            <div>
                                <div className="font-medium">{user.name}</div>
                                <div className="text-sm text-muted-foreground">
                                    {user.email}
                                </div>
                                <div className="mt-2 flex flex-wrap gap-1">
                                    {user.roles.map((role) => (
                                        <Badge key={role} variant="secondary">
                                            {role}
                                        </Badge>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                {can("users.update") ? (
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link
                                            href={edit(user.id)}
                                            aria-label={`Edit ${user.name}`}
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                ) : null}
                                {can("users.delete") &&
                                user.id !== auth.user.id ? (
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
                        </div>
                    ))}
                </div>
            </div>

            <DeleteConfirmModal
                title="Delete user"
                name={userToDelete?.name ?? null}
                url={userToDelete ? destroy.url(userToDelete.id) : null}
                open={userToDelete !== null}
                onOpenChange={(open) => !open && setUserToDelete(null)}
            />
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: "Users", href: index() }],
};
