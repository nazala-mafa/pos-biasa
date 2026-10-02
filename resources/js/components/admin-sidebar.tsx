import { Link } from "@inertiajs/react";
import { ArrowLeft, LayoutGrid, Store, Users } from "lucide-react";
import AppLogo from "@/components/app-logo";
import { NavMain } from "@/components/nav-main";
import { NavUser } from "@/components/nav-user";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { dashboard as adminDashboard } from "@/routes/admin";
import { index as outletsIndex } from "@/routes/admin/outlets";
import { index as usersIndex } from "@/routes/admin/users";
import type { NavItem } from "@/types";
import { useTranslation } from "@/hooks/use-translation";

const adminNavItems: NavItem[] = [
    { title: "Dashboard", href: adminDashboard(), icon: LayoutGrid },
    { title: "Outlets", href: outletsIndex(), icon: Store },
    { title: "Users", href: usersIndex(), icon: Users },
];

export function AdminSidebar() {
    const { t } = useTranslation();
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={adminDashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            tooltip={{ children: "Back to app" }}
                        >
                            <Link href="/">
                                <ArrowLeft />
                                <span>{t("Back to app")}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
