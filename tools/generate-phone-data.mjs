// Regenerates wp-content/plugins/viralnest-core/data/phone-countries.json from libphonenumber-js metadata.
// Usage: node tools/generate-phone-data.mjs <path-to-libphonenumber-js/metadata.min.json>
// Then run: php tools/add-country-names.php  (adds English + Arabic country names via PHP intl)
import { readFileSync, writeFileSync } from 'node:fs';
const src = process.argv[2];
const meta = JSON.parse(readFileSync(src, 'utf8'));
const out = { source: 'libphonenumber-js metadata ' + meta.version, countries: {} };
for (const [iso, c] of Object.entries(meta.countries)) {
  if (iso === '001') continue;
  out.countries[iso] = { dial: c[0], pattern: c[2], lengths: c[3], trunk: c[5] || '' };
}
writeFileSync(new URL('../wp-content/plugins/viralnest-core/data/phone-countries.json', import.meta.url), JSON.stringify(out));
console.log(Object.keys(out.countries).length, 'countries');
