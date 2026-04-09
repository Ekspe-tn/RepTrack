import { validateRIB, getBankFromRIB } from "./bank";
import { BANKS } from "../constants";

describe("Bank Validators", () => {
  describe("validateRIB", () => {
    it("should validate a correct RIB", () => {
      // Using ATB bank code (01) with valid format
      const validRIB = "01123456789012345678";
      expect(validateRIB(validRIB)).toBe(true);

      // Using BIAT bank code (08) with valid format
      const anotherValidRIB = "08123456789012345678";
      expect(validateRIB(anotherValidRIB)).toBe(true);
    });

    it("should reject RIB with invalid format", () => {
      // Too short
      expect(validateRIB("0123456789012345")).toBe(false);

      // Too long
      expect(validateRIB("012345678901234567890")).toBe(false);

      // Contains non-digit characters
      expect(validateRIB("0123456789a123456789")).toBe(false);
    });

    it("should reject RIB with non-existent bank code", () => {
      // Using non-existent bank code 99
      const invalidBankRIB = "99123456789012345678";
      expect(validateRIB(invalidBankRIB)).toBe(false);
    });

    it("should reject RIB with invalid branch code format", () => {
      // Using valid bank code (01) but invalid branch code (not 3 digits)
      const invalidBranchRIB = "0112345678901234567";
      expect(validateRIB(invalidBranchRIB)).toBe(false);
    });

    it("should reject RIB with invalid account number format", () => {
      // Using valid bank and branch codes but invalid account number
      const invalidAccountRIB = "01123abc456789012345";
      expect(validateRIB(invalidAccountRIB)).toBe(false);
    });

    it("should reject RIB with invalid key format", () => {
      // Using valid bank, branch, account but invalid key (not 2 digits)
      const invalidKeyRIB = "0112345678901234567a";
      expect(validateRIB(invalidKeyRIB)).toBe(false);
    });
  });

  describe("getBankFromRIB", () => {
    it("should return correct bank information for valid RIB", () => {
      // ATB bank code
      const atbRIB = "01123456789012345678";
      const atbBank = getBankFromRIB(atbRIB);
      expect(atbBank).toEqual(BANKS.ATB);

      // BIAT bank code
      const biatRIB = "08123456789012345678";
      const biatBank = getBankFromRIB(biatRIB);
      expect(biatBank).toEqual(BANKS.BIAT);
    });

    it("should return null for RIB with invalid format", () => {
      // Too short
      expect(getBankFromRIB("0123456789")).toBeNull();

      // Contains non-digit characters
      expect(getBankFromRIB("0123456789abcdefghij")).toBeNull();
    });

    it("should return null for RIB with non-existent bank code", () => {
      // Using non-existent bank code 99
      const nonExistentBankRIB = "99123456789012345678";
      expect(getBankFromRIB(nonExistentBankRIB)).toBeNull();
    });
  });
});
