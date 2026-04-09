/**
 * Regular expression for CIN validation
 */
const CIN_REGEX = /^[01]\d{7}$/; // 0 or 1 followed by 7 digits to match the tunisian CIN format

/**
 * Validates a Tunisian CIN (Carte d'Identité Nationale)
 * @param cin - The CIN number to validate
 * @returns boolean indicating if the CIN is valid
 */
export const validateCIN = (cin: string): boolean => {
  return CIN_REGEX.test(cin);
};
