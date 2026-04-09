/**
 * Tunisian bank codes and information
 */
export const BANKS = {
  ATB: { code: "01", name: "Arab Tunisian Bank" },
  BIAT: { code: "08", name: "Banque Internationale Arabe de Tunisie" },
  BNA: { code: "03", name: "Banque Nationale Agricole" },
  BH: { code: "14", name: "Banque de l'Habitat" },
  STB: { code: "10", name: "Société Tunisienne de Banque" },
  UIB: { code: "12", name: "Union Internationale de Banques" },
  UBCI: { code: "04", name: "Union Bancaire pour le Commerce et l'Industrie" },
  BT: { code: "05", name: "Banque de Tunisie" },
  AMEN: { code: "07", name: "Amen Bank" },
  ATTIJARI: { code: "11", name: "Attijari Bank" },
  BTK: { code: "20", name: "Banque Tuniso-Koweïtienne" },
  BTL: { code: "25", name: "Banque Tuniso-Libyenne" },
  BTE: { code: "16", name: "Banque Tuniso-Emirats" },
  ZITOUNA: { code: "26", name: "Banque Zitouna" },
  POSTE: { code: "17", name: "La Poste Tunisienne" },
} as const;

/**
 * Currency code and symbol
 */
export const CURRENCY = {
  CODE: "TND",
  SYMBOL: "د.ت",
  NAME: "Tunisian Dinar",
} as const;
