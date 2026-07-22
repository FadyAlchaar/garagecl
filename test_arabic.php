<?php

require_once __DIR__.'/vendor/autoload.php';

$fontsDir = __DIR__.'/assets/fonts/';

$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',

    'fontDir' => array_merge(
        $defaultConfig['fontDir'],
        [$fontsDir]
    ),

    'fontdata' => $defaultFontConfig['fontdata'] + [
        'tajawal' => [
            'R' => 'Tajawal-Regular.ttf',
            'B' => 'Tajawal-Bold.ttf',
        ],
    ],

    'default_font' => 'tajawal'
]);

$mpdf->useOTL = 0xFF;

$mpdf->WriteHTML('
<div style="font-size:24px">
مرحبا بالعالم<br>
معلومات السيارة<br>
خالد الحمدان
</div>
');

$mpdf->Output();