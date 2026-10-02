import { Deferred, Head, Link, router, usePoll } from '@inertiajs/react';
import {
    AlertTriangle,
    Banknote,
    Clock,
    Receipt,
    RefreshCw,
    ShoppingCart,
    Wallet,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import NativeSelect from '@/components/native-select';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useCurrentTeamSlug } from '@/hooks/use-current-team-slug';
import { useTranslation } from '@/hooks/use-translation';
import { formatCurrency, formatQuantity } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as inventoryIndex } from '@/routes/inventory';
import { create as saleCreate, index as salesIndex, show as saleShow } from '@/routes/sales';
import { index as shiftsIndex } from '@/routes/shifts';
import type { DashboardInvitation } from '@/types';

type Period = 'today' | '7d' | '30d';

type DashboardData = {
    period: Period;
    generatedAt: string;
    kpis: {
        sales: number;
        transactions: number;
        cashIn: number;
        receivables: number;
    };
    chart: { date: string; label: string; total: number; transactions: number }[];
    shifts: {
        id: number;
        cashier: string | null;
        openedAt: string;
        openingCash: number;
        salesTotal: number;
        salesCount: number;
    }[];
    recentSales: {
        id: number;
        invoice: string;
        customer: string | null;
        cashier: string | null;
        total: number;
        createdAt: string;
    }[];
    topProducts: {
        productId: number | null;
        name: string;
        quantity: number;
        revenue: number;
    }[];
    lowStock: {
        count: number;
        items: {
            id: number;
            name: string;
            sku: string;
            stock: number;
            minStock: number;
            unit: string | null;
        }[];
    };
    activities: {
        type: string;
        title: string;
        detail: string | null;
        at: string;
    }[];
};

type Props = {
    pendingInvitations?: DashboardInvitation[];
    period: Period;
    dashboard?: DashboardData;
};

const POLL_INTERVAL_MS = 60_000;

const formatTime = (iso: string, withDate = false): string =>
    new Date(iso).toLocaleString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        ...(withDate ? { day: 'numeric', month: 'short' } : {}),
    });

function Section({
    title,
    action,
    children,
    className,
}: {
    title: string;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <Card className={cn('gap-4 py-5', className)}>
            <CardHeader className="flex flex-row items-center justify-between px-5">
                <CardTitle className="text-base">{title}</CardTitle>
                {action}
            </CardHeader>
            <CardContent className="px-5">{children}</CardContent>
        </Card>
    );
}

function Empty({ message }: { message: string }) {
    return (
        <p className="py-8 text-center text-sm text-muted-foreground">
            {message}
        </p>
    );
}

function KpiCard({
    label,
    value,
    hint,
    icon: Icon,
    primary = false,
    warn = false,
}: {
    label: string;
    value: string;
    hint?: string;
    icon: typeof Wallet;
    primary?: boolean;
    warn?: boolean;
}) {
    return (
        <Card
            className={cn(
                'gap-2 py-5',
                primary && 'border-primary/30 bg-primary/5',
            )}
        >
            <CardContent className="px-5">
                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    {label}
                    <Icon className="size-4" />
                </div>
                <p
                    className={cn(
                        'mt-2 font-semibold tracking-tight tabular-nums',
                        primary ? 'text-3xl' : 'text-2xl',
                        warn && 'text-amber-600 dark:text-amber-400',
                    )}
                >
                    {value}
                </p>
                {hint && (
                    <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
                )}
            </CardContent>
        </Card>
    );
}

function SalesChart({ data }: { data: DashboardData['chart'] }) {
    const { t } = useTranslation();
    const max = Math.max(...data.map((day) => day.total), 1);
    const hasSales = data.some((day) => day.total > 0);

    if (!hasSales) {
        return <Empty message={t('No sales in this period yet.')} />;
    }

    return (
        <div
            className="flex h-56 items-end gap-1.5 sm:gap-2"
            role="img"
            aria-label={t('Sales chart')}
        >
            {data.map((day, index) => (
                <div
                    key={day.date}
                    className="group relative flex h-full min-w-0 flex-1 flex-col justify-end gap-2"
                >
                    <div
                        className="w-full rounded-t-md bg-primary/80 transition-colors group-hover:bg-primary"
                        style={{
                            height: `${Math.max((day.total / max) * 100, day.total > 0 ? 3 : 0)}%`,
                        }}
                        title={`${day.label}: ${formatCurrency(day.total)} (${day.transactions})`}
                    />
                    <span
                        className={cn(
                            'truncate text-center text-[10px] text-muted-foreground sm:text-xs',
                            data.length > 7 && index % 5 !== 0 && 'invisible',
                        )}
                    >
                        {day.label}
                    </span>
                </div>
            ))}
        </div>
    );
}

function DashboardSkeleton() {
    return (
        <div className="space-y-4" aria-busy="true">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {Array.from({ length: 4 }).map((_, index) => (
                    <Skeleton key={index} className="h-28 rounded-xl" />
                ))}
            </div>
            <div className="grid gap-4 lg:grid-cols-3">
                <Skeleton className="h-72 rounded-xl lg:col-span-2" />
                <Skeleton className="h-72 rounded-xl" />
            </div>
            <div className="grid gap-4 lg:grid-cols-2">
                <Skeleton className="h-64 rounded-xl" />
                <Skeleton className="h-64 rounded-xl" />
            </div>
        </div>
    );
}

function DashboardContent({ data }: { data?: DashboardData }) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    if (!data) {
        return <DashboardSkeleton />;
    }

    const periodLabel = {
        today: t('Today'),
        '7d': t('Last 7 days'),
        '30d': t('Last 30 days'),
    }[data.period];

    return (
        <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    primary
                    label={`${t('Sales')} · ${periodLabel}`}
                    value={formatCurrency(data.kpis.sales)}
                    icon={Wallet}
                />
                <KpiCard
                    label={t('Total transactions')}
                    value={formatQuantity(data.kpis.transactions)}
                    hint={periodLabel}
                    icon={Receipt}
                />
                <KpiCard
                    label={t('Cash in')}
                    value={formatCurrency(data.kpis.cashIn)}
                    hint={periodLabel}
                    icon={Banknote}
                />
                <KpiCard
                    label={t('Receivables')}
                    value={formatCurrency(data.kpis.receivables)}
                    hint={t('Unpaid invoice balance')}
                    icon={Clock}
                    warn={data.kpis.receivables > 0}
                />
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <Section
                    title={
                        data.chart.length > 7
                            ? t('Sales last 30 days')
                            : t('Sales last 7 days')
                    }
                    className="lg:col-span-2"
                >
                    <SalesChart data={data.chart} />
                </Section>

                <Section
                    title={t('Active shifts')}
                    action={
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={shiftsIndex(team)}>{t('View all')}</Link>
                        </Button>
                    }
                >
                    {data.shifts.length === 0 ? (
                        <div className="space-y-3 py-4 text-center">
                            <p className="text-sm text-muted-foreground">
                                {t('No shift is open right now.')}
                            </p>
                            <Button size="sm" variant="outline" asChild>
                                <Link href={shiftsIndex(team)}>
                                    {t('Open a shift')}
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <ul className="space-y-3">
                            {data.shifts.map((shift) => (
                                <li
                                    key={shift.id}
                                    className="rounded-lg border p-3 text-sm"
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="font-medium">
                                            {shift.cashier ?? '-'}
                                        </span>
                                        <Badge>{t('Open')}</Badge>
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {t('Since')} {formatTime(shift.openedAt)}{' '}
                                        · {t('Opening cash')}{' '}
                                        {formatCurrency(shift.openingCash)}
                                    </p>
                                    <p className="mt-1 tabular-nums">
                                        {formatCurrency(shift.salesTotal)}{' '}
                                        <span className="text-xs text-muted-foreground">
                                            ({shift.salesCount} {t('transactions')})
                                        </span>
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Section
                    title={t('Recent transactions')}
                    action={
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={salesIndex(team)}>{t('View all')}</Link>
                        </Button>
                    }
                >
                    {data.recentSales.length === 0 ? (
                        <div className="space-y-3 py-4 text-center">
                            <p className="text-sm text-muted-foreground">
                                {t('No transactions yet.')}
                            </p>
                            <Button size="sm" asChild>
                                <Link href={saleCreate(team)}>
                                    <ShoppingCart /> {t('New sale')}
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <ul className="divide-y">
                            {data.recentSales.map((sale) => (
                                <li key={sale.id}>
                                    <Link
                                        href={saleShow([team, sale.id])}
                                        className="flex items-center justify-between gap-3 py-2.5 text-sm hover:bg-muted/50"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {sale.invoice}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {formatTime(sale.createdAt, true)} ·{' '}
                                                {sale.customer ?? t('Walk-in customer')}
                                            </p>
                                        </div>
                                        <span className="font-medium tabular-nums">
                                            {formatCurrency(sale.total)}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>

                <Section title={`${t('Top products')} · ${periodLabel}`}>
                    {data.topProducts.length === 0 ? (
                        <Empty message={t('No products sold in this period yet.')} />
                    ) : (
                        <ol className="space-y-3">
                            {data.topProducts.map((product, index) => (
                                <li
                                    key={`${product.productId}-${product.name}`}
                                    className="flex items-center gap-3 text-sm"
                                >
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                        {index + 1}
                                    </span>
                                    <span className="min-w-0 flex-1 truncate font-medium">
                                        {product.name}
                                    </span>
                                    <span className="text-right">
                                        <span className="block tabular-nums">
                                            {formatQuantity(product.quantity)}
                                        </span>
                                        <span className="block text-xs text-muted-foreground tabular-nums">
                                            {formatCurrency(product.revenue)}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ol>
                    )}
                </Section>

                <Section
                    title={t('Low stock')}
                    action={
                        data.lowStock.count > 0 && (
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={inventoryIndex(team)}>
                                    {t('View all')} ({data.lowStock.count})
                                </Link>
                            </Button>
                        )
                    }
                >
                    {data.lowStock.items.length === 0 ? (
                        <Empty message={t('All products have enough stock.')} />
                    ) : (
                        <ul className="divide-y">
                            {data.lowStock.items.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between gap-3 py-2.5 text-sm"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {item.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {item.sku} · {t('Min')}{' '}
                                            {formatQuantity(item.minStock)}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            item.stock <= 0
                                                ? 'destructive'
                                                : 'secondary'
                                        }
                                    >
                                        {formatQuantity(item.stock)} {item.unit}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>

                <Section title={t('Recent activity')}>
                    {data.activities.length === 0 ? (
                        <Empty message={t('No activity yet.')} />
                    ) : (
                        <ul className="space-y-3">
                            {data.activities.map((activity, index) => (
                                <li key={index} className="flex gap-3 text-sm">
                                    <span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary/60" />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate">{activity.title}</p>
                                        {activity.detail && (
                                            <p className="truncate text-xs text-muted-foreground">
                                                {activity.detail}
                                            </p>
                                        )}
                                    </div>
                                    <span className="shrink-0 text-xs text-muted-foreground">
                                        {formatTime(activity.at, true)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>
            </div>
        </div>
    );
}

export default function Dashboard({
    pendingInvitations = [],
    period,
    dashboard: data,
}: Props) {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );
    const [refreshing, setRefreshing] = useState(false);
    const [failed, setFailed] = useState(false);

    usePoll(POLL_INTERVAL_MS, { only: ['dashboard'] });

    const load = (nextPeriod: Period) => {
        setFailed(false);
        router.get(
            dashboard.url(team),
            { period: nextPeriod },
            {
                only: ['dashboard', 'period'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => setRefreshing(true),
                onFinish: () => setRefreshing(false),
                onError: () => setFailed(true),
                onNetworkError: () => setFailed(true),
                onHttpException: () => setFailed(true),
            },
        );
    };

    return (
        <>
            <Head title={t('Dashboard')} />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="w-full max-w-7xl space-y-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="space-y-0.5">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {t('Dashboard')}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Your outlet at a glance. Updates every minute.')}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <NativeSelect
                            value={period}
                            onChange={(event) => load(event.target.value as Period)}
                            aria-label={t('Period')}
                            className="w-40"
                        >
                            <option value="today">{t('Today')}</option>
                            <option value="7d">{t('Last 7 days')}</option>
                            <option value="30d">{t('Last 30 days')}</option>
                        </NativeSelect>
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => load(period)}
                            disabled={refreshing}
                            aria-label={t('Refresh')}
                        >
                            <RefreshCw className={cn(refreshing && 'animate-spin')} />
                        </Button>
                    </div>
                </div>

                {failed && (
                    <Alert variant="destructive">
                        <AlertTriangle />
                        <AlertTitle>{t('Failed to load the dashboard.')}</AlertTitle>
                        <AlertDescription className="flex items-center gap-3">
                            {t('Check your connection and try again.')}
                            <Button size="sm" variant="outline" onClick={() => load(period)}>
                                {t('Try again')}
                            </Button>
                        </AlertDescription>
                    </Alert>
                )}

                <div className={cn('transition-opacity', refreshing && 'opacity-60')}>
                    <Deferred data="dashboard" fallback={<DashboardSkeleton />}>
                        <DashboardContent data={data} />
                    </Deferred>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
