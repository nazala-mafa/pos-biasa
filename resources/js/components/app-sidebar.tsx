import { Link, usePage } from "@inertiajs/react";
import {
    BookOpen,
    Boxes,
    Database,
    FolderGit2,
    LayoutGrid,
    Package,
    Settings,
    ShieldCheck,
    Truck,
    UserRound,
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
import { index as customersIndex } from "@/routes/customers";
import { index as inventoryIndex } from "@/routes/inventory";
import { index as productsIndex } from "@/routes/products";
import { index as suppliersIndex } from "@/routes/suppliers";
import { index as categoriesIndex } from "@/routes/master-data/categories";
import { index as pricesIndex } from "@/routes/master-data/prices";
import { index as taxesIndex } from "@/routes/master-data/taxes";
import { index as unitsIndex } from "@/routes/master-data/units";
import { index as rolesIndex } from "@/routes/roles";
import { index as usersIndex } from "@/routes/users";
import type { NavItem } from "@/types";

export function AppSidebar() {
    const page = usePage();
    const dashboardUrl = page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug)
        : "/";

    const permissions = page.props.auth.permissions;
    const team = page.props.currentTeam?.slug ?? "";

    const masterDataItems: NavItem[] = [
        {
            title: "Product Category",
            href: categoriesIndex(team),
            permission: "categories.view",
        },
        { title: "Unit", href: unitsIndex(team), permission: "units.view" },
        { title: "Price", href: pricesIndex(team), permission: "prices.view" },
        { title: "Tax", href: taxesIndex(team), permission: "taxes.view" },
    ]
        .filter((item) => permissions.includes(item.permission))
        .map(({ permission: _permission, ...item }) => item);

    const navItem = (
        permission: string,
        title: string,
        href: NavItem["href"],
        icon: NavItem["icon"],
    ): NavItem[] =>
        permissions.includes(permission) ? [{ title, href, icon }] : [];

    const mainNavItems: NavItem[] = [
        {
            title: "Dashboard",
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(page.props.auth.user.is_superadmin
            ? [{ title: "Admin Panel", href: adminDashboard(), icon: Settings }]
            : []),
        ...(masterDataItems.length > 0
            ? [
                  {
                      title: "Master Data",
                      href: masterDataItems[0].href,
                      icon: Database,
                      children: masterDataItems,
                  },
              ]
            : []),
        ...navItem(
            "products.view",
            "Produk / Barang",
            productsIndex(team),
            Package,
        ),
        ...navItem(
            "customers.view",
            "Pelanggan",
            customersIndex(team),
            UserRound,
        ),
        ...navItem("suppliers.view", "Supplier", suppliersIndex(team), Truck),
        ...navItem(
            "inventory.view",
            "Stok / Inventory",
            inventoryIndex(team),
            Boxes,
        ),
        ...(permissions.includes("users.view")
            ? [{ title: "Users", href: usersIndex(team), icon: Users }]
            : []),
        ...(permissions.includes("roles.view")
            ? [{ title: "Roles", href: rolesIndex(team), icon: ShieldCheck }]
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
