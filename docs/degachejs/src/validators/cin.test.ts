import { validateCIN } from "./cin";

describe("CIN Validator", () => {
  describe("validateCIN", () => {
    it("should validate correct CIN numbers", () => {
      expect(validateCIN("12345678")).toBe(true);
      expect(validateCIN("00123456")).toBe(true);
    });

    it("should reject CINs with invalid length", () => {
      expect(validateCIN("1234567")).toBe(false); // Too short
      expect(validateCIN("123456789")).toBe(false); // Too long
    });

    it("should reject CINs with non-numeric characters", () => {
      expect(validateCIN("1234567A")).toBe(false);
      expect(validateCIN("ABCDEFGH")).toBe(false);
      expect(validateCIN("1234-678")).toBe(false);
      expect(validateCIN("1234|#•@")).toBe(false);
      expect(validateCIN("99999999")).toBe(false);
    });

    it("should reject empty strings and null values", () => {
      expect(validateCIN("")).toBe(false);
      // @ts-ignore - Testing invalid input
      expect(validateCIN(null)).toBe(false);
      // @ts-ignore - Testing invalid input
      expect(validateCIN(undefined)).toBe(false);
    });
  });
});
