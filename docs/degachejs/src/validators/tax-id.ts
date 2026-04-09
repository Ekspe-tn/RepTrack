/**
 * Regular expression for tax ID validation
 */
const TAX_ID_REGEX = /^\d{7}[A-Z]\/[A-Z]\/[A-Z]\/\d{3}$/;

/**
 * Validates a Tunisian company tax ID (Matricule Fiscal)
 * @param taxId - The tax ID to validate
 * @returns boolean indicating if the tax ID is valid
 */
export const validateTaxID = (taxId: string): boolean => {
  return TAX_ID_REGEX.test(taxId);
};
