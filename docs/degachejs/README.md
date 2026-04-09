# 🌴 degache.js

Tunisian Developer's Essential Utility Library

## Why degache.js? 🤔

Tired of rewriting validation code for Tunisian CIN, phone numbers, and tax IDs? `degache.js` is your go-to utility library for all things Tunisian! Named after the beautiful oasis city of Degache, this library brings the same refreshing relief to your development workflow.

## 🚀 Features

### CIN (Carte d'Identité Nationale) 🆔
```typescript
import { validateCIN } from 'degachejs';

// Simple validation
const isValid = validateCIN('12345678'); // true
```

### Phone Numbers 📱
- ✅ Support for all Tunisian carriers (Ooredoo, Orange, Tunisie Telecom)
- 🔄 International format conversion
- 📞 Smart formatting with country code
- 🏢 Carrier detection
- 🔒 Strict mode validation

```typescript
import { validatePhoneNumber, formatPhoneNumber, getCarrierInfo } from 'degachejs';

// Validate phone number
const isValid = validatePhoneNumber('20123456'); // true

// Validate with strict mode (no spaces or special characters allowed)
const isStrictValid = validatePhoneNumber('20 123 456', { strict: true }); // false

// Format phone number
const formatted = formatPhoneNumber('20123456');
console.log(formatted); // +216 20 123 456

// Get carrier information
const carrier = getCarrierInfo('20123456');
console.log(carrier); // { name: 'Ooredoo', prefixes: ['2'] }
```

### Tax ID (Matricule Fiscal) 💼
```typescript
import { validateTaxID } from 'degachejs';

const isValid = validateTaxID('1234567A/P/M/000');
```

### Currency Formatting 💰
```typescript
import { formatCurrency } from 'degachejs';

const amount = formatCurrency(1234.56);
console.log(amount); // 1.234,560 دينار تونسي
```

### Postal Codes 📮
```typescript
import { validatePostalCode } from 'degachejs';

const isValid = validatePostalCode('1000'); // true for Tunis
```

### Car Plates 🚗
```typescript
import { validateCarPlate, getCarPlateInfo } from 'degachejs';

// Validate car plate (Arabic format: XXX تونس XXXX)
const isValid = validateCarPlate('123 تونس 4567'); // true

// Validate special car plate (RS format)
const isSpecialValid = validateCarPlate('RS 123 تونس', { type: 'special' }); // true

// Validate with strict mode
const isStrictValid = validateCarPlate('123  تونس  4567', { strict: true }); // false

// Get car plate information
const plateInfo = getCarPlateInfo('123 تونس 4567');
console.log(plateInfo);
// {
//   type: 'standard',
//   components: {
//     prefix: '123',
//     region: 'تونس',
//     suffix: '4567'
//   }
// }
```

### Bank Account (RIB) Validation 🏦
```typescript
import { validateRIB, getBankFromRIB } from 'degachejs';

// Validate RIB
const isValid = validateRIB('12345678901234567890');

// Get bank information
const bank = getBankFromRIB('12345678901234567890');
console.log(bank); // { name: 'Bank Name', code: '12' }
```

### Date Formatting 📅
```typescript
import { formatDate } from 'degachejs';

const formatted = formatDate(new Date());
console.log(formatted); // Formatted date in Tunisian style
```

### Constants 📋
```typescript
import { BANKS, CARRIERS, GOVERNORATES } from 'degachejs';

// Access list of Tunisian banks
console.log(BANKS);

// Access list of mobile carriers
console.log(CARRIERS);

// Access list of governorates
console.log(GOVERNORATES);
```

## 📦 Installation

```bash
npm install degachejs
# or
yarn add degachejs
# or
pnpm add degachejs
```

## 🛠️ Usage

```typescript
import {
  validateCIN,
  formatPhoneNumber,
  validateTaxID,
  formatCurrency,
  validateRIB,
  getBankFromRIB,
  formatDate,
  validateCarPlate,
  getCarPlateInfo
} from 'degachejs';

// Validate CIN
const isCINValid = validateCIN('12345678');

// Format phone number
const phoneNumber = formatPhoneNumber('20123456');

// Validate Tax ID
const isTaxIDValid = validateTaxID('1234567A/P/M/000');

// Format currency
const price = formatCurrency(1234.56, { symbol: true });

// Validate RIB
const isRIBValid = validateRIB('12345678901234567890');

// Get bank information
const bankInfo = getBankFromRIB('12345678901234567890');

// Format date
const formattedDate = formatDate(new Date());

// Validate car plate
const isCarPlateValid = validateCarPlate('123 تونس 4567');

// Get car plate information
const carPlateInfo = getCarPlateInfo('123 تونس 4567');
```

## 🤝 Contributing

We welcome contributions from the Tunisian developer community! Whether it's:

- 🐛 Bug fixes
- ✨ New features
- 📚 Documentation improvements
- 🧪 Test cases
- 💡 Feature suggestions

Check out our [Contributing Guide](CONTRIBUTING.md) to get started.

## 🔒 Security

All validation and formatting functions are designed with security in mind, following best practices for handling sensitive data.

## 📄 License

degache.js is MIT licensed. See the [LICENSE](LICENSE) file for details.

## 🏆 Production Ready

- ✅ Comprehensive input validation
- ⚡ Optimized performance
- 🧪 High test coverage
- 📚 Detailed documentation
- 🔒 Type-safe APIs

---

Built with ❤️ for the Tunisian developer community 🇹🇳
