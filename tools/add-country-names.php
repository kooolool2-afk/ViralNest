<?php
// Adds en/ar display names to the phone country data using PHP intl (ICU).
$file = __DIR__ . '/../wp-content/plugins/viralnest-core/data/phone-countries.json';
$data = json_decode(file_get_contents($file), true);
foreach ($data['countries'] as $iso => &$c) {
    $c['en'] = Locale::getDisplayRegion('-' . $iso, 'en');
    $c['ar'] = Locale::getDisplayRegion('-' . $iso, 'ar');
}
file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo count($data['countries']), " named\n";
