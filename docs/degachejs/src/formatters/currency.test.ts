import { formatCurrency } from "./currency";

describe("Currency Formatter", () => {
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

    it("should format zero amount correctly", () => {
      const formatted = formatCurrency(0);
      expect(formatted).toContain("0,000");
      expect(formatted).toContain("دينار تونسي");
    });

    it("should format negative amounts correctly", () => {
      const formatted = formatCurrency(-500.75);
      expect(formatted).toContain("-500,750");
      expect(formatted).toContain("دينار تونسي");
    });

    it("should format large amounts with proper separators", () => {
      const formatted = formatCurrency(1000000.99);
      expect(formatted).toContain("1.000.000,990");
    });

    it("should handle both symbol and code options together", () => {
      // When both options are provided, code should take precedence
      const formatted = formatCurrency(100, { symbol: true, code: true });
      expect(formatted).toContain("TND");
      expect(formatted).not.toContain("د.ت");
    });
  });
});
