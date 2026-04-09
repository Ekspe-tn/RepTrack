/**
 * Regular expressions for car plate validation
 */
const REGEX = {
  // Format: 123 تونس 4567 or 12 تونس 3456
  STANDARD: /^(\d{2,3})\s+(تونس)\s+(\d{4})$/,
  // Format: RS 123 تونس or RS 12 تونس
  SPECIAL: /^(RS)\s+(\d{2,3})\s+(تونس)$/,
  // Format: 123 تونس 4567 or 12 تونس 3456 or RS 123 تونس or RS 12 تونس
  ANY: /^((\d{2,3})\s+(تونس)\s+(\d{4}))|((RS)\s+(\d{2,3})\s+(تونس))$/,
} as const;

/**
 * Options for car plate validation
 */
export interface CarPlateValidationOptions {
  /**
   * When true, enforces strict format validation:
   * - Spaces must be exactly as expected
   * - No extra characters allowed
   * - Must use the exact Arabic text "تونس"
   */
  strict?: boolean;

  /**
   * Type of car plate to validate
   * - 'standard': Regular car plates (e.g., 123 تونس 4567)
   * - 'special': Special car plates (e.g., RS 123 تونس)
   * - 'any': Any valid car plate format (default)
   */
  type?: "standard" | "special" | "any";
}

/**
 * Normalizes a car plate string by:
 * - Trimming whitespace
 * - Converting Latin characters to uppercase (for RS prefix)
 * - Normalizing spaces (replacing multiple spaces with single space)
 *
 * @param carPlate - The car plate to normalize
 * @returns normalized car plate string
 */
const normalizePlate = (carPlate: string): string => {
  // Only convert Latin characters to uppercase, leave Arabic as is
  const uppercased = carPlate.replace(/[a-z]+/g, (match) =>
    match.toUpperCase(),
  );
  return uppercased.trim().replace(/\s+/g, " ");
};

/**
 * Validates a Tunisian car plate
 * @param carPlate - The car plate to validate
 * @param options - Validation options
 * @returns boolean indicating if the car plate is valid
 */
export const validateCarPlate = (
  carPlate: string,
  options: CarPlateValidationOptions = {},
): boolean => {
  if (!carPlate) return false;

  // In strict mode, check for exact format without normalization
  if (options.strict) {
    // Check for extra spaces or incorrect format
    if (
      carPlate !== carPlate.trim() ||
      carPlate.includes("  ") ||
      carPlate.includes("tun") ||
      carPlate.includes("TUN")
    ) {
      return false;
    }

    // Make sure it contains the Arabic text "تونس"
    if (!carPlate.includes("تونس")) {
      return false;
    }
  }

  // Normalize the plate if not in strict mode
  const plateToCheck = options.strict ? carPlate : normalizePlate(carPlate);

  // Determine which regex to use based on the type option
  let regex: RegExp;
  switch (options.type) {
    case "standard":
      regex = REGEX.STANDARD;
      break;
    case "special":
      regex = REGEX.SPECIAL;
      break;
    case "any":
    default:
      regex = REGEX.ANY;
      break;
  }

  return regex.test(plateToCheck);
};

/**
 * Gets information about a car plate
 * @param carPlate - The car plate to check
 * @param options - Validation options
 * @returns object with plate type and components, or null if invalid
 */
export const getCarPlateInfo = (
  carPlate: string,
  options: CarPlateValidationOptions = {},
): {
  type: "standard" | "special";
  components: Record<string, string>;
} | null => {
  // First validate the car plate with the same options
  if (!validateCarPlate(carPlate, options)) return null;

  // In strict mode, check for exact format without normalization
  if (options.strict) {
    // Check for extra spaces or incorrect format
    if (
      carPlate !== carPlate.trim() ||
      carPlate.includes("  ") ||
      carPlate.includes("tun") ||
      carPlate.includes("TUN")
    ) {
      return null;
    }

    // Make sure it contains the Arabic text "تونس"
    if (!carPlate.includes("تونس")) {
      return null;
    }
  }

  const plateToCheck = options.strict ? carPlate : normalizePlate(carPlate);

  // Check if it's a standard plate
  const standardMatch = REGEX.STANDARD.exec(plateToCheck);
  if (standardMatch) {
    return {
      type: "standard",
      components: {
        prefix: standardMatch[1],
        region: standardMatch[2],
        suffix: standardMatch[3],
      },
    };
  }

  // Check if it's a special plate
  const specialMatch = REGEX.SPECIAL.exec(plateToCheck);
  if (specialMatch) {
    return {
      type: "special",
      components: {
        prefix: specialMatch[1],
        number: specialMatch[2],
        region: specialMatch[3],
      },
    };
  }

  return null;
};
