import {
  validateCIN,
  validatePhoneNumber,
  validatePostalCode,
  validateTaxID,
} from "./index";

describe("Validators", () => {
  describe("validateCIN", () => {
    it("should validate correct CIN numbers", () => {
      expect(validateCIN("12345678")).toBe(true);
      expect(validateCIN("00123456")).toBe(true);
    });

    it("should reject invalid CIN numbers", () => {
      expect(validateCIN("1234567")).toBe(false); // Too short
      expect(validateCIN("123456789")).toBe(false); // Too long
      expect(validateCIN("1234567a")).toBe(false); // Contains letter
    });
  });

  describe("validatePhoneNumber", () => {
    it("should validate correct phone numbers", () => {
      expect(validatePhoneNumber("20123456")).toBe(true);
      expect(validatePhoneNumber("50123456")).toBe(true);
      expect(validatePhoneNumber("90123456")).toBe(true);
    });

    it("should reject invalid phone numbers", () => {
      expect(validatePhoneNumber("10123456")).toBe(false); // Invalid prefix
      expect(validatePhoneNumber("2012345")).toBe(false); // Too short
      expect(validatePhoneNumber("201234567")).toBe(false); // Too long
    });
  });

  describe("validatePostalCode", () => {
    it("should validate correct postal codes", () => {
      expect(validatePostalCode("1000")).toBe(true);
      expect(validatePostalCode("2050")).toBe(true);
    });

    it("should reject invalid postal codes", () => {
      expect(validatePostalCode("100")).toBe(false); // Too short
      expect(validatePostalCode("10000")).toBe(false); // Too long
      expect(validatePostalCode("100a")).toBe(false); // Contains letter
    });
  });

  describe("validateTaxID", () => {
    it("should validate correct tax IDs", () => {
      expect(validateTaxID("1234567A/P/M/000")).toBe(true);
      expect(validateTaxID("7654321B/N/P/001")).toBe(true);
    });

    it("should reject invalid tax IDs", () => {
      expect(validateTaxID("123456A/P/M/000")).toBe(false); // Wrong format
      expect(validateTaxID("1234567A/P/M/0001")).toBe(false); // Wrong format
      expect(validateTaxID("1234567A-P-M-000")).toBe(false); // Wrong separators
    });
  });
});
