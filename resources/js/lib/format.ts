const currency = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
});

const quantity = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 });

export const formatCurrency = (value: number): string => currency.format(value);

export const formatQuantity = (value: number): string => quantity.format(value);
