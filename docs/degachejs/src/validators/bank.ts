import { BANKS } from "../constants";
import { Bank } from "../types";

/**
 * Regular expressions for bank validation
 */
const REGEX = {
  RIB: /^\d{20}$/,
  BANK_CODE: /^\d{2}$/,
  BRANCH_CODE: /^\d{3}$/,
  ACCOUNT_NUMBER: /^\d{13}$/,
  KEY: /^\d{2}$/,
} as const;

/**
 * Validates a Tunisian RIB (Relevé d'Identité Bancaire)
 * @param rib - The 20-digit RIB number
 * @returns boolean indicating if the RIB is valid
 */
export const validateRIB = (rib: string): boolean => {
  if (!REGEX.RIB.test(rib)) return false;

  const bankCode = rib.slice(0, 2);
  const branchCode = rib.slice(2, 5);
  const accountNumber = rib.slice(5, 18);
  const key = rib.slice(18, 20);

  // Validate bank code exists
  const bankExists = Object.values(BANKS).some(
    (bank) => bank.code === bankCode,
  );
  if (!bankExists) return false;

  // Validate branch code format
  if (!REGEX.BRANCH_CODE.test(branchCode)) return false;

  // Validate account number format
  if (!REGEX.ACCOUNT_NUMBER.test(accountNumber)) return false;

  // Validate key format
  if (!REGEX.KEY.test(key)) return false;

  // TODO: Implement RIB key validation algorithm
  return true;
};

/**
 * Gets bank information from a RIB number
 * @param rib - The 20-digit RIB number
 * @returns bank information or null if invalid
 */
export const getBankFromRIB = (rib: string): Bank | null => {
  if (!REGEX.RIB.test(rib)) return null;

  const bankCode = rib.slice(0, 2);
  const bank = Object.values(BANKS).find((b) => b.code === bankCode);

  return bank || null;
};
