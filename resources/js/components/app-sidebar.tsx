import { Link, usePage } from "@inertiajs/react";
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    Settings,
    ShieldCheck,
    Users,
} from "lucide-react";
import AppLogo from "@/components/app-logo";
import { NavFooter } from "@/components/nav-footer";
import { NavMain } from "@/components/nav-main";
import { NavUser } from "@/components/nav-user";
import { TeamSwitcher } from "@/components/team-switcher";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { dashboard } from "@/routes";
import { dashboard as adminDashboard } from "@/routes/admin";
import { index as rolesIndex } from "@/routes/roles";
import { index as usersIndex } from "@/routes/users";
import type { NavItem } from "@/types";

export function AppSidebar() {
    const page = usePage();
    const dashboardUrl = page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug)
        : "/";

    const permissions = page.props.auth.permissions;

    const mainNavItems: NavItem[] = [
        {
            title: "Dashboard",
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(page.props.auth.user.is_superadmin
            ? [{ title: "Admin Panel", href: adminDashboard(), icon: Settings }]
            : []),
        ...(permissions.includes("users.view")
            ? [{ title: "Users", href: usersIndex(), icon: Users }]
            : []),
        ...(permissions.includes("roles.view")
            ? [{ title: "Roles", href: rolesIndex(), icon: ShieldCheck }]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        {
            title: "Repository",
            href: "https://github.com/laravel/react-starter-kit",
            icon: FolderGit2,
        },
        {
            title: "Documentation",
            href: "https://laravel.com/docs/starter-kits#react",
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
