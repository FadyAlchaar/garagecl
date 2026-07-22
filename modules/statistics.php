<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/lang.php';
require_once __DIR__.'/../config/auth.php';
requireAuth();

$pdo=db();
$pageTitle=t('stats_title');

// --- Stats queries ---
$totalReports    = (int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$completeReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='complete'")->fetchColumn();
$draftReports    = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='draft'")->fetchColumn();
$thisMonth       = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$avgMileage      = (int)$pdo->query("SELECT AVG(mileage) FROM reports WHERE mileage>0")->fetchColumn();
$avgPerf         = (float)$pdo->query("SELECT AVG(performance_percent) FROM dyno_results WHERE performance_percent>0")->fetchColumn();

// Monthly counts (last 12 months)
$monthly = $pdo->query("
    SELECT DATE_FORMAT(date_inspection,'%Y-%m') AS month, COUNT(*) AS cnt
    FROM reports WHERE date_inspection >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month ORDER BY month ASC
")->fetchAll();

// Fuel distribution
$fuels = $pdo->query("SELECT fuel_type, COUNT(*) AS cnt FROM reports GROUP BY fuel_type ORDER BY cnt DESC")->fetchAll();

// Top brands
$brands = $pdo->query("SELECT brand, COUNT(*) AS cnt FROM reports WHERE brand!='' GROUP BY brand ORDER BY cnt DESC LIMIT 10")->fetchAll();

// Status distribution
$statuses = $pdo->query("SELECT status, COUNT(*) AS cnt FROM reports GROUP BY status")->fetchAll();

// Technician performance
$techs = $pdo->query("
    SELECT u.full_name_ar, u.full_name_en, COUNT(r.id) AS cnt
    FROM reports r JOIN users u ON r.technician_id=u.id
    GROUP BY r.technician_id ORDER BY cnt DESC LIMIT 8
")->fetchAll();

// JSON encode for JS charts
$monthlyJson = json_encode($monthly, JSON_UNESCAPED_UNICODE);
$fuelsJson   = json_encode($fuels,   JSON_UNESCAPED_UNICODE);
$brandsJson  = json_encode($brands,  JSON_UNESCAPED_UNICODE);

require_once __DIR__.'/../includes/header.php';
?>

<div class="page-header">
    <h1><span class="ar"><?= t('stats_title') ?></span><span class="en">Statistics & Reports</span></h1>
</div>

<!-- KPI CARDS -->
<div class="grid-4">
    <div class="card text-center">
        <div style="font-size:2.5rem;font-weight:800;color:var(--primary)"><?= $totalReports ?></div>
        <div class="ar">إجمالي التقارير</div><div class="en">Total Reports</div>
    </div>
    <div class="card text-center">
        <div style="font-size:2.5rem;font-weight:800;color:var(--good)"><?= $completeReports ?></div>
        <div class="ar">مكتملة</div><div class="en">Complete</div>
    </div>
    <div class="card text-center">
        <div style="font-size:2.5rem;font-weight:800;color:#2563eb"><?= $thisMonth ?></div>
        <div class="ar">هذا الشهر</div><div class="en">This Month</div>
    </div>
    <div class="card text-center">
        <div style="font-size:2rem;font-weight:800;color:var(--warning)"><?= number_format($avgPerf,1) ?>%</div>
        <div class="ar">متوسط أداء المحرك</div><div class="en">Avg Engine Performance</div>
    </div>
</div>

<div class="grid-2">
<!-- MONTHLY CHART -->
<div class="card">
    <div class="card-header"><span class="ar"><?= t('stats_monthly') ?></span><span class="en">Monthly Reports</span></div>
    <canvas id="monthlyChart" height="200"></canvas>
</div>

<!-- FUEL PIE -->
<div class="card">
    <div class="card-header"><span class="ar">توزيع أنواع الوقود</span><span class="en">Fuel Type Distribution</span></div>
    <canvas id="fuelChart" height="200"></canvas>
</div>
</div>

<div class="grid-2">
<!-- BRANDS BAR -->
<div class="card">
    <div class="card-header"><span class="ar"><?= t('stats_brands') ?></span><span class="en">Top Brands</span></div>
    <canvas id="brandsChart" height="200"></canvas>
</div>

<!-- TECHNICIANS -->
<div class="card">
    <div class="card-header"><span class="ar">أداء الفنيين</span><span class="en">Technician Performance</span></div>
    <?php if(empty($techs)): ?>
    <p style="color:var(--text-muted);text-align:center;padding:2rem"><?= t('no_data') ?></p>
    <?php else: ?>
    <table class="reports-table" style="font-size:.9rem">
        <thead><tr><th>الفني / Technician</th><th>التقارير / Reports</th><th></th></tr></thead>
        <tbody>
        <?php $max=max(array_column($techs,'cnt'))?:1; foreach($techs as $tech): ?>
        <tr>
            <td><strong><?= clean($tech['full_name_ar']) ?></strong></td>
            <td style="font-weight:700;color:var(--primary)"><?= $tech['cnt'] ?></td>
            <td style="width:40%">
                <div style="background:#f0f2f5;border-radius:4px;height:8px">
                    <div style="background:var(--primary);width:<?= round($tech['cnt']/$max*100) ?>%;height:8px;border-radius:4px"></div>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</div>

<!-- Average mileage -->
<div class="card">
    <div class="card-header"><span class="ar">إحصائيات إضافية</span><span class="en">Additional Statistics</span></div>
    <div class="grid-3">
        <div style="text-align:center;padding:1rem">
            <div style="font-size:1.8rem;font-weight:700;color:var(--accent)"><?= number_format($avgMileage) ?></div>
            <div class="ar">متوسط الكيلومتر</div><div class="en">Avg Mileage (KM)</div>
        </div>
        <div style="text-align:center;padding:1rem">
            <div style="font-size:1.8rem;font-weight:700;color:var(--good)"><?= $completeReports ?></div>
            <div class="ar">تقارير مكتملة</div><div class="en">Completed</div>
        </div>
        <div style="text-align:center;padding:1rem">
            <div style="font-size:1.8rem;font-weight:700;color:var(--warning)"><?= $draftReports ?></div>
            <div class="ar">مسودات</div><div class="en">Drafts</div>
        </div>
    </div>
</div>

<script>
// Pure canvas charts — no external dependency

const COLORS=['#c0392b','#3b82f6','#22c55e','#f59e0b','#8b5cf6','#06b6d4','#f97316','#ec4899','#14b8a6','#84cc16'];

function drawBar(canvasId, labels, data, color='#c0392b'){
    const cv=document.getElementById(canvasId);
    if(!cv) return;
    const ctx=cv.getContext('2d');
    const W=cv.offsetWidth, H=cv.height=200;
    cv.width=W;
    const pad={t:20,r:10,b:40,l:45};
    const maxV=Math.max(...data,1);
    const bw=(W-pad.l-pad.r)/labels.length;
    ctx.clearRect(0,0,W,H);
    ctx.fillStyle='#f9fafb';ctx.fillRect(0,0,W,H);
    // Grid lines
    for(let i=0;i<=4;i++){
        const y=pad.t+(H-pad.t-pad.b)*i/4;
        ctx.strokeStyle='#e5e7eb';ctx.lineWidth=1;
        ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(W-pad.r,y);ctx.stroke();
        ctx.fillStyle='#9ca3af';ctx.font='10px sans-serif';ctx.textAlign='right';
        ctx.fillText(Math.round(maxV*(4-i)/4),pad.l-4,y+4);
    }
    // Bars
    labels.forEach((lbl,i)=>{
        const bh=(data[i]/maxV)*(H-pad.t-pad.b);
        const x=pad.l+i*bw+bw*.15;
        const y=H-pad.b-bh;
        ctx.fillStyle=color;
        ctx.beginPath();ctx.roundRect(x,y,bw*.7,bh,4);ctx.fill();
        ctx.fillStyle='#374151';ctx.font='9px sans-serif';ctx.textAlign='center';
        ctx.fillText(data[i],x+bw*.35,y-4);
        ctx.fillStyle='#6b7280';ctx.font='9px sans-serif';
        const sl=lbl.length>6?lbl.substr(-5):lbl;
        ctx.fillText(sl,x+bw*.35,H-pad.b+14);
    });
}

function drawPie(canvasId, labels, data){
    const cv=document.getElementById(canvasId);
    if(!cv) return;
    const ctx=cv.getContext('2d');
    const W=cv.offsetWidth, H=cv.height=200;
    cv.width=W;
    ctx.clearRect(0,0,W,H);
    ctx.fillStyle='#f9fafb';ctx.fillRect(0,0,W,H);
    const total=data.reduce((a,b)=>a+b,0)||1;
    const cx=W/2-60,cy=H/2,r=Math.min(cx,cy)-10;
    let angle=-Math.PI/2;
    data.forEach((v,i)=>{
        const slice=(v/total)*Math.PI*2;
        ctx.beginPath();ctx.moveTo(cx,cy);
        ctx.arc(cx,cy,r,angle,angle+slice);
        ctx.closePath();ctx.fillStyle=COLORS[i%COLORS.length];ctx.fill();
        ctx.strokeStyle='#fff';ctx.lineWidth=2;ctx.stroke();
        angle+=slice;
    });
    // Legend
    labels.forEach((lbl,i)=>{
        const y=20+i*22;
        if(y>H-10) return;
        ctx.fillStyle=COLORS[i%COLORS.length];ctx.fillRect(W-110,y,14,14);
        ctx.fillStyle='#374151';ctx.font='11px sans-serif';ctx.textAlign='left';
        ctx.fillText(lbl.substr(0,12)+' ('+data[i]+')',W-92,y+11);
    });
}

const monthly = <?= $monthlyJson ?>;
const fuels   = <?= $fuelsJson ?>;
const brands  = <?= $brandsJson ?>;

drawBar('monthlyChart', monthly.map(r=>r.month.substr(5)), monthly.map(r=>parseInt(r.cnt)), '#c0392b');
drawPie('fuelChart', fuels.map(r=>r.fuel_type), fuels.map(r=>parseInt(r.cnt)));
drawBar('brandsChart', brands.map(r=>r.brand), brands.map(r=>parseInt(r.cnt)), '#3b82f6');
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
