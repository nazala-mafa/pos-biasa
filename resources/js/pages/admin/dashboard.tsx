import { Head } from "@inertiajs/react";
import Heading from "@/components/heading";
import { dashboard } from "@/routes/admin";
import { useTranslation } from "@/hooks/use-translation";

type Props = {
    stats: { users: number; superadmins: number; roles: number; outlets: number };
};

export default function AdminDashboard({ stats }: Props) {
    const { t } = useTranslation();
    const cards = [
        { label: t("Users"), value: stats.users },
        { label: t("Superadmins"), value: stats.superadmins },
        { label: t("Roles"), value: stats.roles },
        { label: t("Outlets"), value: stats.outlets },
    ];

    return (
        <>
            <Head title={t("Admin")} />
            <div className="flex flex-col space-y-6 p-4">
                <Heading
                    variant="small"
                    title={t("Admin panel")}
                    description={t("Overview of the whole system")}
                />
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map((card) => (
                        <div
                            key={card.label}
                            className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <p className="text-sm text-muted-foreground">
                                {card.label}
                            </p>
                            <p className="text-2xl font-semibold">
                                {card.value}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: "Admin", href: dashboard() }],
};
