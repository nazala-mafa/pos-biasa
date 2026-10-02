export type ReportSummaryItem = { label: string; value: string };

export default function ReportSummary({
    items,
}: {
    items: ReportSummaryItem[];
}) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {items.map((item) => (
                <div key={item.label} className="rounded-xl border p-4">
                    <p className="text-sm text-muted-foreground">
                        {item.label}
                    </p>
                    <p className="text-lg font-semibold">{item.value}</p>
                </div>
            ))}
        </div>
    );
}
