<?php
/**
 * ============================================================
 * CENTRAL DICTIONARY FILE
 * All terminologies used in the Garage Inspection System
 * Keys match EXACTLY with database columns (item_key, panel_key)
 * ============================================================
 */

// ============================================================
// 1. FUEL TYPES (matches database `fuel_type` enum)
// ============================================================
if (!defined('FUEL_TYPES')) {
    define('FUEL_TYPES', [
        'Petrol'   => ['ar' => 'بنزين',      'en' => 'Petrol'],
        'Diesel'   => ['ar' => 'ديزل',       'en' => 'Diesel'],
        'Electric' => ['ar' => 'كهرباء',     'en' => 'Electric'],
        'Hybrid'   => ['ar' => 'هجين',       'en' => 'Hybrid'],
        'Gas'      => ['ar' => 'غاز',        'en' => 'Gas'],
    ]);
}

// ============================================================
// 1b. BODY STYLES (matches database `reports.body_style` enum)
// Determines which set of 5 traced SVG diagrams is shown in the
// body panel map step. Add a new key here + trace 5 matching SVGs
// in assets/img/cars-views/ to support another silhouette.
// ============================================================
if (!defined('BODY_STYLES')) {
    define('BODY_STYLES', [
        'sedan' => ['ar' => 'سيدان', 'en' => 'Sedan'],
        'suv'   => ['ar' => 'دفع رباعي', 'en' => 'SUV'],
    ]);
}

// ============================================================
// 2. CHECKLIST ITEMS (matches `checklist_items.item_key`)
// ============================================================
if (!defined('CHECKLIST_ITEMS')) {
    define('CHECKLIST_ITEMS', [
        ['key' => 'engine',        'ar' => 'المحرك',                    'en' => 'Engine'],
        ['key' => 'transmission',  'ar' => 'ناقل الحركة',               'en' => 'Transmission'],
        ['key' => 'brakes',        'ar' => 'الفرامل',                   'en' => 'Brakes'],
        ['key' => 'suspension',    'ar' => 'التعليق',                   'en' => 'Suspension'],
        ['key' => 'steering',      'ar' => 'التوجيه',                   'en' => 'Steering'],
        ['key' => 'tires',         'ar' => 'الإطارات',                  'en' => 'Tires'],
        ['key' => 'battery',       'ar' => 'البطارية',                  'en' => 'Battery'],
        ['key' => 'lights',        'ar' => 'الإضاءة',                   'en' => 'Lights'],
        ['key' => 'wipers',        'ar' => 'الماسحات',                  'en' => 'Wipers'],
        ['key' => 'ac',            'ar' => 'المكيف',                    'en' => 'A/C'],
        ['key' => 'heater',        'ar' => 'المدفأة',                   'en' => 'Heater'],
        ['key' => 'exhaust',       'ar' => 'العادم',                    'en' => 'Exhaust'],
        ['key' => 'cooling',       'ar' => 'نظام التبريد',              'en' => 'Cooling System'],
        ['key' => 'electrical',    'ar' => 'الكهرباء',                  'en' => 'Electrical'],
        ['key' => 'interior',      'ar' => 'المقصورة الداخلية',        'en' => 'Interior'],
        ['key' => 'exterior',      'ar' => 'الهيكل الخارجي',            'en' => 'Exterior'],
    ]);
}

// ============================================================
// 3. INSPECTION CHECKS (matches `inspection_checks.item_key`)
// ============================================================
if (!defined('INSPECTION_CHECKS_DICT')) {
    define('INSPECTION_CHECKS_DICT', [
        // ----- Engine Checks (section: 'engine') -----
        'irregular_work'    => ['ar' => 'عمل غير منتظم في المحرك',               'en' => 'Irregular engine operation'],
        'engine_noise'      => ['ar' => 'صوت سلبي من المحرك',                   'en' => 'Abnormal engine noise'],
        'gearbox_noise'     => ['ar' => 'صوت سلبي من علبة السرعة',                 'en' => 'Gearbox noise'],
        'gear_knock'        => ['ar' => 'ضربات أثناء تبديل الغيار',             'en' => 'Knocking during gear change'],
        'water_oil_mix'     => ['ar' => 'خليط ماء ـ زيت في المحرك',             'en' => 'Water-oil mix in engine'],
        'compression_needed'=> ['ar' => 'هل تحتاج السيارة قياس ضغط المحرك',    'en' => 'Engine compression test needed'],
        'urgent_service'    => ['ar' => 'هل تحتاج السيارة خدمة صيانة طارئة',   'en' => 'Urgent maintenance needed'],
        'steam_blow'        => ['ar' => 'نفخ بخار من المحرك',                   'en' => 'Steam blowing from engine'],
        'injector_noise'    => ['ar' => 'صوت سلبي من الحاقن',                   'en' => 'Injector noise'],
        'sprayer_noise'     => ['ar' => 'صوت سلبي عند تشغيل المرش',             'en' => 'Sprayer noise on startup'],
        'exhaust_manifold'  => ['ar' => 'صوت او تسريب من مشعب العادم',          'en' => 'Exhaust manifold leak/noise'],
        'diff_noise'        => ['ar' => 'صوت سلبي من الدفرنسيه',                'en' => 'Differential noise'],
        'black_smoke'       => ['ar' => 'دخان أسود من عادم السيارة',            'en' => 'Black smoke from exhaust'],
        'white_smoke'       => ['ar' => 'دخان أبيض من عادم السيارة',            'en' => 'White smoke from exhaust'],
        'engine_oil_leak'   => ['ar' => 'تسريب زيت من المحرك',                  'en' => 'Engine oil leak'],
        'gearbox_oil_leak'  => ['ar' => 'تسريب زيت من منطقة علبة السرعة',          'en' => 'Gearbox oil leak'],
        'diff_oil_leak'     => ['ar' => 'تسريب زيت من الدفرنسيه',               'en' => 'Differential oil leak'],
        'turbo_oil_leak'    => ['ar' => 'تسريب زيت من منطقة التوربو',           'en' => 'Turbo oil leak'],
        'intercooler_leak'  => ['ar' => 'تسريب زيت من المبرد الداخلي',          'en' => 'Intercooler oil leak'],
        'coolant_leak'      => ['ar' => 'تسريب ماء من المحرك',                  'en' => 'Coolant leak from engine'],
        'fuel_leak'         => ['ar' => 'تسريب من نظام الوقود',                 'en' => 'Fuel system leak'],
        'belts_condition'   => ['ar' => 'الأحزمة المرئية بالعين',               'en' => 'Visible belts condition'],
        'gearbox_condition' => ['ar' => 'نظام الغيار',                          'en' => 'Gearbox system condition'],
        'turbo_condition'   => ['ar' => 'نظام التوربو',                         'en' => 'Turbo system condition'],
        'oil_level'         => ['ar' => 'مستوى زيت المحرك',                    'en' => 'Engine oil level'],
        'coolant_ratio'     => ['ar' => 'نسبة ماء تبريد المحرك',               'en' => 'Coolant ratio'],
        'coolant_condition' => ['ar' => 'حالة ماء تبريد المحرك',               'en' => 'Coolant condition'],
        'antifreeze'        => ['ar' => 'حالة مانع التجمد',                     'en' => 'Antifreeze condition'],
        'brake_fluid'       => ['ar' => 'نسبة هيدروليك الفرامل',                'en' => 'Brake fluid level'],
        'plastic_parts'     => ['ar' => 'الأقسام البلاستيكية',                  'en' => 'Plastic parts condition'],

        // ----- Underbody Checks (section: 'underbody') -----
        'combustion_leak'    => ['ar' => 'تسريب في غرفة اشتعال المحرك',         'en' => 'Combustion chamber leak'],
        'front_play'         => ['ar' => 'فراغ في التنظيم الأمامي',              'en' => 'Front alignment play'],
        'rear_play'          => ['ar' => 'فراغ في التنظيم الخلفي',               'en' => 'Rear alignment play'],
        'steering_play'      => ['ar' => 'فراغ في نظام عجلة القيادة',            'en' => 'Steering wheel play'],
        'power_steering_leak'=> ['ar' => 'تسريب زيت من مضخة عجلة القيادة',      'en' => 'Power steering pump leak'],
        'steering_box_leak'  => ['ar' => 'تسريب زيت من علبة عجلة القيادة',      'en' => 'Steering box leak'],
        'steering_shaft_leak'=> ['ar' => 'تسريب زيت من محور منفاخ عجلة القيادة','en' => 'Steering shaft leak'],
        'brake_center_leak'  => ['ar' => 'تسريب زيت من مراكز الفرامل',          'en' => 'Brake center leak'],
        'brake_hose_leak'    => ['ar' => 'تسريب زيت أو تفسخ في خراطيم الفرامل','en' => 'Brake hose leak/deterioration'],
        'front_brake_disc'   => ['ar' => 'حالة القرص الأمامي لنظام الفرامل',    'en' => 'Front brake disc condition'],
        'front_brake_pad'    => ['ar' => 'حالة البلطة الأمامية لنظام الفرامل',  'en' => 'Front brake pad condition'],
        'rear_brake_disc'    => ['ar' => 'حالة القرص الخلفية لنظام الفرامل',    'en' => 'Rear brake disc condition'],
        'rear_brake_pad'     => ['ar' => 'حالة البلطة الخلفية لنظام الفرامل',   'en' => 'Rear brake pad condition'],
        'exhaust_system'     => ['ar' => 'فحص نظام عادم السيارة',                'en' => 'Exhaust system check'],
        'particle_filter'    => ['ar' => 'فحص فلتر الجسيمات',                   'en' => 'Particle filter check'],
        'tires_condition'    => ['ar' => 'فحص حالة العجالات',                    'en' => 'Tires condition'],
        'rims_condition'     => ['ar' => 'فحص حالة الجانط',                      'en' => 'Rims condition'],
        'underbody_frame'    => ['ar' => 'فحص هيكل التثبيت السفلي الحامي للمحرك','en' => 'Underbody engine protection frame'],

        // ----- Electrical Checks (section: 'electrical') -----
        'engine_ecu'    => ['ar' => 'قيد أضرار اللوحة المركزية للمحرك',          'en' => 'Engine ECU fault codes'],
        'airbag_ecu'    => ['ar' => 'قيد أضرار اللوحة المركزية للوسادة الهوائية','en' => 'Airbag ECU fault codes'],
        'ac_ecu'        => ['ar' => 'قيد أضرار اللوحة المركزية للتكيف',          'en' => 'AC ECU fault codes'],
        'battery'       => ['ar' => 'حالة البطارية',                              'en' => 'Battery condition'],
        'brake_ecu'     => ['ar' => 'قيد أضرار اللوحة المركزية للفرامل',         'en' => 'Brake ECU fault codes'],
        'gearbox_ecu'   => ['ar' => 'قيد أضرار اللوحة المركزية للشونجمان',       'en' => 'Gearbox ECU fault codes'],
        'ac_pressure'   => ['ar' => 'حالة ضغط التكييف',                           'en' => 'AC pressure condition'],
        'wiring'        => ['ar' => 'تأسيسات الكهرباء',                           'en' => 'Electrical wiring'],

        // ----- Airbag Checks (section: 'airbag') -----
        'driver_airbag'   => ['ar' => 'فحص الوسادة الهوائية الخاصة بالسائق',    'en' => "Driver's airbag check"],
        'passenger_airbag'=> ['ar' => 'فحص الوسادة الهوائية الخاصة بالركاب',    'en' => "Passenger's airbag check"],
        'side_curtain'    => ['ar' => 'فحص الستارة الهوائية الجانبية',           'en' => 'Side curtain airbag check'],
        'airbag_ecu_main' => ['ar' => 'قيد أضرار اللوحة الرئيسية للوسادة الهوائية','en' => 'Main airbag ECU fault codes'],

        // ----- Interior Checks (section: 'interior') -----
        'ac_heating'      => ['ar' => 'نظام التكييف والتدفئة',        'en' => 'AC & heating system'],
        'interior_lights' => ['ar' => 'الإضاءة الداخلية',             'en' => 'Interior lighting'],
        'dashboard'       => ['ar' => 'لوحة القيادة',                  'en' => 'Dashboard'],
        'horn'            => ['ar' => 'فحص الزمور',                    'en' => 'Horn check'],
        'remote_controls' => ['ar' => 'أزرار التحكم عن بعد',          'en' => 'Remote control buttons'],
        'exterior_lights' => ['ar' => 'الإضاءة الخارجية',             'en' => 'Exterior lighting'],
        'sunroof'         => ['ar' => 'فتحة سقف',                     'en' => 'Sunroof'],
        'headlight_wash'  => ['ar' => 'غسل المصباح',                   'en' => 'Headlight washer'],

        // ----- Accessories Checks (section: 'accessories') -----
        'windows'         => ['ar' => 'فحوصات النوافذ',               'en' => 'Windows check'],
        'wipers'          => ['ar' => 'نظام المساحات',                 'en' => 'Wiper system'],
        'interior_trim'   => ['ar' => 'التنجيد الداخلي',              'en' => 'Interior trim'],
        'seatbelts'       => ['ar' => 'أحزمة الأمان',                 'en' => 'Seatbelts'],
        'radio'           => ['ar' => 'فحص نظام الراديو',             'en' => 'Radio system'],
        'interior_issues' => ['ar' => 'تشوهات / مشاكل داخل السيارة', 'en' => 'Interior deformations / issues'],
        'steering_wheel'  => ['ar' => 'عجلة القيادة',                 'en' => 'Steering wheel'],
        'mirrors'         => ['ar' => 'فحوصات المرايا',               'en' => 'Mirrors check'],
        'door_trim'       => ['ar' => 'تنجيد الأبواب',                'en' => 'Door trim'],
        'seats'           => ['ar' => 'المقاعد',                      'en' => 'Seats'],
        'spare_wheel'     => ['ar' => 'حالة العجلة الاحتياط',         'en' => 'Spare wheel condition'],
        'parking_sensors' => ['ar' => 'فحص حساسات الباركينغ',         'en' => 'Parking sensors'],
        'gear_lever'      => ['ar' => 'ذراع الغيار',                  'en' => 'Gear lever'],
    ]);
}

// ============================================================
// 4. BODY PANELS (matches `body_panels.panel_key`)
// ============================================================
if (!defined('BODY_PANELS_DICT')) {
    define('BODY_PANELS_DICT', [
        // Right Side
        'right_front_fender' => ['ar' => 'الرفراف الأيمن الأمامي',   'en' => 'Right Front Fender'],
        'right_front_door'   => ['ar' => 'الباب الأيمن الأمامي',     'en' => 'Right Front Door'],
        'right_rear_door'    => ['ar' => 'الباب الأيمن الخلفي',      'en' => 'Right Rear Door'],
        'right_rear_fender'  => ['ar' => 'الرفراف الأيمن الخلفي',    'en' => 'Right Rear Fender'],
        'right_sill'         => ['ar' => 'مارشبيل اليمين',           'en' => 'Right Sill'],
        'right_a_pillar'     => ['ar' => 'العمود الأيمن الأمامي',    'en' => 'Right A-Pillar'],
        'right_b_pillar'     => ['ar' => 'عمود الباب الأيمن الأمامي','en' => 'Right B-Pillar'],
        // 'right_roof_rail'    => ['ar' => 'عمود السقف الأيمن',        'en' => 'Right Roof Rail'],
        'right_c_pillar'     => ['ar' => 'العمود الأيمن الخلفي',     'en' => 'Right C-Pillar'],
        'right_d_pillar'     => ['ar' => 'العمود الأيمن المتوسط',    'en' => 'Right D-Pillar'],
        'right_platform'     => ['ar' => 'المنصة الحامية اليمين',    'en' => 'Right Platform'],
        'right_chassis'      => ['ar' => 'الشاسيه الأيمن',           'en' => 'Right Chassis'],

        // Left Side
        'left_front_fender'  => ['ar' => 'الرفراف الأيسر الأمامي',   'en' => 'Left Front Fender'],
        'left_front_door'    => ['ar' => 'الباب الأيسر الأمامي',     'en' => 'Left Front Door'],
        'left_rear_door'     => ['ar' => 'الباب الأيسر الخلفي',      'en' => 'Left Rear Door'],
        'left_rear_fender'   => ['ar' => 'الرفراف الأيسر الخلفي',    'en' => 'Left Rear Fender'],
        'left_sill'          => ['ar' => 'مارشبيل اليسار',           'en' => 'Left Sill'],
        'left_a_pillar'      => ['ar' => 'العمود الأيسر الأمامي',    'en' => 'Left A-Pillar'],
        'left_b_pillar'      => ['ar' => 'عمود الباب الأيسر الأمامي','en' => 'Left B-Pillar'],
        // 'left_roof_rail'     => ['ar' => 'عمود السقف الأيسري',       'en' => 'Left Roof Rail'],
        'left_c_pillar'      => ['ar' => 'العمود الأيسر الخلفي',     'en' => 'Left C-Pillar'],
        'left_d_pillar'      => ['ar' => 'العمود الأيسر المتوسط',    'en' => 'Left D-Pillar'],
        'left_platform'      => ['ar' => 'المنصة الحامية اليسار',    'en' => 'Left Platform'],
        'left_chassis'       => ['ar' => 'الشاسيه الأيسر',           'en' => 'Left Chassis'],

        // Top
        'hood'               => ['ar' => 'غطاء المحرك',               'en' => 'Hood'],
        'roof'               => ['ar' => 'السقف',                     'en' => 'Roof'],
        // 'trunk_top'          => ['ar' => 'أعلى غطاء الصندوق',         'en' => 'Trunk Lid Top'],
        'trunk'              => ['ar' => 'غطاء الصندوق',              'en' => 'Trunk Lid'],

        // Front / Rear
        // 'trunk_door'         => ['ar' => 'غطاء الصندوق',              'en' => 'Trunk Door'],
        'rear_panel'         => ['ar' => 'البانيل الخلفي',            'en' => 'Rear Panel'],
        'trunk_floor'        => ['ar' => 'صاج الصندوق',               'en' => 'Trunk Floor'],
        'rear_bumper'        => ['ar' => 'الدعامية الخلفية',          'en' => 'Rear Bumper'],
        'front_bumper'       => ['ar' => 'الدعامية الأمامية',         'en' => 'Front Bumper'],
        'front_panel'        => ['ar' => 'البانيل الأمامي',           'en' => 'Front Panel'],
    ]);
}

// ============================================================
// 5. PANEL STATUS (matches `body_panels.status`)
// ============================================================
if (!defined('PANEL_STATUS')) {
    define('PANEL_STATUS', [
        'original'   => ['ar' => 'أصلي',        'en' => 'Original',    'color' => '#ffffff'],
        'painted'    => ['ar' => 'تم طلاؤه',     'en' => 'Painted',     'color' => '#3b82f6'],
        'replaced'   => ['ar' => 'تم تبديله',    'en' => 'Replaced',    'color' => '#ef4444'],
        'repaired'   => ['ar' => 'تم تعديله',    'en' => 'Repaired',    'color' => '#a855f7'],
        'spot_paint' => ['ar' => 'طلاء موضعي',   'en' => 'Spot Paint',  'color' => '#eab308'],
        'plastic'    => ['ar' => 'بلاستك',       'en' => 'Plastic',     'color' => '#9ca3af'],
    ]);
}

// ============================================================
// 6. RESULT OPTIONS (matches `inspection_checks.result`)
// ============================================================
if (!defined('RESULT_OPTIONS')) {
    define('RESULT_OPTIONS', [
        'good'        => ['ar' => 'جيد',        'en' => 'Good'],
        'none'        => ['ar' => 'لا يوجد',    'en' => 'None'],
        'light'       => ['ar' => 'خفيف',       'en' => 'Light'],
        'medium'      => ['ar' => 'متوسط',      'en' => 'Medium'],
        'bad'         => ['ar' => 'سيئ',        'en' => 'Bad'],
        'not_checked' => ['ar' => 'لم يفحص',    'en' => 'Not Checked'],
    ]);
}

// ============================================================
// 7. CHECKLIST STATUS (matches `checklist_items.result`)
// ============================================================
if (!defined('CHECKLIST_STATUS')) {
    define('CHECKLIST_STATUS', [
        'pass'        => ['ar' => 'ناجح',       'en' => 'Pass',         'color' => '#16a34a'],
        'fail'        => ['ar' => 'راسب',       'en' => 'Fail',         'color' => '#dc2626'],
        'not_checked' => ['ar' => 'لم يفحص',    'en' => 'Not Checked',  'color' => '#6b7280'],
    ]);
}

// ============================================================
// 8. BRAKE & SUSPENSION STATUS (matches `brake_results.*_status`, `suspension_results.*_status`)
// ============================================================
if (!defined('STATUS_GOOD_WARNING_FAIL')) {
    define('STATUS_GOOD_WARNING_FAIL', [
        'good'    => ['ar' => 'جيد',      'en' => 'Good',    'color' => '#16a34a'],
        'warning' => ['ar' => 'تحذير',    'en' => 'Warning', 'color' => '#ca8a04'],
        'fail'    => ['ar' => 'فشل',      'en' => 'Fail',    'color' => '#dc2626'],
    ]);
}

// ============================================================
// 9. DEVIATION STATUS (matches `brake_results.*_deviation_status`)
// ============================================================
if (!defined('DEVIATION_STATUS')) {
    define('DEVIATION_STATUS', [
        'pass' => ['ar' => 'نجح', 'en' => 'Pass', 'color' => '#16a34a'],
        'fail' => ['ar' => 'فشل', 'en' => 'Fail', 'color' => '#dc2626'],
    ]);
}

// ============================================================
// 10. REPORT STATUS (matches `reports.status`)
// ============================================================
if (!defined('REPORT_STATUS')) {
    define('REPORT_STATUS', [
        'draft'    => ['ar' => 'مسودة',    'en' => 'Draft'],
        'complete' => ['ar' => 'مكتمل',    'en' => 'Complete'],
    ]);
}

// ============================================================
// 11. PACKAGE TYPES
// ============================================================
if (!defined('PACKAGE_TYPES')) {
    define('PACKAGE_TYPES', [
        'Basic'          => ['ar' => 'أساسي',          'en' => 'Basic'],
        'Standard'       => ['ar' => 'قياسي',          'en' => 'Standard'],
        'Premium'        => ['ar' => 'ممتاز',          'en' => 'Premium'],
        'Comprehensive'  => ['ar' => 'شامل',           'en' => 'Comprehensive'],
        'GOLD PLUS PAKET'=> ['ar' => 'حزمة الذهب بلس', 'en' => 'Gold Plus Package'],
    ]);
}

// ============================================================
// 12. SECTION NOTES (matches `section_notes.section`)
// ============================================================
if (!defined('SECTION_NAMES')) {
    define('SECTION_NAMES', [
        'engine'      => ['ar' => 'المحرك',                         'en' => 'Engine'],
        'transmission'=> ['ar' => 'ناقل الحركة',                    'en' => 'Transmission'],
        'brakes'      => ['ar' => 'الفرامل',                        'en' => 'Brakes'],
        'suspension'  => ['ar' => 'التعليق',                        'en' => 'Suspension'],
        'exterior'    => ['ar' => 'الهيكل الخارجي',                 'en' => 'Exterior'],
        'interior'    => ['ar' => 'المقصورة الداخلية',             'en' => 'Interior'],
        'dyno'        => ['ar' => 'أداء المحرك',                   'en' => 'Engine Performance'],
        'general'     => ['ar' => 'عام',                            'en' => 'General'],
    ]);
}

// ============================================================
// 13. SECTION HEADERS (for PDF and view)
// ============================================================
if (!defined('SECTION_HEADERS')) {
    define('SECTION_HEADERS', [
        'engine'      => ['ar' => 'فحوصات المحرك',                         'en' => 'Engine Checks'],
        'underbody'   => ['ar' => 'التنظيم الأمامي / الطقم السفلي',        'en' => 'Underbody / Alignment'],
        'electrical'  => ['ar' => 'النظام الكهربائي والإلكتروني',          'en' => 'Electrical & Electronic'],
        'airbag'      => ['ar' => 'نظام الوسادة الهوائية',                 'en' => 'Airbag System'],
        'interior'    => ['ar' => 'الأقسام الداخلية والخارجية',            'en' => 'Interior & Exterior'],
        'accessories' => ['ar' => 'حزمة الإكسسوارات والراحة',              'en' => 'Accessories & Comfort'],
    ]);
}

// ============================================================
// 14. UI TRANSLATIONS (for buttons, labels, headers)
// ============================================================
if (!defined('UI_TRANSLATIONS')) {
    define('UI_TRANSLATIONS', [
        // Navigation
        'dashboard'        => ['ar' => 'لوحة التحكم',     'en' => 'Dashboard'],
        'new_report'       => ['ar' => 'تقرير جديد',      'en' => 'New Report'],
        'settings'         => ['ar' => 'الإعدادات',       'en' => 'Settings'],
        'users'            => ['ar' => 'المستخدمين',      'en' => 'Users'],
        'logout'           => ['ar' => 'تسجيل الخروج',    'en' => 'Logout'],
        'login'            => ['ar' => 'تسجيل الدخول',    'en' => 'Login'],
        
        // Actions
        'search'           => ['ar' => 'بحث',             'en' => 'Search'],
        'clear'            => ['ar' => 'مسح',             'en' => 'Clear'],
        'save'             => ['ar' => 'حفظ',             'en' => 'Save'],
        'saving'           => ['ar' => 'جاري الحفظ...',   'en' => 'Saving...'],
        'edit'             => ['ar' => 'تعديل',           'en' => 'Edit'],
        'view'             => ['ar' => 'عرض',             'en' => 'View'],
        'delete'           => ['ar' => 'حذف',             'en' => 'Delete'],
        'print_pdf'        => ['ar' => 'طباعة PDF',       'en' => 'Print PDF'],
        'send_notification'=> ['ar' => 'إرسال إشعار',     'en' => 'Send Notification'],
        'back'             => ['ar' => 'العودة',          'en' => 'Back'],
        'next'             => ['ar' => 'التالي',          'en' => 'Next'],
        'previous'         => ['ar' => 'السابق',          'en' => 'Previous'],
        'cancel'           => ['ar' => 'إلغاء',           'en' => 'Cancel'],
        'print'            => ['ar' => 'طباعة',           'en' => 'Print'],
        'download_pdf'     => ['ar' => 'تحميل PDF',       'en' => 'Download PDF'],
        'export'           => ['ar' => 'تصدير',           'en' => 'Export'],
        'import'           => ['ar' => 'استيراد',         'en' => 'Import'],

        // Form labels
        'vehicle_info'     => ['ar' => 'معلومات السيارة', 'en' => 'Vehicle Information'],
        'assessment_info'  => ['ar' => 'معلومات التقييم',  'en' => 'Assessment Information'],
        'customer_info'    => ['ar' => 'معلومات العميل',   'en' => 'Customer Information'],
        'seller_info'      => ['ar' => 'معلومات البائع',   'en' => 'Seller Information'],
        'plate_number'     => ['ar' => 'رقم اللوحة',       'en' => 'Plate Number'],
        'chassis_number'   => ['ar' => 'رقم الشاسيه',      'en' => 'Chassis Number'],
        'brand'            => ['ar' => 'الماركة',          'en' => 'Brand'],
        'model'            => ['ar' => 'الموديل',          'en' => 'Model'],
        'year'             => ['ar' => 'سنة الصنع',        'en' => 'Year'],
        'fuel_type'        => ['ar' => 'نوع الوقود',       'en' => 'Fuel Type'],
        'mileage'          => ['ar' => 'الكيلومتر',        'en' => 'Mileage (KM)'],
        'package_type'     => ['ar' => 'نوع الباقة',       'en' => 'Package Type'],
        'inspection_date'  => ['ar' => 'تاريخ الفحص',      'en' => 'Inspection Date'],
        'time_in'          => ['ar' => 'ساعة الدخول',      'en' => 'Time In'],
        'time_out'         => ['ar' => 'ساعة الخروج',      'en' => 'Time Out'],
        'license_seen'     => ['ar' => 'هل رأيت الرخصة؟',  'en' => 'License Seen?'],
        'customer_name'    => ['ar' => 'اسم المشتري',      'en' => 'Customer Name'],
        'customer_phone'   => ['ar' => 'هاتف المشتري',     'en' => 'Customer Phone'],
        'seller_name'      => ['ar' => 'اسم البائع',       'en' => 'Seller Name'],
        'seller_phone'     => ['ar' => 'هاتف البائع',      'en' => 'Seller Phone'],

        // Statistics
        'total_reports'    => ['ar' => 'إجمالي التقارير',  'en' => 'Total Reports'],
        'today_reports'    => ['ar' => 'تقارير اليوم',      'en' => "Today's Reports"],
        'completed'        => ['ar' => 'مكتمل',            'en' => 'Completed'],
        'pending'          => ['ar' => 'قيد الانتظار',      'en' => 'Pending'],
        'draft_reports'    => ['ar' => 'المسودات',          'en' => 'Drafts'],

        // Messages
        'confirm_delete'   => ['ar' => 'هل أنت متأكد من حذف هذا التقرير؟', 'en' => 'Are you sure you want to delete this report?'],
        'saved_success'    => ['ar' => 'تم الحفظ بنجاح',   'en' => 'Saved successfully'],
        'save_failed'      => ['ar' => 'فشل الحفظ',        'en' => 'Save failed'],
        'no_reports'       => ['ar' => 'لا توجد تقارير',   'en' => 'No reports found'],
        'create_first'     => ['ar' => 'إنشاء أول تقرير',  'en' => 'Create First Report'],
        
        // For Cutomers UI
        'customers'          => ['ar' => 'العملاء',          'en' => 'Customers'],
        'add_customer'       => ['ar' => 'إضافة عميل',       'en' => 'Add Customer'],
        'edit_customer'      => ['ar' => 'تعديل عميل',       'en' => 'Edit Customer'],
        'delete_customer'    => ['ar' => 'حذف عميل',         'en' => 'Delete Customer'],
        'customer_name_ar'   => ['ar' => 'الاسم (عربي)',     'en' => 'Name (Arabic)'],
        'customer_name_en'   => ['ar' => 'الاسم (إنجليزي)',   'en' => 'Name (English)'],
        'phone'              => ['ar' => 'رقم الهاتف',        'en' => 'Phone Number'],
        'email'              => ['ar' => 'البريد الإلكتروني',  'en' => 'Email Address'],
        'address'            => ['ar' => 'العنوان',           'en' => 'Address'],
        'id_number'          => ['ar' => 'رقم الهوية / الإقامة', 'en' => 'ID / Iqama / Passport'],
        'notes'              => ['ar' => 'ملاحظات',           'en' => 'Notes'],
        'status'             => ['ar' => 'الحالة',            'en' => 'Status'],
        'active'             => ['ar' => 'نشط',               'en' => 'Active'],
        'inactive'           => ['ar' => 'غير نشط',           'en' => 'Inactive'],
        'no_customers'       => ['ar' => 'لا يوجد عملاء',     'en' => 'No customers found'],
        'customer_saved'     => ['ar' => 'تم حفظ العميل بنجاح', 'en' => 'Customer saved successfully'],
        'customer_deleted'   => ['ar' => 'تم حذف العميل بنجاح', 'en' => 'Customer deleted successfully'],
        // ============================================================
        // Add to UI_TRANSLATIONS
        // ============================================================
        'search_title'        => ['ar' => 'بحث متقدم',              'en' => 'Advanced Search'],
        'search_plate'        => ['ar' => 'رقم اللوحة',             'en' => 'Plate Number'],
        'search_chassis'      => ['ar' => 'رقم الشاسيه',            'en' => 'Chassis Number'],
        'search_customer'     => ['ar' => 'اسم العميل',             'en' => 'Customer Name'],
        'search_brand'        => ['ar' => 'الماركة',                'en' => 'Brand'],
        'search_date_from'    => ['ar' => 'من تاريخ',               'en' => 'Date From'],
        'search_date_to'      => ['ar' => 'إلى تاريخ',              'en' => 'Date To'],
        'search_status'       => ['ar' => 'الحالة',                 'en' => 'Status'],
        'search_fuel'         => ['ar' => 'نوع الوقود',             'en' => 'Fuel Type'],
        'search_tech'         => ['ar' => 'الفني',                  'en' => 'Technician'],
        'search_results'      => ['ar' => 'نتائج البحث',            'en' => 'Search Results'],
        'search_filters'      => ['ar' => 'فلاتر البحث',            'en' => 'Search Filters'],
        'btn_search'          => ['ar' => 'بحث',                    'en' => 'Search'],
        'btn_clear'           => ['ar' => 'مسح',                    'en' => 'Clear'],
        'btn_export_csv'      => ['ar' => 'تصدير CSV',              'en' => 'Export CSV'],
        'no_results'          => ['ar' => 'لا توجد نتائج',          'en' => 'No results found'],
        'records'             => ['ar' => 'سجل',                    'en' => 'records'],
        'technician'          => ['ar' => 'الفني',                  'en' => 'Technician'],
        'all'                 => ['ar' => 'الكل',                   'en' => 'All'],
        'status_complete'     => ['ar' => 'مكتمل',                 'en' => 'Complete'],
        'status_draft'        => ['ar' => 'مسودة',                 'en' => 'Draft'],
        'fuel'                => ['ar' => 'الوقود',                 'en' => 'Fuel'],
        'year'                => ['ar' => 'السنة',                  'en' => 'Year'],
        'search_year'         => ['ar' => 'سنة الصنع',              'en' => 'Year'],
        // ============================================================
        // Add to UI_TRANSLATIONS
        // ============================================================
        'diagrams'           => ['ar' => 'الرسوم البيانية',        'en' => 'Diagrams'],
        'diagram_library'    => ['ar' => 'مكتبة الرسوم البيانية',   'en' => 'Diagram Library'],
        'upload_diagram'     => ['ar' => 'رفع رسم بياني',           'en' => 'Upload Diagram'],
        'select_diagrams'    => ['ar' => 'اختر الرسوم البيانية',    'en' => 'Select Diagrams'],
        'attached_diagrams'  => ['ar' => 'الرسوم المرفقة',          'en' => 'Attached Diagrams'],
        'click_to_view_full' => ['ar' => 'اضغط للتكبير',            'en' => 'Click to enlarge'],
        'no_diagrams'        => ['ar' => 'لا توجد رسوم بيانية',     'en' => 'No diagrams found'],
        'category_chassis'   => ['ar' => 'هيكل السيارة',            'en' => 'Chassis'],
        'category_engine'    => ['ar' => 'المحرك',                  'en' => 'Engine'],
        'category_suspension'=> ['ar' => 'نظام التعليق',             'en' => 'Suspension'],
        'category_interior'  => ['ar' => 'المقصورة الداخلية',       'en' => 'Interior'],
        'category_electrical'=> ['ar' => 'النظام الكهربائي',         'en' => 'Electrical'],
        'diagram_saved'      => ['ar' => 'تم حفظ الرسم البياني',     'en' => 'Diagram saved'],
        'diagram_deleted'    => ['ar' => 'تم حذف الرسم البياني',     'en' => 'Diagram deleted'],
        'confirm_delete_diagram' => ['ar' => 'هل أنت متأكد من حذف هذا الرسم البياني؟', 'en' => 'Are you sure you want to delete this diagram?'],
    ]);
}

// ============================================================
// 15. HELPER FUNCTIONS
// ============================================================

/**
 * Get translated name for an inspection check item
 * 
 * @param string $key The item_key from database
 * @param string $lang 'ar' or 'en'
 * @return string Translated name
 */
if (!function_exists('getInspectionName')) {
    function getInspectionName($key, $lang = 'ar') {
        $dict = INSPECTION_CHECKS_DICT;
        return $dict[$key][$lang] ?? str_replace('_', ' ', $key);
    }
}

/**
 * Get translated name for a body panel
 * 
 * @param string $key The panel_key from database
 * @param string $lang 'ar' or 'en'
 * @return string Translated name
 */
if (!function_exists('getBodyName')) {
    function getBodyName($key, $lang = 'ar') {
        $dict = BODY_PANELS_DICT;
        return $dict[$key][$lang] ?? str_replace('_', ' ', $key);
    }
}

/**
 * Get translated UI text
 * 
 * @param string $key The UI key
 * @param string $lang 'ar' or 'en'
 * @return string Translated text
 */
if (!function_exists('uiText')) {
    function uiText($key, $lang = 'ar') {
        $dict = UI_TRANSLATIONS;
        return $dict[$key][$lang] ?? $key;
    }
}

/**
 * Get translated section name
 * 
 * @param string $key The section key
 * @param string $lang 'ar' or 'en'
 * @return string Translated section name
 */
if (!function_exists('sectionName')) {
    function sectionName($key, $lang = 'ar') {
        $dict = SECTION_NAMES;
        return $dict[$key][$lang] ?? $key;
    }
}

/**
 * Get translated section header
 * 
 * @param string $key The section key
 * @param string $lang 'ar' or 'en'
 * @return string Translated section header
 */
if (!function_exists('sectionHeader')) {
    function sectionHeader($key, $lang = 'ar') {
        $dict = SECTION_HEADERS;
        return $dict[$key][$lang] ?? $key;
    }
}

// ============================================================
// 16. BUILD INSPECTION CHECK SECTIONS (for forms)
// ============================================================

/**
 * Build the full inspection check sections array
 * This matches the structure used in report-new.php
 */
if (!function_exists('getInspectionSections')) {
    function getInspectionSections() {
        // Define the keys for each section
        $sectionKeys = [
            'engine' => [
                'irregular_work', 'engine_noise', 'gearbox_noise', 'gear_knock',
                'water_oil_mix', 'compression_needed', 'urgent_service', 'steam_blow',
                'injector_noise', 'sprayer_noise', 'exhaust_manifold', 'diff_noise',
                'black_smoke', 'white_smoke', 'engine_oil_leak', 'gearbox_oil_leak',
                'diff_oil_leak', 'turbo_oil_leak', 'intercooler_leak', 'coolant_leak',
                'fuel_leak', 'belts_condition', 'gearbox_condition', 'turbo_condition',
                'oil_level', 'coolant_ratio', 'coolant_condition', 'antifreeze',
                'brake_fluid', 'plastic_parts'
            ],
            'underbody' => [
                'combustion_leak', 'front_play', 'rear_play', 'steering_play',
                'power_steering_leak', 'steering_box_leak', 'steering_shaft_leak',
                'brake_center_leak', 'brake_hose_leak', 'front_brake_disc',
                'front_brake_pad', 'rear_brake_disc', 'rear_brake_pad',
                'exhaust_system', 'particle_filter', 'tires_condition',
                'rims_condition', 'underbody_frame'
            ],
            'electrical' => [
                'engine_ecu', 'airbag_ecu', 'ac_ecu', 'battery',
                'brake_ecu', 'gearbox_ecu', 'ac_pressure', 'wiring'
            ],
            'airbag' => [
                'driver_airbag', 'passenger_airbag', 'side_curtain', 'airbag_ecu_main'
            ],
            'interior' => [
                'ac_heating', 'interior_lights', 'dashboard', 'horn',
                'remote_controls', 'exterior_lights', 'sunroof', 'headlight_wash'
            ],
            'accessories' => [
                'windows', 'wipers', 'interior_trim', 'seatbelts',
                'radio', 'interior_issues', 'steering_wheel', 'mirrors',
                'door_trim', 'seats', 'spare_wheel', 'parking_sensors', 'gear_lever'
            ]
        ];

        $sections = [];
        foreach ($sectionKeys as $sectionKey => $keys) {
            $items = [];
            foreach ($keys as $itemKey) {
                $items[] = [
                    'key' => $itemKey,
                    'ar' => INSPECTION_CHECKS_DICT[$itemKey]['ar'] ?? $itemKey,
                    'en' => INSPECTION_CHECKS_DICT[$itemKey]['en'] ?? $itemKey,
                ];
            }
            $sections[] = [
                'key' => $sectionKey,
                'ar' => SECTION_HEADERS[$sectionKey]['ar'] ?? $sectionKey,
                'en' => SECTION_HEADERS[$sectionKey]['en'] ?? $sectionKey,
                'items' => $items,
            ];
        }
        return $sections;
    }
}

// ============================================================
// 17. BUILD BODY PANEL GROUPS (for forms)
// ============================================================

/**
 * Build the body panel groups array
 * This matches the structure used in report-new.php
 */
if (!function_exists('getBodyPanelGroups')) {
    function getBodyPanelGroups() {
        $allPanels = BODY_PANELS_DICT;
        
        $groups = [
            'right' => [
                'title_ar' => 'يمين',
                'title_en' => 'Right Side',
                'panels' => []
            ],
            'left' => [
                'title_ar' => 'يسار',
                'title_en' => 'Left Side',
                'panels' => []
            ],
            'top' => [
                'title_ar' => 'فوق',
                'title_en' => 'Top',
                'panels' => []
            ],
            'front_rear' => [
                'title_ar' => 'أمام / خلف',
                'title_en' => 'Front / Rear',
                'panels' => []
            ]
        ];

        $groupKeys = [
            'right' => [
                'right_front_fender', 'right_front_door', 'right_rear_door', 'right_rear_fender',
                'right_sill', 'right_a_pillar', 'right_b_pillar', 'roof',        // was right_roof_rail
                'right_c_pillar', 'right_d_pillar', 'right_platform', 'right_chassis'
            ],
            'left' => [
                'left_front_fender', 'left_front_door', 'left_rear_door', 'left_rear_fender',
                'left_sill', 'left_a_pillar', 'left_b_pillar', 'roof',           // was left_roof_rail
                'left_c_pillar', 'left_d_pillar', 'left_platform', 'left_chassis'
            ],
            'top' => ['hood', 'roof', 'trunk'],                                   // was trunk_top
            'front_rear' => ['trunk', 'rear_panel', 'trunk_floor', 'rear_bumper', 'front_bumper', 'front_panel'] // was trunk_door
        ];

        foreach ($groupKeys as $groupKey => $keys) {
            foreach ($keys as $panelKey) {
                $groups[$groupKey]['panels'][] = [
                    'key' => $panelKey,
                    'ar' => $allPanels[$panelKey]['ar'] ?? str_replace('_', ' ', $panelKey),
                    'en' => $allPanels[$panelKey]['en'] ?? str_replace('_', ' ', $panelKey),
                ];
            }
        }

        return $groups;
    }
}