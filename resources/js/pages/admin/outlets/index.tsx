import { Head, Link } from "@inertiajs/react";
import { ExternalLink, Pencil, Plus, Trash2 } from "lucide-react";
import { useState } from "react";
import DataTable from "@/components/data-table";
import type { DataTableColumn } from "@/components/data-table";
import DeleteConfirmModal from "@/components/delete-confirm-modal";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { dashboard as outletDashboard } from "@/routes";
import { dashboard } from "@/routes/admin";
import {
    create,
    destroy,
    edit,
    index,
} from "@/routes/admin/outlets";

type OutletRow = {
    slug: string;
    name: string;
    membersCount: number;
    isDefault: boolean;
    createdAt: string | null;
};

export default function OutletsIndex({ outlets }: { outlets: OutletRow[] }) {
    const [outletToDelete, setOutletToDelete] = useState<OutletRow | null>(
        null,
    );

    const columns: DataTableColumn<OutletRow>[] = [
        {
            id: "name",
            header: "Name",
            searchValue: (outlet) => outlet.name,
            cell: (outlet) => (
                <div className="flex items-center gap-2">
                    <span className="font-medium">{outlet.name}</span>
                    {outlet.isDefault ? (
                        <Badge variant="secondary">Default</Badge>
                    ) : null}
                </div>
            ),
        },
        {
            id: "users",
            header: "Users",
            cell: (outlet) => outlet.membersCount,
        },
        {
            id: "createdAt",
            header: "Created",
            dateValue: (outlet) => outlet.createdAt,
            cell: (outlet) => outlet.createdAt ?? "-",
        },
        {
            id: "actions",
            header: "Actions",
            className: "text-right",
            cell: (outlet) => (
                <div className="flex items-center justify-end gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={outletDashboard(outlet.slug)}
                            aria-label={`Open ${outlet.name}`}
                        >
                            <ExternalLink className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={edit(outlet.slug)}
                            aria-label={`Edit ${outlet.name}`}
                        >
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                    {!outlet.isDefault ? (
                        <>
                            <Button
                                variant="ghost"
                                size="sm"
                                aria-label={`Delete ${outlet.name}`}
                                onClick={() => setOutletToDelete(outlet)}
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        </>
                    ) : null}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Outlets" />

            <DataTable
                size="sm"
                title="Outlets"
                description="Manage your outlets (branches)"
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <Button asChild data-test="outlets-new-button">
                            <Link href={create()}>
                                <Plus /> New outlet
                            </Link>
                        </Button>
                    </div>
                }
                columns={columns}
                rows={outlets}
                rowKey={(outlet) => outlet.slug}
                rowTestId="outlet-row"
                searchPlaceholder="Search outlets..."
            />

            <DeleteConfirmModal
                title="Delete outlet"
                name={outletToDelete?.name ?? null}
                url={outletToDelete ? destroy.url(outletToDelete.slug) : null}
                open={outletToDelete !== null}
                onOpenChange={(open) => !open && setOutletToDelete(null)}
            />
        </>
    );
}

OutletsIndex.layout = {
    breadcrumbs: [
        { title: "Admin", href: dashboard() },
        { title: "Outlets", href: index() },
    ],
};
