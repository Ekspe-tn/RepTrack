/**
 * Tunisian mobile carriers and their prefixes
 */
export const CARRIERS = {
  OOREDOO: {
    name: "Ooredoo Tunisia",
    prefixes: ["2", "5"] as const,
  },
  ORANGE: {
    name: "Orange Tunisia",
    prefixes: ["4", "5"] as const,
  },
  TELECOM: {
    name: "Tunisie Telecom",
    prefixes: ["2", "9"] as const,
  },
} as const;

/**
 * Country calling code for Tunisia
 */
export const COUNTRY_CODE = "+216";

/**
 * All valid mobile prefixes
 */
export const VALID_PREFIXES = Array.from(
  new Set(
    Object.values(CARRIERS)
      .map((carrier) => carrier.prefixes)
      .flat(),
  ),
) as readonly string[];

/**
 * Mobile carrier prefixes
 */
export const MOBILE_PREFIXES = {
  OOREDOO: ["2", "5"],
  ORANGE: ["4", "5"],
  TELECOM: ["2", "9"],
} as const;
