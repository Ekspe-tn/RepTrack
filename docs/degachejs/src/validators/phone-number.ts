import { CARRIERS, VALID_PREFIXES } from "../constants";
import { Carrier } from "../types";

/**
 * Options for phone number validation
 */
export interface PhoneNumberValidationOptions {
  /**
   * When true, enforces strict format validation:
   * - No spaces or special characters allowed
   * - Must be exactly 8 digits or with +216 prefix
   * - Must start with a valid carrier prefix
   */
  strict?: boolean;
}

/**
 * Regular expressions for phone number validation
 */
const REGEX = {
  PHONE: /^[2-9]\d{7}$/,
  INTERNATIONAL: /^\+216[2-9]\d{7}$/,
  // Strict mode allows only digits and optional +216 prefix
  STRICT_PHONE: /^(?:\+216)?[2-9]\d{7}$/,
} as const;

/**
 * Validates a Tunisian phone number
 * @param phoneNumber - The phone number to validate
 * @param options - Validation options
 * @returns boolean indicating if the phone number is valid
 */
export const validatePhoneNumber = (
  phoneNumber: string,
  options: PhoneNumberValidationOptions = {},
): boolean => {
  if (!phoneNumber) return false;

  // In strict mode, validate against the strict regex first
  if (options.strict && !REGEX.STRICT_PHONE.test(phoneNumber)) {
    return false;
  }

  // Remove international prefix if present
  const normalizedNumber = phoneNumber.replace(/^\+216/, "");

  if (!REGEX.PHONE.test(normalizedNumber)) return false;
  return VALID_PREFIXES.includes(normalizedNumber[0]);
};

/**
 * Gets carrier information from a phone number
 * @param phoneNumber - The phone number to check
 * @param options - Validation options
 * @returns carrier information or null if invalid
 */
export const getCarrierInfo = (
  phoneNumber: string,
  options: PhoneNumberValidationOptions = {},
): Carrier | null => {
  if (!validatePhoneNumber(phoneNumber, options)) return null;

  const normalizedNumber = phoneNumber.replace(/^\+216/, "");
  const prefix = normalizedNumber[0];

  return (
    Object.entries(CARRIERS).find(([_, carrier]) =>
      carrier.prefixes.includes(prefix as never),
    )?.[1] || null
  );
};
