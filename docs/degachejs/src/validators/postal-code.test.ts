import { validatePostalCode } from "./postal-code";
import { POSTAL_CODES_MAP } from "../constants";

describe("Postal Code Validator", () => {
  describe("validatePostalCode", () => {
    it("should validate correct postal codes", () => {
      // Test a few known valid postal codes from different regions
      expect(validatePostalCode("1000")).toBe(true); // Tunis
      expect(validatePostalCode("2050")).toBe(true); // Hammam-Lif (Ben Arous)
      expect(validatePostalCode("3000")).toBe(true); // Sfax
      expect(validatePostalCode("4000")).toBe(true); // Sousse
      expect(validatePostalCode("5000")).toBe(true); // Monastir
      expect(validatePostalCode("8000")).toBe(true); // Nabeul
    });

    it("should validate all postal codes from the POSTAL_CODES_MAP", () => {
      // All postal codes in the map should be valid
      Object.keys(POSTAL_CODES_MAP).forEach((code) => {
        expect(validatePostalCode(code)).toBe(true);
      });
    });

    it("should reject postal codes with invalid format", () => {
      // Too short
      expect(validatePostalCode("100")).toBe(false);

      // Too long
      expect(validatePostalCode("10000")).toBe(false);

      // Contains non-digit characters
      expect(validatePostalCode("100A")).toBe(false);
      expect(validatePostalCode("A000")).toBe(false);
      expect(validatePostalCode("10-0")).toBe(false);
    });

    it("should reject postal codes that match the format but are not in the map", () => {
      // These have the right format (4 digits) but are not valid Tunisian postal codes
      expect(validatePostalCode("9999")).toBe(false);
      expect(validatePostalCode("0000")).toBe(false);
      expect(validatePostalCode("1234")).toBe(false); // Assuming this specific code is not in the map
    });

    it("should reject empty strings and invalid inputs", () => {
      expect(validatePostalCode("")).toBe(false);
      // @ts-ignore - Testing invalid input
      expect(validatePostalCode(null)).toBe(false);
      // @ts-ignore - Testing invalid input
      expect(validatePostalCode(undefined)).toBe(false);
    });
  });
});
