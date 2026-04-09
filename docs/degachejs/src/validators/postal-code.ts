import { POSTAL_CODES_MAP } from "../constants";

/**
 * Regular expression for postal code validation
 */
const POSTAL_CODE_REGEX = /^\d{4}$/;

/**
 * Validates a Tunisian postal code
 * @param postalCode - The postal code to validate
 * @returns boolean indicating if the postal code is valid
 */
export const validatePostalCode = (postalCode: string): boolean => {
  if (!postalCode) return false;
  if (!POSTAL_CODE_REGEX.test(postalCode)) return false;
  return postalCode in POSTAL_CODES_MAP;
};
