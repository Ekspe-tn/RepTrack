import { validateCarPlate, getCarPlateInfo } from "./car-plate";

describe("Car Plate Validators", () => {
  describe("validateCarPlate", () => {
    it("should validate correct standard car plates", () => {
      expect(validateCarPlate("123 تونس 4567")).toBe(true);
      expect(validateCarPlate("12 تونس 3456")).toBe(true);
    });

    it("should validate correct special car plates", () => {
      expect(validateCarPlate("RS 123 تونس")).toBe(true);
      expect(validateCarPlate("RS 12 تونس")).toBe(true);
    });

    it("should normalize and validate car plates with extra spaces", () => {
      expect(validateCarPlate("123  تونس  4567")).toBe(true);
      expect(validateCarPlate(" 12 تونس 3456 ")).toBe(true);
      expect(validateCarPlate("RS  123  تونس")).toBe(true);
    });

    it("should normalize and validate car plates with lowercase letters for RS", () => {
      expect(validateCarPlate("rs 123 تونس")).toBe(true);
    });

    it("should reject car plates with invalid format", () => {
      expect(validateCarPlate("1234 تونس 4567")).toBe(false); // Too many digits in prefix
      expect(validateCarPlate("123 تونس 456")).toBe(false); // Too few digits in suffix
      expect(validateCarPlate("123 ALG 4567")).toBe(false); // Invalid region code
      expect(validateCarPlate("RS تونس 123")).toBe(false); // Invalid special format
      expect(validateCarPlate("RS 1234 تونس")).toBe(false); // Too many digits in special number
      expect(validateCarPlate("123 TUN 4567")).toBe(false); // Using Latin instead of Arabic
    });

    it("should reject empty strings and invalid inputs", () => {
      expect(validateCarPlate("")).toBe(false);
      // @ts-ignore - Testing invalid input
      expect(validateCarPlate(null)).toBe(false);
      // @ts-ignore - Testing invalid input
      expect(validateCarPlate(undefined)).toBe(false);
    });

    describe("with type option", () => {
      it("should validate only standard plates when type is 'standard'", () => {
        expect(validateCarPlate("123 تونس 4567", { type: "standard" })).toBe(
          true,
        );
        expect(validateCarPlate("RS 123 تونس", { type: "standard" })).toBe(
          false,
        );
      });

      it("should validate only special plates when type is 'special'", () => {
        expect(validateCarPlate("RS 123 تونس", { type: "special" })).toBe(true);
        expect(validateCarPlate("123 تونس 4567", { type: "special" })).toBe(
          false,
        );
      });

      it("should validate any valid plate when type is 'any'", () => {
        expect(validateCarPlate("123 تونس 4567", { type: "any" })).toBe(true);
        expect(validateCarPlate("RS 123 تونس", { type: "any" })).toBe(true);
      });
    });

    describe("with strict mode", () => {
      it("should validate correct car plates in strict mode", () => {
        expect(validateCarPlate("123 تونس 4567", { strict: true })).toBe(true);
        expect(validateCarPlate("RS 123 تونس", { strict: true })).toBe(true);
      });

      it("should reject car plates with extra spaces in strict mode", () => {
        expect(validateCarPlate("123  تونس  4567", { strict: true })).toBe(
          false,
        );
        expect(validateCarPlate(" 12 تونس 3456 ", { strict: true })).toBe(
          false,
        );
      });

      it("should reject car plates with Latin instead of Arabic in strict mode", () => {
        expect(validateCarPlate("123 TUN 4567", { strict: true })).toBe(false);
        expect(validateCarPlate("RS 123 TUN", { strict: true })).toBe(false);
      });

      it("should reject car plates with lowercase RS in strict mode", () => {
        expect(validateCarPlate("rs 123 تونس", { strict: true })).toBe(false);
      });
    });
  });

  describe("getCarPlateInfo", () => {
    it("should return correct information for standard car plates", () => {
      const info = getCarPlateInfo("123 تونس 4567");
      expect(info).toEqual({
        type: "standard",
        components: {
          prefix: "123",
          region: "تونس",
          suffix: "4567",
        },
      });
    });

    it("should return correct information for special car plates", () => {
      const info = getCarPlateInfo("RS 123 تونس");
      expect(info).toEqual({
        type: "special",
        components: {
          prefix: "RS",
          number: "123",
          region: "تونس",
        },
      });
    });

    it("should normalize and return information for car plates with extra spaces", () => {
      const info = getCarPlateInfo("123  تونس  4567");
      expect(info).toEqual({
        type: "standard",
        components: {
          prefix: "123",
          region: "تونس",
          suffix: "4567",
        },
      });
    });

    it("should normalize and return information for car plates with lowercase RS", () => {
      const info = getCarPlateInfo("rs 123 تونس");
      expect(info).toEqual({
        type: "special",
        components: {
          prefix: "RS",
          number: "123",
          region: "تونس",
        },
      });
    });

    it("should return null for invalid car plates", () => {
      expect(getCarPlateInfo("1234 تونس 4567")).toBeNull(); // Too many digits in prefix
      expect(getCarPlateInfo("123 ALG 4567")).toBeNull(); // Invalid region code
      expect(getCarPlateInfo("123 TUN 4567")).toBeNull(); // Using Latin instead of Arabic
      expect(getCarPlateInfo("")).toBeNull(); // Empty string
    });

    describe("with strict mode", () => {
      it("should return information for valid car plates in strict mode", () => {
        const info = getCarPlateInfo("123 تونس 4567", { strict: true });
        expect(info).toEqual({
          type: "standard",
          components: {
            prefix: "123",
            region: "تونس",
            suffix: "4567",
          },
        });
      });

      it("should return null for car plates with extra spaces in strict mode", () => {
        expect(getCarPlateInfo("123  تونس  4567", { strict: true })).toBeNull();
      });

      it("should return null for car plates with Latin instead of Arabic in strict mode", () => {
        expect(getCarPlateInfo("123 TUN 4567", { strict: true })).toBeNull();
      });

      it("should return null for car plates with lowercase RS in strict mode", () => {
        expect(getCarPlateInfo("rs 123 تونس", { strict: true })).toBeNull();
      });
    });
  });
});
