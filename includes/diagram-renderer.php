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
 * mPDF's own data-URI detector (vendor/mpdf/mpdf/src/Image/ImageProcessor.php)
 * uses a regex ending in `(.*)` with no DOTALL modifier — `.` does not match
 * newlines in PCRE by default. Inkscape's Plain SVG export wraps the
 * embedded car-photo's base64 payload across multiple lines (as literal
 * `&#10;` entities, which decode to real newlines once PHP reads the file
 * as XML-ish text). The moment that payload contains a newline, mPDF's
 * regex silently captures only the first line and truncates the rest —
 * a corrupted, undecodable image that renders as nothing, while the
 * vector panel paths (parsed as real XML, not this regex) render fine.
 * Collapsing the base64 payload to one line here fixes it for mPDF, and
 * is harmless for the browser too (which never had this bug).
 */
function collapseBodyDiagramImageData(string $svg): string
{
    return preg_replace_callback(
        '/(xlink:href="data:image\/[a-zA-Z0-9.+-]+;base64,)([^"]+)(")/s',
        function ($m) {
            // at this point the file is still raw text — Inkscape's line
            // wraps are literal "&#10;"/"&#13;" entity text, not yet
            // decoded to real newline characters, so \s+ alone misses
            // them. Strip both forms defensively.
            $clean = preg_replace('/&#(?:10|13|x0?[ad]);/i', '', $m[2]);
            $clean = preg_replace('/\s+/', '', $clean);
            return $m[1] . $clean . $m[3];
        },
        $svg
    );
}

/**
 * PDF-only fix: some traced views (currently Right) mirror the Left
 * view's photo using transform="scale(-1,1)" x="-870" on the <image>
 * element — perfectly valid SVG, honored correctly by every browser.
 * But mPDF's svgImage() (vendor/mpdf/mpdf/src/Image/Svg.php) only ever
 * reads x/y/width/height/preserveAspectRatio/xlink:href off an <image>
 * — it never looks at a transform attribute on that element at all.
 * The negative x then lands the whole photo off-canvas, invisible.
 * Fix: physically flip the JPEG here with GD, drop the transform, and
 * reset x back to 0 — the image no longer needs any SVG-level mirror
 * trick to look correct. Web rendering doesn't call this (browsers
 * never had the bug), only the PDF path does.
 */
function unmirrorBodyDiagramImage(string $svg): string
{
    return preg_replace_callback(
        '/<image\b([^>]*?)transform="scale\(-1,\s*1\)"([^>]*?)\/>/s',
        function ($m) {
            $attrs = $m[1] . $m[2];
            if (!preg_match('/xlink:href="data:image\/jpeg;base64,([^"]+)"/', $attrs, $hm)) {
                return $m[0]; // not a jpeg data URI we recognize — leave untouched
            }
            if (!function_exists('imagecreatefromstring') || !function_exists('imageflip')) {
                return $m[0]; // GD unavailable — leave as-is rather than fatal error
            }
            $raw = base64_decode($hm[1]);
            $im = @imagecreatefromstring($raw);
            if ($im === false) {
                return $m[0];
            }
            imageflip($im, IMG_FLIP_HORIZONTAL);
            ob_start();
            imagejpeg($im, null, 90);
            $flippedData = ob_get_clean();
            imagedestroy($im);
            $flippedB64 = base64_encode($flippedData);

            $newAttrs = preg_replace('/xlink:href="data:image\/jpeg;base64,[^"]+"/', 'xlink:href="data:image/jpeg;base64,' . $flippedB64 . '"', $attrs);
            $newAttrs = preg_replace('/\bx="-?\d+(\.\d+)?"/', 'x="0"', $newAttrs);

            return '<image' . $newAttrs . '/>';
        },
        $svg
    );
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
/**
 * Resolve a traced shape's raw id (e.g. "sedan_left_front_fender") to its
 * real BODY_PANELS_DICT key, or null if it's not a tracked panel (glass,
 * grille, or anything else intentionally left untracked).
 * Both the web widget and the PDF renderer go through this single
 * function so their panel resolution can never silently drift apart.
 */
function resolveBodyPanelKey(string $rawId): ?string
{
    $suffix = preg_replace('/^(sedan_|suv_)/', '', $rawId);
    $panelKey = PANEL_ID_SUFFIX_MAP[$suffix] ?? $suffix;
    return array_key_exists($panelKey, BODY_PANELS_DICT) ? $panelKey : null;
}

function wireBodyDiagramSvg(string $style, string $view): string
{
    $path = bodyDiagramSourcePath($style, $view);
    if (!is_file($path)) {
        return '<!-- diagram not found: ' . htmlspecialchars($path) . ' -->';
    }
    $svg = file_get_contents($path);
    $svg = collapseBodyDiagramImageData($svg);

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
        $panelKey = resolveBodyPanelKey($idm[1]);

        if ($panelKey === null) {
            // not a tracked panel (shouldn't happen post-cleanup, but fail safe
            // rather than mis-tag an unknown shape as clickable/paintable)
            return str_replace('/>', ' class="panel-reference" data-panel="" />', $tag);
        }

        // Inkscape bakes fill:#xxxxxx;fill-opacity:N directly into each
        // shape's style="" attribute. An inline style attribute always
        // beats setAttribute('fill', ...) from JS, so paint would silently
        // fail (or worse, always render the shape's original trace color).
        // Strip those, AND the baked-in stroke — Inkscape assigned each
        // shape's stroke color/width independently across many tracing
        // sessions (mostly black, sometimes white), so left alone they
        // show up as inconsistent thick borders. The .panel-clickable CSS
        // class now owns stroke entirely, so every shape looks the same.
        $tag = preg_replace('/\bfill\s*:\s*[^;"]+;?/i', '', $tag);
        $tag = preg_replace('/\bfill-opacity\s*:\s*[^;"]+;?/i', '', $tag);
        $tag = preg_replace('/\bstroke[a-z-]*\s*:\s*[^;"]+;?/i', '', $tag);

        // strip any pre-existing class="" / data-panel="" first — some
        // shapes (bumper halves) already carry a leftover data-panel
        // attribute from an earlier hand-wiring pass. Adding a second one
        // is invalid XML: browsers silently tolerate it, but mPDF's strict
        // XML parser does not, so this must never be assumed clean.
        $tag = preg_replace('/\s+class="[^"]*"/', '', $tag);
        $tag = preg_replace('/\s+data-panel="[^"]*"/', '', $tag);

        $tag = str_replace('/>', ' class="panel-clickable" data-panel="' . $panelKey . '" />', $tag);
        return $tag;
    }, $svg);

    return $svg;
}

/**
 * ============================================================
 * PDF RENDERING (pdf/generate.php)
 * mPDF renders SVG through its own XML parser (vendor/mpdf/mpdf/src/Image/Svg.php),
 * not a browser — it does not apply CSS classes/stylesheets to SVG shapes,
 * only attributes it parses directly off each element. So for the PDF we
 * bake the *actual final* fill color for the report's saved status
 * straight into each path's style attribute, server-side, at generation
 * time — no class, no JS, no click interactivity needed or possible.
 * ============================================================
 */

/**
 * Same wiring as wireBodyDiagramSvg(), but colors every tracked panel
 * with its real saved status color instead of leaving it paintable.
 * $panelStatuses: [panel_key => status_string] pulled from body_panels.
 * Requires panelColor(string $status): string to already be defined
 * (pdf/generate.php defines it) — falls back to '#ffffff' if not found.
 */
function renderBodyDiagramForPdf(string $style, string $view, array $panelStatuses): string
{
    $path = bodyDiagramSourcePath($style, $view);
    if (!is_file($path)) {
        return '';
    }
    $svg = file_get_contents($path);
    $svg = collapseBodyDiagramImageData($svg);
    $svg = unmirrorBodyDiagramImage($svg);

    $svg = preg_replace('/<\?xml[^>]*\?>\s*/', '', $svg);
    $svg = preg_replace('/<!--.*?-->/s', '', $svg);
    $svg = preg_replace('/\s+(inkscape|sodipodi):[a-zA-Z-]+="[^"]*"/', '', $svg);
    $svg = preg_replace('/<sodipodi:namedview\b.*?(\/>|<\/sodipodi:namedview>)/s', '', $svg);

    $svg = preg_replace_callback('/<path\b[^>]*\/?>/', function ($m) use ($panelStatuses) {
        $tag = $m[0];
        if (!preg_match('/\bid="([^"]+)"/', $tag, $idm)) {
            return $tag;
        }
        $panelKey = resolveBodyPanelKey($idm[1]);

        // strip the baked-in trace fill/fill-opacity/stroke from the
        // EXISTING style="" attribute (there may be no style attribute at
        // all, in which case this is a no-op and one gets added below).
        // Print has no hover state, so stroke is always fully removed
        // here — only the fill color communicates status.
        $tag = preg_replace('/\bfill\s*:\s*[^;"]+;?/i', '', $tag);
        $tag = preg_replace('/\bfill-opacity\s*:\s*[^;"]+;?/i', '', $tag);
        $tag = preg_replace('/\bstroke[a-z-]*\s*:\s*[^;"]+;?/i', '', $tag);

        $newFillCss = ($panelKey === null)
            ? 'fill:none;stroke:none;'
            : (function () use ($panelKey, $panelStatuses) {
                $status = $panelStatuses[$panelKey]['status'] ?? 'original';
                $color = function_exists('panelColor') ? panelColor($status) : '#ffffff';
                $opacity = ($status === 'original' || !$status) ? 0.03 : 0.62;
                return 'fill:' . $color . ';fill-opacity:' . $opacity . ';stroke:none;';
            })();

        if (preg_match('/style="([^"]*)"/', $tag, $sm)) {
            // an existing style="" attribute is present (the normal case) —
            // merge into it, never add a second style attribute (invalid XML,
            // and would silently break mPDF's strict SVG parser)
            $merged = $newFillCss . $sm[1];
            $tag = preg_replace('/style="[^"]*"/', 'style="' . $merged . '"', $tag, 1);
        } else {
            $tag = str_replace('/>', ' style="' . $newFillCss . '" />', $tag);
        }
        return $tag;
    }, $svg);

    return $svg;
}

/**
 * Wrap renderBodyDiagramForPdf() output as a data URI, ready to drop
 * straight into an <img src="..."> inside HTML passed to $mpdf->WriteHTML().
 * mPDF's ImageProcessor explicitly supports data:image/svg+xml;base64,...
 * (vendor/mpdf/mpdf/src/Image/ImageProcessor.php line ~145).
 */
function bodyDiagramDataUri(string $style, string $view, array $panelStatuses): string
{
    $svg = renderBodyDiagramForPdf($style, $view, $panelStatuses);
    if ($svg === '') {
        return '';
    }
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Renders the 5 views across 3 rows suited to a portrait-oriented PDF
 * page: Front+Back together, Left+Right together, Top alone on its own
 * row (it's a tall/narrow silhouette, not a wide one like the other 4,
 * so it doesn't pair well side-by-side with anything).
 * Call this directly from pdf/generate.php where the body panel
 * section is built.
 */
function renderBodyDiagramRowForPdf(string $style, array $panelStatuses): void
{
    $img = function (string $viewKey) use ($style, $panelStatuses) {
        $label = BODY_DIAGRAM_VIEWS[$viewKey];
        $uri = bodyDiagramDataUri($style, $viewKey, $panelStatuses);
        return '<img src="' . $uri . '" style="width:100%">'
             . '<div style="font-size:7pt;color:#666">' . $label['ar'] . ' / ' . $label['en'] . '</div>';
    };
    ?>
    <table style="width:100%;margin-bottom:2px">
    <tr>
        <td style="text-align:center;padding:2px 4px;width:50%"><?= $img('front') ?></td>
        <td style="text-align:center;padding:2px 4px;width:50%"><?= $img('back') ?></td>
    </tr>
    </table>
    <table style="width:100%;margin-bottom:2px">
    <tr>
        <td style="text-align:center;padding:2px 4px;width:50%"><?= $img('left') ?></td>
        <td style="text-align:center;padding:2px 4px;width:50%"><?= $img('right') ?></td>
    </tr>
    </table>
    <table style="width:100%;margin-bottom:8px">
    <tr>
        <td style="text-align:center;padding:2px 4px">
            <img src="<?= bodyDiagramDataUri($style, 'top', $panelStatuses) ?>" style="width:36%">
            <div style="font-size:7pt;color:#666"><?= BODY_DIAGRAM_VIEWS['top']['ar'] ?> / <?= BODY_DIAGRAM_VIEWS['top']['en'] ?></div>
        </td>
    </tr>
    </table>
    <?php
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
