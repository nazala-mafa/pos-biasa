import { Head, Link, usePage } from "@inertiajs/react";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import DeleteConfirmModal from "@/components/delete-confirm-modal";
import { Button } from "@/components/ui/button";

export type MasterDataRow = {
    id: number;
    name: string;
    createdAt: string | null;
};

type Props<T extends MasterDataRow> = {
    title: string;
    description: string;
    itemLabel: string;
    permissionPrefix: string;
    rows: T[];
    extraColumns?: DataTableColumn<T>[];
    createUrl: string;
    editUrl: (id: number) => string;
    destroyUrl: (id: number) => string;
};

export default function MasterDataIndex<T extends MasterDataRow>({
    title,
    description,
    itemLabel,
    permissionPrefix,
    rows,
    extraColumns = [],
    createUrl,
    editUrl,
    destroyUrl,
}: Props<T>) {
    const { auth } = usePage().props;
    const [toDelete, setToDelete] = useState<T | null>(null);

    const can = (action: string) =>
        auth.permissions.includes(`${permissionPrefix}.${action}`);

    const columns: DataTableColumn<T>[] = [
        {
            id: "name",
            header: "Name",
            searchValue: (row) => row.name,
            cell: (row) => <span className="font-medium">{row.name}</span>,
        },
        ...extraColumns,
        {
            id: "createdAt",
            header: "Created",
            dateValue: (row) => row.createdAt,
            cell: (row) => row.createdAt ?? "-",
        },
        {
            id: "actions",
            header: "Actions",
            className: "text-right",
            cell: (row) => (
                <div className="flex items-center justify-end gap-2">
                    {can("update") ? (
                        <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={editUrl(row.id)}
                                aria-label={`Edit ${row.name}`}
                            >
                                <Pencil className="h-4 w-4" />
                            </Link>
                        </Button>
                    ) : null}
                    {can("delete") ? (
                        <Button
                            variant="ghost"
                            size="sm"
                            aria-label={`Delete ${row.name}`}
                            onClick={() => setToDelete(row)}
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
            <Head title={title} />

            <DataTable
                title={title}
                description={description}
                actions={
                    can("create") ? (
                        <Button
                            asChild
                            data-test={`${permissionPrefix}-new-button`}
                        >
                            <Link href={createUrl}>
                                <Plus /> New {itemLabel}
                            </Link>
                        </Button>
                    ) : null
                }
                columns={columns}
                rows={rows}
                rowKey={(row) => row.id}
                rowTestId={`${permissionPrefix}-row`}
                searchPlaceholder={`Search ${title.toLowerCase()}...`}
            />

            <DeleteConfirmModal
                title={`Delete ${itemLabel}`}
                name={toDelete?.name ?? null}
                url={toDelete ? destroyUrl(toDelete.id) : null}
                open={toDelete !== null}
                onOpenChange={(open) => !open && setToDelete(null)}
            />
        </>
    );
}
