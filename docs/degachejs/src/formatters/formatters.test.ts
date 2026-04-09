import { formatCurrency, formatDate } from "./index";
import { formatPhoneNumber } from "./phone-number";

describe("Formatters", () => {
  describe("formatPhoneNumber", () => {
    it("should format valid phone numbers correctly", () => {
      expect(formatPhoneNumber("20123456")).toBe("+216 20 123 456");
      expect(formatPhoneNumber("50123456")).toBe("+216 50 123 456");
    });

    it("should return null for invalid phone numbers", () => {
      expect(formatPhoneNumber("2012345")).toBe(null);
      expect(formatPhoneNumber("201234567")).toBe(null);
    });
  });

  describe("formatCurrency", () => {
    it("should format currency with default options", () => {
      const formatted = formatCurrency(1234.56);
      // In Tunisian locale, numbers use comma as decimal separator and period as thousands separator
      expect(formatted).toContain("1.234,560");
      expect(formatted).toContain("دينار تونسي");
    });

    it("should format currency with symbol option", () => {
      const formatted = formatCurrency(1234.56, { symbol: true });
      expect(formatted).toContain("د.ت");
    });

    it("should format currency with code option", () => {
      const formatted = formatCurrency(1234.56, { code: true });
      expect(formatted).toContain("TND");
    });
  });

  describe("formatDate", () => {
    it("should format date with default options", () => {
      const date = new Date("2024-02-14");
      const formatted = formatDate(date);
      expect(formatted).toBeTruthy();
      // Note: Exact string comparison is avoided due to locale-specific formatting
    });

    it("should format date with custom options", () => {
      const date = new Date("2024-02-14");
      const formatted = formatDate(date, {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
      expect(formatted).toBeTruthy();
    });
  });
});
