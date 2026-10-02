import { Link, usePage } from "@inertiajs/react";
import {
    Database,
    LayoutGrid,
    Package,
    Settings,
    ShieldCheck,
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
import { index as salesIndex } from "@/routes/sales";
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

    const groupItems = (
        items: (NavItem & { permission: string })[],
    ): NavItem[] =>
        items
            .filter((item) => permissions.includes(item.permission))
            .map(({ permission: _permission, ...item }) => item);

    const posItems = groupItems([
        {
            title: "Penjualan",
            href: salesIndex(team),
            permission: "sales.view",
        },
        {
            title: "Produk / Barang",
            href: productsIndex(team),
            permission: "products.view",
        },
        {
            title: "Pelanggan",
            href: customersIndex(team),
            permission: "customers.view",
        },
        {
            title: "Supplier",
            href: suppliersIndex(team),
            permission: "suppliers.view",
        },
        {
            title: "Stok / Inventory",
            href: inventoryIndex(team),
            permission: "inventory.view",
        },
    ]);

    const administrationItems = groupItems([
        { title: "Users", href: usersIndex(team), permission: "users.view" },
        { title: "Roles", href: rolesIndex(team), permission: "roles.view" },
    ]);

    const mainNavItems: NavItem[] = [
        {
            title: "Dashboard",
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(posItems.length > 0
            ? [
                  {
                      title: "POS",
                      href: posItems[0].href,
                      icon: Package,
                      children: posItems,
                  },
              ]
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
        ...(administrationItems.length > 0
            ? [
                  {
                      title: "Administration",
                      href: administrationItems[0].href,
                      icon: ShieldCheck,
                      children: administrationItems,
                  },
              ]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        ...(page.props.auth.user.is_superadmin
            ? [{ title: "Admin Panel", href: adminDashboard(), icon: Settings }]
            : []),
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
