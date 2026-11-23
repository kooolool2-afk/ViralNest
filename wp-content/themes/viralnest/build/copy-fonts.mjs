// Copies the self-hosted variable fonts (SIL Open Font License) into assets/fonts.
import { copyFileSync, mkdirSync } from 'node:fs';
const out = new URL('../assets/fonts/', import.meta.url);
mkdirSync(out, { recursive: true });
const files = [
  ['@fontsource-variable/sora/files/sora-latin-wght-normal.woff2', 'sora-latin-wght.woff2'],
  ['@fontsource-variable/sora/files/sora-latin-ext-wght-normal.woff2', 'sora-latin-ext-wght.woff2'],
  ['@fontsource-variable/readex-pro/files/readex-pro-arabic-wght-normal.woff2', 'readex-pro-arabic-wght.woff2'],
];
for (const [from, to] of files) {
  copyFileSync(new URL('../node_modules/' + from, import.meta.url), new URL(to, out));
  console.log('font', to);
}
