import { BANKS } from "../constants";
import { CARRIERS } from "../constants";
import { GOVERNORATES } from "../constants";

/**
 * Type for Tunisian CIN (Carte d'Identité Nationale)
 * An 8-digit number
 */
export type CIN = string;

/**
 * Type for Tunisian phone numbers
 * 8 digits starting with 2-9
 */
export type PhoneNumber = string;

/**
 * Type for Tunisian postal code
 * 4 digits
 */
export type PostalCode = string;

/**
 * Type for Tunisian company tax ID (Matricule Fiscal)
 * Format: 7 digits followed by letter/letter/letter/3 digits
 */
export type TaxID = string;

/**
 * Type for Tunisian RIB (Relevé d'Identité Bancaire)
 * 20 digits
 */
export type RIB = string;

/**
 * Type for currency formatting options
 */
export interface CurrencyFormatOptions {
  symbol?: boolean;
  code?: boolean;
}

/**
 * Type for bank information
 */
export type Bank = (typeof BANKS)[keyof typeof BANKS];

/**
 * Type for carrier information
 */
export type Carrier = (typeof CARRIERS)[keyof typeof CARRIERS];

/**
 * Type for governorate information
 */
export type Governorate = (typeof GOVERNORATES)[keyof typeof GOVERNORATES];

/**
 * Type for address information
 */
export interface Address {
  street: string;
  city: string;
  postalCode: PostalCode;
  governorate: keyof typeof GOVERNORATES;
}
