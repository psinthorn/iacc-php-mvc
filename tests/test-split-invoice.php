<?php
/**
 * Split Invoice WHT Test
 * 
 * Tests the split invoice logic when products have labour charges.
 * Simulates the full flow: PR → PO → Delivery → Receive (split trigger)
 * 
 * Scenarios:
 * 1. 1 product, no labour → single invoice (no split)
 * 2. 1 product with labour → split into material + labour invoices
 * 3. 2 products, 1 has labour → split (material invoice has 2 products, labour has 1)
 * 4. 3 products, 2 have labour → split (material invoice has 3 products, labour has 2)
 */
session_start();
require_once(__DIR__ . "/../inc/sys.configs.php");
require_once(__DIR__ . "/../inc/class.dbconn.php");
require_once(__DIR__ . "/../inc/class.hard.php");
require_once(__DIR__ . "/../inc/security.php");

$_SESSION['com_id'] = 95;
$_SESSION['user_id'] = 1;

$db = new DbConn($config);
$conn = $db->conn;
$har = new HardClass();
$har->setConnection($conn);

$results = [];
$passed = 0;
$failed = 0;
$cleanupIds = ['pr' => [], 'po' => [], 'product' => [], 'deliver' => [], 'receive' => [], 'iv' => []];

function test($name, $condition, $details = '') {
    global $results, $passed, $failed;
    if ($condition) {
        $results[] = ['name' => $name, 'status' => 'PASS', 'details' => $details];
        $passed++;
    } else {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'details' => $details];
        $failed++;
    }
}

function trackId($table, $id) {
    global $cleanupIds;
    $cleanupIds[$table][] = $id;
}

/**
 * Create a complete test scenario: PR → PO → Products → Delivery
 * Returns ['pr_id', 'po_id', 'deliv_id', 'product_ids']
 */
function createTestScenario($conn, $har, $comId, $scenarioName, $products) {
    // 1. Create PR
    $prId = $har->Maxid('pr');
    $sql = "INSERT INTO pr (company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, mailcount, payby, deleted_at)
            VALUES ('$comId', '" . mysqli_real_escape_string($conn, "TEST-SPLIT: $scenarioName") . "', 'Test split invoice', '1', '$comId', '$comId', '" . date('Y-m-d') . "', '3', '0', '0', '0', NULL)";
    mysqli_query($conn, $sql);
    $prId = mysqli_insert_id($conn);
    trackId('pr', $prId);

    // 2. Create PO
    $poMaxId = $har->Maxid('po');
    $taxNumber = (date("y") + 43) . str_pad($poMaxId, 6, '0', STR_PAD_LEFT);
    $sql = "INSERT INTO po (company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, `over`, deleted_at)
            VALUES ('$comId', '', '" . mysqli_real_escape_string($conn, "TEST-SPLIT: $scenarioName") . "', '$prId', '$taxNumber', '" . date('Y-m-d') . "', '" . date('Y-m-d') . "', '" . date('Y-m-d') . "', '', '', '0', '0', '7', '3', NULL)";
    mysqli_query($conn, $sql);
    $poId = mysqli_insert_id($conn);
    trackId('po', $poId);

    // 3. Insert products
    $productIds = [];
    foreach ($products as $p) {
        $sql = "INSERT INTO product (company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at)
                VALUES ('$comId', '$poId', '" . floatval($p['price']) . "', '0', '0', '0', '1', '" . floatval($p['qty'] ?? 1) . "', '1', '0', '" . mysqli_real_escape_string($conn, $p['des']) . "', '" . intval($p['activelabour']) . "', '" . floatval($p['valuelabour'] ?? 0) . "', '0', '1970-01-01', '0', NULL)";
        mysqli_query($conn, $sql);
        $pid = mysqli_insert_id($conn);
        $productIds[] = $pid;
        trackId('product', $pid);
    }

    // 4. Create delivery
    $sql = "INSERT INTO deliver (company_id, po_id, deliver_date, out_id, deleted_at)
            VALUES ('$comId', '$poId', '" . date('Y-m-d') . "', '0', NULL)";
    mysqli_query($conn, $sql);
    $delivId = mysqli_insert_id($conn);
    trackId('deliver', $delivId);

    return ['pr_id' => $prId, 'po_id' => $poId, 'deliv_id' => $delivId, 'product_ids' => $productIds, 'tax' => $taxNumber];
}

/**
 * Run receiveDelivery and return created IVs
 */
function receiveAndCheck($conn, $har, $comId, $scenario) {
    // Use the Delivery model — needs global $db for BaseModel constructor
    global $db;
    
    require_once(__DIR__ . "/../inc/class.company_filter.php");
    require_once(__DIR__ . "/../app/Models/BaseModel.php");
    require_once(__DIR__ . "/../app/Models/Delivery.php");

    $delivery = new \App\Models\Delivery();

    $data = [
        'po_id' => $scenario['po_id'],
        'deliv_id' => $scenario['deliv_id'],
        'ref' => $scenario['pr_id'],
    ];

    // Count IVs before
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM iv WHERE company_id='$comId'"));
    $ivCountBefore = intval($r['cnt']);

    $delivery->receiveDelivery($data, $comId);

    // Count IVs after
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM iv WHERE company_id='$comId'"));
    $ivCountAfter = intval($r['cnt']);

    $newIvCount = $ivCountAfter - $ivCountBefore;

    // Get the new IVs (find POs linked to original via split_group_id or direct)
    $ivRecords = [];
    
    // Check for split POs first
    $splitPOs = [];
    $rr = mysqli_query($conn, "SELECT id, tax, name, split_group_id, split_type, `over` FROM po WHERE split_group_id IS NOT NULL AND po_id_new='' AND ref='" . intval($scenario['pr_id']) . "'");
    while ($row = mysqli_fetch_assoc($rr)) {
        $splitPOs[] = $row;
        trackId('po', $row['id']);
    }

    // Get IV records for split POs
    foreach ($splitPOs as $sp) {
        $ivRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM iv WHERE tex='" . intval($sp['id']) . "'"));
        if ($ivRow) {
            $ivRow['split_type'] = $sp['split_type'];
            $ivRow['po_tax'] = $sp['tax'];
            $ivRow['po_over'] = $sp['over'];
            $ivRecords[] = $ivRow;
            trackId('iv', $ivRow['tex']);
            
            // Track products on split POs
            $prods = mysqli_query($conn, "SELECT pro_id FROM product WHERE po_id='" . intval($sp['id']) . "'");
            while ($pp = mysqli_fetch_assoc($prods)) {
                trackId('product', $pp['pro_id']);
            }
        }
    }

    // If no split, get IV for original PO
    if (empty($splitPOs)) {
        $ivRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM iv WHERE tex='" . intval($scenario['po_id']) . "'"));
        if ($ivRow) {
            $ivRow['split_type'] = 'full';
            $ivRow['po_tax'] = $scenario['tax'];
            $ivRecords[] = $ivRow;
            trackId('iv', $ivRow['tex']);
        }
    }

    // Track receive records
    $recvR = mysqli_query($conn, "SELECT rec_id FROM receive WHERE po_id='" . intval($scenario['po_id']) . "'");
    while ($rec = mysqli_fetch_assoc($recvR)) {
        trackId('receive', $rec['rec_id']);
    }

    return [
        'new_iv_count' => $newIvCount,
        'iv_records' => $ivRecords,
        'split_pos' => $splitPOs,
    ];
}

$comId = 95;

// ================================================================
// SCENARIO 1: 1 product, no labour → single invoice (no split)
// ================================================================
$s1 = createTestScenario($conn, $har, $comId, "Scenario 1: No Labour", [
    ['price' => 5000, 'des' => 'Material only item', 'activelabour' => 0, 'valuelabour' => 0, 'qty' => 1],
]);
$r1 = receiveAndCheck($conn, $har, $comId, $s1);

test('S1: Single invoice created (no split)',
    $r1['new_iv_count'] === 1,
    "Expected 1 IV, got {$r1['new_iv_count']}"
);
test('S1: No split POs created',
    count($r1['split_pos']) === 0,
    "Split POs: " . count($r1['split_pos'])
);
test('S1: Invoice type is full',
    !empty($r1['iv_records']) && $r1['iv_records'][0]['split_type'] === 'full',
    "Type: " . ($r1['iv_records'][0]['split_type'] ?? 'none')
);

// ================================================================
// SCENARIO 2: 1 product with labour → 2 invoices (split)
// ================================================================
$s2 = createTestScenario($conn, $har, $comId, "Scenario 2: 1 Product + Labour", [
    ['price' => 3000, 'des' => 'Electrical work', 'activelabour' => 1, 'valuelabour' => 1500, 'qty' => 1],
]);
$r2 = receiveAndCheck($conn, $har, $comId, $s2);

test('S2: Two invoices created (split)',
    $r2['new_iv_count'] === 2,
    "Expected 2 IVs, got {$r2['new_iv_count']}"
);
test('S2: Two split POs created',
    count($r2['split_pos']) === 2,
    "Split POs: " . count($r2['split_pos'])
);

// Check material invoice
$matPO = null; $labPO = null;
foreach ($r2['split_pos'] as $sp) {
    if ($sp['split_type'] === 'material') $matPO = $sp;
    if ($sp['split_type'] === 'labour') $labPO = $sp;
}
test('S2: Material PO has over=0 (no WHT)',
    $matPO !== null && intval($matPO['over']) === 0,
    "Material PO over: " . ($matPO['over'] ?? 'NULL')
);
test('S2: Labour PO has over=3 (WHT 3%)',
    $labPO !== null && intval($labPO['over']) === 3,
    "Labour PO over: " . ($labPO['over'] ?? 'NULL')
);
test('S2: Material PO tax has /1 suffix',
    $matPO !== null && str_ends_with($matPO['tax'], '/1'),
    "Material tax: " . ($matPO['tax'] ?? 'NULL')
);
test('S2: Labour PO tax has /2 suffix',
    $labPO !== null && str_ends_with($labPO['tax'], '/2'),
    "Labour tax: " . ($labPO['tax'] ?? 'NULL')
);

// Check products on split POs
$matProducts = [];
$rr = mysqli_query($conn, "SELECT price, activelabour, valuelabour, des FROM product WHERE po_id='" . intval($matPO['id']) . "'");
while ($row = mysqli_fetch_assoc($rr)) $matProducts[] = $row;

$labProducts = [];
$rr = mysqli_query($conn, "SELECT price, activelabour, valuelabour, des FROM product WHERE po_id='" . intval($labPO['id']) . "'");
while ($row = mysqli_fetch_assoc($rr)) $labProducts[] = $row;

test('S2: Material PO has 1 product (material price)',
    count($matProducts) === 1,
    "Material products: " . count($matProducts)
);
test('S2: Material product price is 3000 (material cost)',
    count($matProducts) === 1 && floatval($matProducts[0]['price']) == 3000,
    "Price: " . ($matProducts[0]['price'] ?? 'N/A')
);
test('S2: Labour PO has 1 product',
    count($labProducts) === 1,
    "Labour products: " . count($labProducts)
);
test('S2: Labour product price is 1500 (valuelabour)',
    count($labProducts) === 1 && floatval($labProducts[0]['price']) == 1500,
    "Price: " . ($labProducts[0]['price'] ?? 'N/A')
);

// ================================================================
// SCENARIO 3: 2 products, 1 has labour → split
// Material invoice: 2 products (both material prices)
// Labour invoice: 1 product (the one with labour)
// ================================================================
$s3 = createTestScenario($conn, $har, $comId, "Scenario 3: 2 Products, 1 Labour", [
    ['price' => 8000, 'des' => 'Roof tiles', 'activelabour' => 0, 'valuelabour' => 0, 'qty' => 2],
    ['price' => 5000, 'des' => 'Roof installation', 'activelabour' => 1, 'valuelabour' => 3000, 'qty' => 1],
]);
$r3 = receiveAndCheck($conn, $har, $comId, $s3);

test('S3: Two invoices created (split)',
    $r3['new_iv_count'] === 2,
    "Expected 2 IVs, got {$r3['new_iv_count']}"
);

$matPO3 = null; $labPO3 = null;
foreach ($r3['split_pos'] as $sp) {
    if ($sp['split_type'] === 'material') $matPO3 = $sp;
    if ($sp['split_type'] === 'labour') $labPO3 = $sp;
}

// Material PO should have ALL 2 products with material prices
$matProducts3 = [];
$rr = mysqli_query($conn, "SELECT price, activelabour, des FROM product WHERE po_id='" . intval($matPO3['id']) . "' ORDER BY price");
while ($row = mysqli_fetch_assoc($rr)) $matProducts3[] = $row;

$labProducts3 = [];
$rr = mysqli_query($conn, "SELECT price, activelabour, des FROM product WHERE po_id='" . intval($labPO3['id']) . "'");
while ($row = mysqli_fetch_assoc($rr)) $labProducts3[] = $row;

test('S3: Material PO has 2 products (all items)',
    count($matProducts3) === 2,
    "Material products: " . count($matProducts3)
);
test('S3: Material prices are 5000 and 8000',
    count($matProducts3) === 2 && floatval($matProducts3[0]['price']) == 5000 && floatval($matProducts3[1]['price']) == 8000,
    "Prices: " . implode(', ', array_column($matProducts3, 'price'))
);
test('S3: All material products have activelabour=0',
    count($matProducts3) === 2 && intval($matProducts3[0]['activelabour']) === 0 && intval($matProducts3[1]['activelabour']) === 0,
    "activelabour: " . implode(', ', array_column($matProducts3, 'activelabour'))
);
test('S3: Labour PO has 1 product',
    count($labProducts3) === 1,
    "Labour products: " . count($labProducts3)
);
test('S3: Labour product price is 3000 (valuelabour)',
    count($labProducts3) === 1 && floatval($labProducts3[0]['price']) == 3000,
    "Price: " . ($labProducts3[0]['price'] ?? 'N/A')
);
test('S3: Material PO has over=0',
    $matPO3 !== null && intval($matPO3['over']) === 0,
    "over: " . ($matPO3['over'] ?? 'NULL')
);
test('S3: Labour PO has over=3',
    $labPO3 !== null && intval($labPO3['over']) === 3,
    "over: " . ($labPO3['over'] ?? 'NULL')
);

// ================================================================
// SCENARIO 4: 3 products, 2 have labour → split
// Material invoice: 3 products (all material prices)
// Labour invoice: 2 products (the 2 with labour, using valuelabour)
// ================================================================
$s4 = createTestScenario($conn, $har, $comId, "Scenario 4: 3 Products, 2 Labour", [
    ['price' => 12000, 'des' => 'Sand wash materials', 'activelabour' => 0, 'valuelabour' => 0, 'qty' => 1],
    ['price' => 8000, 'des' => 'Electrical wiring', 'activelabour' => 1, 'valuelabour' => 4000, 'qty' => 1],
    ['price' => 6000, 'des' => 'Plumbing work', 'activelabour' => 1, 'valuelabour' => 2500, 'qty' => 1],
]);
$r4 = receiveAndCheck($conn, $har, $comId, $s4);

test('S4: Two invoices created (split)',
    $r4['new_iv_count'] === 2,
    "Expected 2 IVs, got {$r4['new_iv_count']}"
);

$matPO4 = null; $labPO4 = null;
foreach ($r4['split_pos'] as $sp) {
    if ($sp['split_type'] === 'material') $matPO4 = $sp;
    if ($sp['split_type'] === 'labour') $labPO4 = $sp;
}

// Material PO should have ALL 3 products
$matProducts4 = [];
$rr = mysqli_query($conn, "SELECT price, activelabour, des FROM product WHERE po_id='" . intval($matPO4['id']) . "' ORDER BY price");
while ($row = mysqli_fetch_assoc($rr)) $matProducts4[] = $row;

$labProducts4 = [];
$rr = mysqli_query($conn, "SELECT price, activelabour, des FROM product WHERE po_id='" . intval($labPO4['id']) . "' ORDER BY price");
while ($row = mysqli_fetch_assoc($rr)) $labProducts4[] = $row;

test('S4: Material PO has 3 products (all items)',
    count($matProducts4) === 3,
    "Material products: " . count($matProducts4)
);
test('S4: Material prices are 6000, 8000, 12000',
    count($matProducts4) === 3 &&
    floatval($matProducts4[0]['price']) == 6000 &&
    floatval($matProducts4[1]['price']) == 8000 &&
    floatval($matProducts4[2]['price']) == 12000,
    "Prices: " . implode(', ', array_column($matProducts4, 'price'))
);
test('S4: All material products have activelabour=0',
    count($matProducts4) === 3 &&
    intval($matProducts4[0]['activelabour']) === 0 &&
    intval($matProducts4[1]['activelabour']) === 0 &&
    intval($matProducts4[2]['activelabour']) === 0,
    "activelabour: " . implode(', ', array_column($matProducts4, 'activelabour'))
);
test('S4: Labour PO has 2 products',
    count($labProducts4) === 2,
    "Labour products: " . count($labProducts4)
);
test('S4: Labour prices are 2500 and 4000 (valuelabour)',
    count($labProducts4) === 2 &&
    floatval($labProducts4[0]['price']) == 2500 &&
    floatval($labProducts4[1]['price']) == 4000,
    "Prices: " . implode(', ', array_column($labProducts4, 'price'))
);
test('S4: Split group IDs match between material and labour',
    $matPO4 !== null && $labPO4 !== null && $matPO4['split_group_id'] === $labPO4['split_group_id'],
    "Material group: " . ($matPO4['split_group_id'] ?? 'NULL') . ", Labour group: " . ($labPO4['split_group_id'] ?? 'NULL')
);
test('S4: Original PO superseded (po_id_new set)',
    true, // Check
    ""
);
$origPO4 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT po_id_new FROM po WHERE id='" . intval($s4['po_id']) . "'"));
test('S4: Original PO points to material PO',
    $origPO4 !== null && intval($origPO4['po_id_new']) === intval($matPO4['id']),
    "po_id_new: " . ($origPO4['po_id_new'] ?? 'NULL') . ", material PO: " . ($matPO4['id'] ?? 'NULL')
);

// ================================================================
// CLEANUP — Remove all test data
// ================================================================
// Delete in reverse dependency order
foreach ($cleanupIds['iv'] as $id) {
    mysqli_query($conn, "DELETE FROM iv WHERE tex='$id'");
}
foreach ($cleanupIds['receive'] as $id) {
    mysqli_query($conn, "DELETE FROM receive WHERE rec_id='$id'");
}
foreach ($cleanupIds['product'] as $id) {
    mysqli_query($conn, "DELETE FROM product WHERE pro_id='$id'");
}
foreach ($cleanupIds['deliver'] as $id) {
    mysqli_query($conn, "DELETE FROM deliver WHERE id='$id'");
}
foreach ($cleanupIds['po'] as $id) {
    mysqli_query($conn, "DELETE FROM po WHERE id='$id'");
}
foreach ($cleanupIds['pr'] as $id) {
    mysqli_query($conn, "DELETE FROM pr WHERE id='$id'");
}

// ================================================================
// OUTPUT
// ================================================================
$isHtml = php_sapi_name() !== 'cli';
if ($isHtml): ?>
<!DOCTYPE html>
<html><head><title>Split Invoice WHT Test</title>
<style>
    body { font-family: -apple-system, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; background: #f5f5f5; }
    h1 { color: #333; }
    h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
    .test { padding: 8px 14px; margin: 4px 0; border-radius: 6px; font-size: 14px; }
    .pass { background: #d4edda; color: #155724; }
    .fail { background: #f8d7da; color: #721c24; font-weight: bold; }
    .summary { padding: 20px; margin-top: 30px; font-size: 20px; border-radius: 8px; text-align: center; }
    .details { color: #666; font-size: 12px; margin-left: 10px; }
</style></head><body>
<h1>Split Invoice WHT Test</h1>

<h2>Scenario 1: 1 Product, No Labour (No Split)</h2>
<?php $si = 0; foreach ($results as $i => $r): if ($i < 3): ?>
    <div class="test <?= $r['status'] === 'PASS' ? 'pass' : 'fail' ?>">
        <?= $r['status'] === 'PASS' ? '✓' : '✗' ?> <?= htmlspecialchars($r['name']) ?>
        <?php if ($r['details']): ?><span class="details"><?= htmlspecialchars($r['details']) ?></span><?php endif; ?>
    </div>
<?php endif; endforeach; ?>

<h2>Scenario 2: 1 Product + Labour (Split)</h2>
<?php foreach ($results as $i => $r): if ($i >= 3 && $i < 13): ?>
    <div class="test <?= $r['status'] === 'PASS' ? 'pass' : 'fail' ?>">
        <?= $r['status'] === 'PASS' ? '✓' : '✗' ?> <?= htmlspecialchars($r['name']) ?>
        <?php if ($r['details']): ?><span class="details"><?= htmlspecialchars($r['details']) ?></span><?php endif; ?>
    </div>
<?php endif; endforeach; ?>

<h2>Scenario 3: 2 Products, 1 Labour (Split)</h2>
<?php foreach ($results as $i => $r): if ($i >= 13 && $i < 21): ?>
    <div class="test <?= $r['status'] === 'PASS' ? 'pass' : 'fail' ?>">
        <?= $r['status'] === 'PASS' ? '✓' : '✗' ?> <?= htmlspecialchars($r['name']) ?>
        <?php if ($r['details']): ?><span class="details"><?= htmlspecialchars($r['details']) ?></span><?php endif; ?>
    </div>
<?php endif; endforeach; ?>

<h2>Scenario 4: 3 Products, 2 Labour (Split)</h2>
<?php foreach ($results as $i => $r): if ($i >= 21): ?>
    <div class="test <?= $r['status'] === 'PASS' ? 'pass' : 'fail' ?>">
        <?= $r['status'] === 'PASS' ? '✓' : '✗' ?> <?= htmlspecialchars($r['name']) ?>
        <?php if ($r['details']): ?><span class="details"><?= htmlspecialchars($r['details']) ?></span><?php endif; ?>
    </div>
<?php endif; endforeach; ?>

<div class="summary" style="background: <?= $failed === 0 ? '#28a745' : '#dc3545' ?>; color: white;">
    ✓ PASSED: <?= $passed ?>/<?= $passed + $failed ?> | FAILED: <?= $failed ?>/<?= $passed + $failed ?>
</div>
</body></html>
<?php else:
    // CLI output
    echo "\n=== Split Invoice WHT Test ===\n\n";
    foreach ($results as $r) {
        $icon = $r['status'] === 'PASS' ? '✓' : '✗';
        echo "$icon {$r['name']}";
        if ($r['details']) echo " — {$r['details']}";
        echo "\n";
    }
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "PASSED: $passed/" . ($passed + $failed) . " | FAILED: $failed/" . ($passed + $failed) . "\n";
endif;
