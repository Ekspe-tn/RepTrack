import type { CurrencyFormatOptions } from "../types";
import { LOCALE } from "./date";

/**
 * Formats a monetary amount in Tunisian Dinar
 * @param amount - The amount to format
 * @param options - Formatting options
 * @returns formatted amount with currency symbol
 */
export const formatCurrency = (
  amount: number,
  options: CurrencyFormatOptions = {},
): string => {
  const formatter = new Intl.NumberFormat(LOCALE, {
    style: "currency",
    currency: "TND",
    currencyDisplay: options.code ? "code" : options.symbol ? "symbol" : "name",
  });
  return formatter.format(amount);
};
