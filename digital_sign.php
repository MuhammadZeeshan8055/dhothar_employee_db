<?php
/**
 * Digital Sign page
 * Shows payment info + signature pad (or saved signature image).
 * Open with: digital_sign?type=delivery&id=123
 *            digital_sign?type=ride&id=456
 */

include('session.php');
include('database.php');
include('includes/digital_sign_helper.php');

$obj = new Database();

// ---- Read URL params ----
$type = trim($_GET['type'] ?? '');
$id = (int) ($_GET['id'] ?? 0);

$error = '';
$row = null;
$back_url = $base_url . 'delivery_earnings';
$type_label = '';

$table = digital_sign_table($type);

if ($type === 'delivery') {
    $back_url = $base_url . 'delivery_earnings';
    $type_label = 'Delivery';
} elseif ($type === 'ride') {
    $back_url = $base_url . 'ride_sharing_earnings';
    $type_label = 'Ride Sharing';
} else {
    $error = 'Invalid type. Use delivery or ride.';
}

if ($error === '' && $id <= 0) {
    $error = 'Missing record id.';
}

// ---- Load earning + employee name ----
if ($error === '') {
    $join = 'LEFT JOIN add_employee_details ON add_employee_details.id = ' . $table . '.employee_id';
    $columns = $table . '.*, add_employee_details.name';
    $where = $table . '.id = ' . $id;

    $obj->select($table, $columns, $join, $where, null, 1);
    $result = $obj->getResult();

    if (empty($result[0]['id'])) {
        $error = 'Record not found.';
    } else {
        $row = $result[0];
    }
}

// ---- Prepare display values ----
$employee_name = '';
$signature_file = '';
$already_signed = false;
$amount = 0;
$week_label = '';
$date_range = '';

if ($row) {
    $employee_name = $row['name'] ?? '';
    $signature_file = trim($row['signature_image'] ?? '');
    $already_signed = ($signature_file !== '');
    $amount = (float) ($row['total_balance'] ?? 0);
    $week_label = 'Week ' . (int) ($row['week_number'] ?? 0) . ' / ' . (int) ($row['week_year'] ?? 0);

    if (!empty($row['week_start']) && !empty($row['week_end'])) {
        $date_range = formatDate($row['week_start']) . ' to ' . formatDate($row['week_end']);
    }
}

// Can draw a new signature only if not signed yet
$can_sign = ($error === '' && $row && !$already_signed);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Digital Sign | Dhothar International</title>
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/custom.css">
    <?php include('components/theme_head.php'); ?>
    <style>
        body { background: #f1f5f9; margin: 0; padding: 12px; font-family: Arial, sans-serif; }
        .sign-box { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; }
        .sign-box h2 { margin: 0 0 12px; font-size: 20px; color: #1e293b; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .info-row .label { color: #64748b; }
        .info-row .value { color: #0f172a; font-weight: 600; text-align: right; }
        .amount-big { font-size: 28px; font-weight: 700; color: #0f766e; text-align: center; margin: 16px 0 8px; }
        .pad-title { margin: 18px 0 8px; font-size: 15px; font-weight: 600; color: #1e293b; }
        #signCanvas { width: 100%; height: 220px; border: 2px dashed #94a3b8; border-radius: 8px; background: #fff; touch-action: none; display: block; }
        .btn-row { display: flex; gap: 10px; margin-top: 14px; }
        .btn-row .btn { flex: 1; padding: 12px 10px; font-size: 15px; }
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 12px; }
        .msg-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .msg-ok { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .sig-preview { width: 100%; max-height: 220px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; object-fit: contain; }
        .back-link { display: inline-block; margin-bottom: 10px; color: #334155; font-size: 14px; }
        #statusMsg { display: none; margin-top: 12px; }
    </style>
</head>
<body>
<div class="sign-box">
    <a class="back-link" href="<?= htmlspecialchars($back_url) ?>">&larr; Back to earnings</a>
    <h2>Digital Sign — <?= htmlspecialchars($type_label) ?></h2>

    <?php if ($error !== ''): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>

    <?php else: ?>

        <?php if ($already_signed): ?>
            <div class="msg msg-ok">Signed on <?= htmlspecialchars(formatDate($row['signed_at'] ?? '')) ?> — marked as Paid</div>
        <?php endif; ?>

        <div class="info-row">
            <span class="label">Employee</span>
            <span class="value"><?= htmlspecialchars($employee_name) ?></span>
        </div>
        <div class="info-row">
            <span class="label">Week</span>
            <span class="value"><?= htmlspecialchars($week_label) ?></span>
        </div>
        <div class="info-row">
            <span class="label">Date range</span>
            <span class="value"><?= htmlspecialchars($date_range) ?></span>
        </div>

        <div class="amount-big"><?= number_format($amount, 2) ?></div>
        <p style="text-align:center;color:#64748b;font-size:13px;margin:0;">Amount paid (Total Balance)</p>

        <?php if ($already_signed): ?>
            <div class="pad-title">Saved signature</div>
            <img class="sig-preview" src="<?= $base_url ?>uploads/signatures/<?= htmlspecialchars($signature_file) ?>" alt="Signature">
        <?php else: ?>
            <div class="pad-title">Employee signature</div>
            <canvas id="signCanvas" width="500" height="220"></canvas>
            <div class="btn-row">
                <button type="button" class="btn btn-default" id="clearBtn">Clear</button>
                <button type="button" class="btn btn-success" id="confirmBtn">Confirm &amp; Save</button>
            </div>
            <div id="statusMsg" class="msg"></div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php if ($can_sign): ?>
<script>
// Simple signature pad (mouse + finger)
var canvas = document.getElementById('signCanvas');
var ctx = canvas.getContext('2d');
var drawing = false;
var hasInk = false;

ctx.strokeStyle = '#111';
ctx.lineWidth = 2.5;
ctx.lineCap = 'round';
ctx.lineJoin = 'round';

// Get x,y on the canvas (works for mouse and touch)
function getPos(e) {
    var rect = canvas.getBoundingClientRect();
    var clientX;
    var clientY;

    if (e.touches && e.touches.length) {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else {
        clientX = e.clientX;
        clientY = e.clientY;
    }

    return {
        x: (clientX - rect.left) * (canvas.width / rect.width),
        y: (clientY - rect.top) * (canvas.height / rect.height)
    };
}

function startDraw(e) {
    e.preventDefault();
    drawing = true;
    var pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
}

function moveDraw(e) {
    if (!drawing) {
        return;
    }
    e.preventDefault();
    var pos = getPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    hasInk = true;
}

function endDraw(e) {
    if (!drawing) {
        return;
    }
    e.preventDefault();
    drawing = false;
}

canvas.addEventListener('mousedown', startDraw);
canvas.addEventListener('mousemove', moveDraw);
canvas.addEventListener('mouseup', endDraw);
canvas.addEventListener('mouseleave', endDraw);

canvas.addEventListener('touchstart', startDraw, { passive: false });
canvas.addEventListener('touchmove', moveDraw, { passive: false });
canvas.addEventListener('touchend', endDraw);
canvas.addEventListener('touchcancel', endDraw);

document.getElementById('clearBtn').onclick = function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasInk = false;
    showMsg('', false, true);
};

document.getElementById('confirmBtn').onclick = function () {
    if (!hasInk) {
        showMsg('Please draw the signature first.', true);
        return;
    }

    var btn = document.getElementById('confirmBtn');
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    var form = new FormData();
    form.append('type', '<?= htmlspecialchars($type, ENT_QUOTES) ?>');
    form.append('id', '<?= (int) $id ?>');
    form.append('signature', canvas.toDataURL('image/png'));

    // no .php — .htaccess redirects .php and drops POST data
    fetch('<?= $base_url ?>ajax/save_digital_sign', {
        method: 'POST',
        body: form,
        credentials: 'same-origin'
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data && data.success) {
            showMsg(data.message || 'Signature saved.', false);
            setTimeout(function () { window.location.reload(); }, 800);
            return;
        }

        showMsg((data && data.message) ? data.message : 'Save failed.', true);
        btn.disabled = false;
        btn.innerHTML = 'Confirm & Save';
    })
    .catch(function () {
        showMsg('Network error. Please try again.', true);
        btn.disabled = false;
        btn.innerHTML = 'Confirm & Save';
    });
};

function showMsg(text, isError, hide) {
    var el = document.getElementById('statusMsg');

    if (hide || text === '') {
        el.style.display = 'none';
        el.innerHTML = '';
        return;
    }

    el.style.display = 'block';
    el.className = 'msg ' + (isError ? 'msg-error' : 'msg-ok');
    el.innerHTML = text;
}
</script>
<?php endif; ?>
</body>
</html>
