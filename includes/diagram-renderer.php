<?php
/**
 * ============================================================
 * BODY PANEL DIAGRAM RENDERER
 * Loads the traced SVG car-view files, wires each traced shape's
 * id to the real BODY_PANELS_DICT key (config/dictionaries.php),
 * and outputs ready-to-embed SVG markup for the body panel map
 * step in report-new.php (and later, pdf/generate.php).
 *
 * Source SVGs live in assets/img/cars-views/, named:
 *   Sedan-{Left,Right,Top,Front,Back}View.svg
 *   SUV {Left,Right,Top,Front,Back}.svg
 * Each traced <path> has an id like "sedan_left_front_fender" or
 * "suv_right_a_pillar" — a vehicle-type prefix + a panel name that
 * is either identical to its dictionary key, or one of a small set
 * of known synonyms handled by PANEL_ID_SUFFIX_MAP below.
 * ============================================================
 */

// suffix (id with the sedan_/suv_ prefix stripped) => real BODY_PANELS_DICT key
// only listed where the traced label differs from the dictionary key;
// anything not listed here is assumed to already match the dict key exactly.
if (!defined('PANEL_ID_SUFFIX_MAP')) {
    define('PANEL_ID_SUFFIX_MAP', [
        'left_quarter_panel'  => 'left_rear_fender',
        'right_quarter_panel' => 'right_rear_fender',
        'left_rocker_panel'   => 'left_sill',
        'right_rocker_panel'  => 'right_sill',
        'roof_panel'          => 'roof',
        'trunklid'            => 'trunk',
        'left_front_bumper'   => 'front_bumper',
        'right_front_bumper'  => 'front_bumper',
        'rear_bumper_left'    => 'rear_bumper',
        'rear_bumper_right'   => 'rear_bumper',
        // defensive fallbacks for known one-off naming inconsistencies —
        // prefer fixing the source SVG's id directly over relying on these.
        'right_fender'        => 'right_front_fender',
        'right_rear_bumper'   => 'rear_bumper',
    ]);
}

if (!defined('BODY_DIAGRAM_VIEWS')) {
    define('BODY_DIAGRAM_VIEWS', [
        'front' => ['ar' => 'أمام',  'en' => 'Front'],
        'left'  => ['ar' => 'يسار',  'en' => 'Left'],
        'top'   => ['ar' => 'أعلى',  'en' => 'Top'],
        'right' => ['ar' => 'يمين',  'en' => 'Right'],
        'back'  => ['ar' => 'خلف',   'en' => 'Back'],
    ]);
}

/**
 * Map a body style + view key to its source SVG filename.
 */
function bodyDiagramSourcePath(string $style, string $view): string
{
    $dir = __DIR__ . '/../assets/img/cars-views/';
    $map = [
        'sedan' => [
            'front' => 'Sedan-FrontView.svg', 'back' => 'Sedan-BackView.svg',
            'left'  => 'Sedan-LeftView.svg',  'right' => 'Sedan-RightView.svg',
            'top'   => 'Sedan-TopView.svg',
        ],
        'suv' => [
            'front' => 'SUV Front.svg', 'back' => 'SUV Back.svg',
            'left'  => 'SUV Left.svg',  'right' => 'SUV Right.svg',
            'top'   => 'SUV Top.svg',
        ],
    ];
    return $dir . ($map[$style][$view] ?? '');
}

/**
 * Read a traced SVG file and wire every panel <path> with
 * class="panel-clickable" data-panel="<dict key>", stripping the
 * sedan_/suv_ prefix and translating known naming synonyms.
 * Any inkscape:/sodipodi: leftovers are stripped defensively even
 * though current exports are already clean Plain SVG.
 */
function wireBodyDiagramSvg(string $style, string $view): string
{
    $path = bodyDiagramSourcePath($style, $view);
    if (!is_file($path)) {
        return '<!-- diagram not found: ' . htmlspecialchars($path) . ' -->';
    }
    $svg = file_get_contents($path);

    // strip xml declaration, comments, and any inkscape/sodipodi elements
    $svg = preg_replace('/<\?xml[^>]*\?>\s*/', '', $svg);
    $svg = preg_replace('/<!--.*?-->/s', '', $svg);
    $svg = preg_replace('/\s+(inkscape|sodipodi):[a-zA-Z-]+="[^"]*"/', '', $svg);
    $svg = preg_replace('/<sodipodi:namedview\b.*?(\/>|<\/sodipodi:namedview>)/s', '', $svg);

    $svg = preg_replace_callback('/<path\b[^>]*\/?>/', function ($m) {
        $tag = $m[0];
        if (!preg_match('/\bid="([^"]+)"/', $tag, $idm)) {
            return $tag;
        }
        $rawId = $idm[1];
        $suffix = preg_replace('/^(sedan_|suv_)/', '', $rawId);
        $panelKey = PANEL_ID_SUFFIX_MAP[$suffix] ?? $suffix;

        if (!array_key_exists($panelKey, BODY_PANELS_DICT)) {
            // not a tracked panel (shouldn't happen post-cleanup, but fail safe
            // rather than mis-tag an unknown shape as clickable/paintable)
            return str_replace('/>', ' class="panel-reference" data-panel="" />', $tag);
        }

        $tag = str_replace('/>', ' class="panel-clickable" data-panel="' . $panelKey . '" />', $tag);
        return $tag;
    }, $svg);

    return $svg;
}

/**
 * Render the full diagram widget: view tabs + all 10 wired SVGs
 * (both body styles embedded, toggled client-side so switching
 * body_style in Step 1 doesn't need a page reload), plus an
 * inline status-picker popover template.
 *
 * $currentStyle / $currentPanels are used only to pre-paint saved
 * statuses on load (edit mode); saving itself still goes through
 * the existing body[key][status] <select> elements untouched.
 */
function renderBodyDiagramWidget(string $currentStyle, array $currentPanels): void
{
    ?>
    <div class="body-diagram-widget" data-current-style="<?= htmlspecialchars($currentStyle) ?>">
        <div class="diagram-view-tabs">
            <?php foreach (BODY_DIAGRAM_VIEWS as $viewKey => $label): ?>
            <button type="button" class="view-tab<?= $viewKey === 'front' ? ' active' : '' ?>" data-view="<?= $viewKey ?>">
                <span class="ar"><?= $label['ar'] ?></span><span class="en"><?= $label['en'] ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="body-map-svg-wrap">
            <?php foreach (['sedan', 'suv'] as $style): ?>
                <?php foreach (BODY_DIAGRAM_VIEWS as $viewKey => $label): ?>
                <div class="diagram-slide"
                     data-style="<?= $style ?>"
                     data-view="<?= $viewKey ?>"
                     style="<?= ($style === $currentStyle && $viewKey === 'front') ? '' : 'display:none' ?>">
                    <?= wireBodyDiagramSvg($style, $viewKey) ?>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <div class="panel-status-popover hidden" id="panelStatusPopover">
            <div class="popover-panel-name" id="popoverPanelName"></div>
            <div class="popover-swatches">
                <?php foreach (PANEL_STATUS as $sKey => $sVal): ?>
                <button type="button" class="popover-swatch" data-status="<?= $sKey ?>" style="background:<?= $sVal['color'] ?>" title="<?= $sVal['ar'] ?> / <?= $sVal['en'] ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}
