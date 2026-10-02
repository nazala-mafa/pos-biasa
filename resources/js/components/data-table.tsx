import { ChevronLeft, ChevronRight, Columns3, Download } from "lucide-react";
import { useMemo, useState } from "react";
import type { ReactNode } from "react";
import Heading from "@/components/heading";
import PageContainer from "@/components/page-container";
import type { PageSize } from "@/components/page-container";
import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { useTranslation } from "@/hooks/use-translation";

export type DataTableColumn<T> = {
    id: string;
    header: string;
    cell: (row: T) => ReactNode;
    /** Plain-text value used by the search filter. */
    searchValue?: (row: T) => string;
    /** Value written to the exported file; falls back to the search value. */
    exportValue?: (row: T) => string | number;
    /** ISO date string; when any column defines it, a date range filter is shown. */
    dateValue?: (row: T) => string | null;
    hiddenByDefault?: boolean;
    className?: string;
};

type DataTableProps<T> = {
    title: string;
    description?: string;
    actions?: ReactNode;
    /** Max width: sm (little data), md (medium), lg (a lot). Defaults to lg. */
    size?: PageSize;
    columns: DataTableColumn<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    rowTestId?: string;
    searchPlaceholder?: string;
    /** When set, an export button downloads the filtered rows and visible columns as CSV. */
    exportFilename?: string;
    /** Rendered above the table with the rows that match the current filters. */
    summary?: (filteredRows: T[]) => ReactNode;
};

const csvCell = (value: string | number): string => {
    const text = String(value);

    return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
};

const LIMIT_OPTIONS = [10, 25, 50, 100];

export default function DataTable<T>({
    title,
    description,
    actions,
    size = "lg",
    columns,
    rows,
    rowKey,
    rowTestId,
    searchPlaceholder = "Search...",
    exportFilename,
    summary,
}: DataTableProps<T>) {
    const { t } = useTranslation();
    const [search, setSearch] = useState("");
    const [dateFrom, setDateFrom] = useState("");
    const [dateTo, setDateTo] = useState("");
    const [limit, setLimit] = useState(LIMIT_OPTIONS[0]);
    const [page, setPage] = useState(1);
    const [hiddenColumns, setHiddenColumns] = useState<string[]>(
        columns.filter((column) => column.hiddenByDefault).map((c) => c.id),
    );

    const dateColumn = columns.find((column) => column.dateValue);
    const visibleColumns = columns.filter(
        (column) => !hiddenColumns.includes(column.id),
    );

    const filteredRows = useMemo(() => {
        const term = search.trim().toLowerCase();

        return rows.filter((row) => {
            if (term !== "") {
                const haystack = columns
                    .map((column) => column.searchValue?.(row) ?? "")
                    .join(" ")
                    .toLowerCase();

                if (!haystack.includes(term)) {
                    return false;
                }
            }

            if (dateColumn?.dateValue && (dateFrom !== "" || dateTo !== "")) {
                const date = dateColumn.dateValue(row)?.slice(0, 10);

                if (!date) {
                    return false;
                }

                if (dateFrom !== "" && date < dateFrom) {
                    return false;
                }

                if (dateTo !== "" && date > dateTo) {
                    return false;
                }
            }

            return true;
        });
    }, [rows, columns, search, dateColumn, dateFrom, dateTo]);

    const lastPage = Math.max(1, Math.ceil(filteredRows.length / limit));
    const currentPage = Math.min(page, lastPage);
    const offset = (currentPage - 1) * limit;
    const pageRows = filteredRows.slice(offset, offset + limit);

    const toggleColumn = (id: string, visible: boolean) => {
        setHiddenColumns((current) =>
            visible ? current.filter((c) => c !== id) : [...current, id],
        );
    };

    const exportCsv = () => {
        const lines = [
            visibleColumns.map((column) => csvCell(t(column.header))).join(","),
            ...filteredRows.map((row) =>
                visibleColumns
                    .map((column) =>
                        csvCell(
                            column.exportValue?.(row) ??
                                column.searchValue?.(row) ??
                                "",
                        ),
                    )
                    .join(","),
            ),
        ];
        const blob = new Blob(["\uFEFF" + lines.join("\r\n")], {
            type: "text/csv;charset=utf-8",
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");

        link.href = url;
        link.download = `${exportFilename}-${new Date().toISOString().slice(0, 10)}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    };

    return (
        <PageContainer size={size} className="flex flex-col space-y-6 p-4">
            <div className="flex items-center justify-between gap-4">
                <Heading
                    variant="small"
                    title={title}
                    description={description}
                />
                {actions}
            </div>

            <div className="flex flex-wrap items-end gap-3">
                <Input
                    type="search"
                    aria-label={t("Search")}
                    placeholder={t(searchPlaceholder)}
                    value={search}
                    onChange={(event) => {
                        setSearch(event.target.value);
                        setPage(1);
                    }}
                    className="w-full sm:w-64"
                />

                {dateColumn ? (
                    <div className="flex items-center gap-2">
                        <Input
                            type="date"
                            aria-label={`${t(dateColumn.header)} ${t("from")}`}
                            value={dateFrom}
                            max={dateTo || undefined}
                            onChange={(event) => {
                                setDateFrom(event.target.value);
                                setPage(1);
                            }}
                            className="w-auto"
                        />
                        <span className="text-sm text-muted-foreground">–</span>
                        <Input
                            type="date"
                            aria-label={`${t(dateColumn.header)} ${t("to")}`}
                            value={dateTo}
                            min={dateFrom || undefined}
                            onChange={(event) => {
                                setDateTo(event.target.value);
                                setPage(1);
                            }}
                            className="w-auto"
                        />
                    </div>
                ) : null}

                <div className="ml-auto flex items-center gap-2">
                    {exportFilename ? (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={exportCsv}
                            disabled={filteredRows.length === 0}
                            data-test="datatable-export-button"
                        >
                            <Download /> {t("Export")}
                        </Button>
                    ) : null}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="sm">
                                <Columns3 /> {t("Columns")}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuLabel>
                                {t("Toggle columns")}
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {columns.map((column) => (
                                <DropdownMenuCheckboxItem
                                    key={column.id}
                                    checked={!hiddenColumns.includes(column.id)}
                                    onSelect={(event) => event.preventDefault()}
                                    onCheckedChange={(checked) =>
                                        toggleColumn(column.id, checked)
                                    }
                                >
                                    {t(column.header)}
                                </DropdownMenuCheckboxItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            {summary ? summary(filteredRows) : null}

            <div className="overflow-x-auto rounded-xl border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50 text-left">
                        <tr>
                            {visibleColumns.map((column) => (
                                <th
                                    key={column.id}
                                    className={`p-3 font-medium ${column.className ?? ""}`}
                                >
                                    {t(column.header)}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {pageRows.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={Math.max(visibleColumns.length, 1)}
                                    className="p-6 text-center text-muted-foreground"
                                >
                                    {t("No data found.")}
                                </td>
                            </tr>
                        ) : (
                            pageRows.map((row) => (
                                <tr
                                    key={rowKey(row)}
                                    data-test={rowTestId}
                                    className="border-t"
                                >
                                    {visibleColumns.map((column) => (
                                        <td
                                            key={column.id}
                                            className={`p-3 ${column.className ?? ""}`}
                                        >
                                            {column.cell(row)}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
                <div className="flex items-center gap-2">
                    <span className="text-muted-foreground">{t("Rows per page")}</span>
                    <Select
                        value={String(limit)}
                        onValueChange={(value) => {
                            setLimit(Number(value));
                            setPage(1);
                        }}
                    >
                        <SelectTrigger
                            size="sm"
                            className="w-20"
                            aria-label={t("Rows per page")}
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent side="top" avoidCollisions className="min-h-40">
                            {LIMIT_OPTIONS.map((option) => (
                                <SelectItem key={option} value={String(option)}>
                                    {option}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex items-center gap-3">
                    <span className="text-muted-foreground">
                        {filteredRows.length === 0
                            ? t("0 results")
                            : t(":from–:to of :total", {
                                  from: offset + 1,
                                  to: offset + pageRows.length,
                                  total: filteredRows.length,
                              })}
                    </span>
                    <span className="text-muted-foreground">
                        {t("Page :current of :last", {
                            current: currentPage,
                            last: lastPage,
                        })}
                    </span>
                    <div className="flex gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            aria-label={t("Previous page")}
                            disabled={currentPage <= 1}
                            onClick={() => setPage(currentPage - 1)}
                        >
                            <ChevronLeft />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            aria-label={t("Next page")}
                            disabled={currentPage >= lastPage}
                            onClick={() => setPage(currentPage + 1)}
                        >
                            <ChevronRight />
                        </Button>
                    </div>
                </div>
            </div>
        </PageContainer>
    );
}
