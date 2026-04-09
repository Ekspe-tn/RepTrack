/**
 * Formats a date according to Tunisian locale
 * @param date - The date to format
 * @param options - Intl.DateTimeFormat options
 * @returns formatted date string
 */
export const formatDate = (
  date: Date,
  options: Intl.DateTimeFormatOptions = {
    year: "numeric",
    month: "long",
    day: "numeric",
  },
): string => {
  const formatter = new Intl.DateTimeFormat(LOCALE, options);
  return formatter.format(date);
};

/**
 * Date locale
 */
export const LOCALE = "ar-TN";
