<?php
/**
 * BaseRef — Standalone Unit & Calculation Tests
 *
 * Can be run via CLI without external dependencies:
 *   php tests/run_tests.php
 *
 * Tests:
 * 1. Cohen's Kappa calculation logic matching api/kappa.php
 * 2. PRISMA 2020 Flow counting and reason categorization logic (lib/PrismaReasons.php)
 * 3. Eligibility agreement scope used by api/kappa.php
 * 4. Dashboard counters with PRISMA 2020 terms (pages/dashboard.php)
 * 5. Optional gtranslate widget is off by default (config/ui.php)
 */

$totalTests = 0;
$passedTests = 0;

function assertCondition(bool $condition, string $testName): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] $testName\n";
    } else {
        echo "  [FAIL] $testName\n";
    }
}

function assertEquals(mixed $expected, mixed $actual, string $testName, float $tolerance = 0.0001): void {
    if (is_numeric($expected) && is_numeric($actual)) {
        assertCondition(abs((float)$expected - (float)$actual) <= $tolerance, "$testName (expected: $expected, got: $actual)");
    } else {
        assertCondition($expected === $actual, "$testName (expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")");
    }
}

echo "Running BaseRef Verification Tests...\n\n";

// =========================================================================
// Suite 1: Cohen's Kappa calculation
// =========================================================================
echo "1. Cohen's Kappa Calculations:\n";

function computeCohenKappa(array $ratingsA, array $ratingsB): array {
    $joint = array_intersect_key($ratingsA, $ratingsB);
    $nJoint = count($joint);
    if ($nJoint === 0) {
        return ['kappa' => null, 'po' => null, 'n' => 0];
    }

    $cats = ['include', 'exclude', 'uncertain'];
    $table = array_fill_keys($cats, array_fill_keys($cats, 0));
    foreach ($joint as $id => $decA) {
        $decB = $ratingsB[$id];
        $table[$decA][$decB]++;
    }

    $po = 0;
    $margA = array_fill_keys($cats, 0);
    $margB = array_fill_keys($cats, 0);
    foreach ($cats as $c1) {
        $po += $table[$c1][$c1];
        foreach ($cats as $c2) {
            $margA[$c1] += $table[$c1][$c2];
            $margB[$c2] += $table[$c1][$c2];
        }
    }

    $po /= $nJoint;
    $pe = 0;
    foreach ($cats as $c) {
        $pe += ($margA[$c] / $nJoint) * ($margB[$c] / $nJoint);
    }

    $kappa = ($pe < 1) ? (($po - $pe) / (1 - $pe)) : 1.0;

    return [
        'kappa' => $kappa,
        'po' => $po,
        'n' => $nJoint,
        'table' => $table
    ];
}

// Test 1.1: Perfect Agreement
$rev1 = [1 => 'include', 2 => 'exclude', 3 => 'include'];
$rev2 = [1 => 'include', 2 => 'exclude', 3 => 'include'];
$res1 = computeCohenKappa($rev1, $rev2);
assertEquals(1.0, $res1['kappa'], "Perfect agreement gives kappa = 1.0");

// Test 1.2: Synthetic screening sample (240 articles, 97.5% agreement, expected kappa ~ 0.929)
$revA = [];
$revB = [];
for ($i = 1; $i <= 55; $i++) { $revA[$i] = 'include'; }
for ($i = 56; $i <= 240; $i++) { $revA[$i] = 'exclude'; }

// RevB matches except 3 include->exclude and 3 exclude->include
$revB = $revA;
$revB[1] = 'exclude'; $revB[2] = 'exclude'; $revB[3] = 'exclude';
$revB[56] = 'include'; $revB[57] = 'include'; $revB[58] = 'include';

$res2 = computeCohenKappa($revA, $revB);
assertEquals(0.975, $res2['po'], "Screening observed agreement is 97.5%");
assertEquals(0.9292, $res2['kappa'], "Screening kappa is ~0.929", 0.001);

// Test 1.3: Synthetic eligibility matrix on 45 reports (checks the formula, not study data):
// 29 include/include, 3 include/exclude (R1/R2), 0 exclude/include, 13 exclude/exclude
$elA = []; $elB = [];
for ($i = 1; $i <= 32; $i++) { $elA[$i] = 'include'; }          // R1 includes 32 ...
for ($i = 33; $i <= 45; $i++) { $elA[$i] = 'exclude'; }         // ... and excludes 13
$elB = $elA;
$elB[1] = 'exclude'; $elB[2] = 'exclude'; $elB[3] = 'exclude';  // R2 excludes 3 of R1's 32 inclusions

$res3 = computeCohenKappa($elA, $elB);
assertEquals(45, $res3['n'], "Eligibility evaluated in common is 45 reports");
assertEquals(29, $res3['table']['include']['include'], "Eligibility matrix: include/include = 29");
assertEquals(3,  $res3['table']['include']['exclude'], "Eligibility matrix: include/exclude = 3");
assertEquals(0,  $res3['table']['exclude']['include'], "Eligibility matrix: exclude/include = 0");
assertEquals(13, $res3['table']['exclude']['exclude'], "Eligibility matrix: exclude/exclude = 13");
assertEquals(0.9333, $res3['po'], "Eligibility observed agreement is 93.33%", 0.0001);
assertEquals(0.848, round($res3['kappa'], 3), "Eligibility rounded kappa is exactly 0.848");

// =========================================================================
// Suite 2: PRISMA Reason categorization & aggregation
// =========================================================================
echo "\n2. PRISMA Reason Categorization:\n";
require_once __DIR__ . '/../lib/PrismaReasons.php';

assertCondition(isNotRetrievedReason("Texto completo indisponível", null), "Identifies 'Texto completo indisponível' as not retrieved");
assertCondition(isNotRetrievedReason("Full-text not available", null), "Identifies English full-text unavailable");
assertCondition(isNotRetrievedReason("Report not retrieved", null), "Identifies report not retrieved");
assertCondition(!isNotRetrievedReason("Metodologia inadequada", null), "Normal exclusion reason is NOT marked as not retrieved");
assertCondition(!isNotRetrievedReason("Não responde à questão de pesquisa", null), "Irrelevant content is NOT marked as not retrieved");

// Explicit flags override text pattern
assertCondition(isNotRetrievedReason("Metodologia inadequada", 1), "Explicit flag 1 forces not retrieved");
assertCondition(!isNotRetrievedReason("Texto completo indisponível", 0), "Explicit flag 0 forces normal exclusion");

// =========================================================================
// Suite 3: Eligibility scope (real code in lib/PrismaReasons.php, used by api/kappa.php)
// =========================================================================
echo "\n3. Eligibility agreement scope (reports assessed in full text):\n";

// 57 articles decided by both reviewers: 55 went through the owner's screening (include), 2 did not (screening divergence).
// Of the 55, 10 have the reason "Texto completo indisponível" (not retrieved) and 45 were actually assessed.
$joint = range(1, 57);
$refScreening = [];
$refElig = [];
for ($i = 1; $i <= 55; $i++) { $refScreening[$i] = 'include'; }
$refScreening[56] = 'exclude'; $refScreening[57] = 'exclude';
for ($i = 1; $i <= 32; $i++)  { $refElig[$i] = ['decision' => 'include', 'reason' => null, 'not_retrieved' => null]; }
for ($i = 33; $i <= 42; $i++) { $refElig[$i] = ['decision' => 'exclude', 'reason' => 'Texto completo indisponível', 'not_retrieved' => null]; }
for ($i = 43; $i <= 55; $i++) { $refElig[$i] = ['decision' => 'exclude', 'reason' => 'Não responde à questão de pesquisa', 'not_retrieved' => null]; }
$refElig[56] = ['decision' => 'exclude', 'reason' => 'Metodologia inadequada', 'not_retrieved' => null];
$refElig[57] = ['decision' => 'exclude', 'reason' => 'Metodologia inadequada', 'not_retrieved' => null];

$beforeScreening = md5(serialize($refScreening)); $beforeElig = md5(serialize($refElig));
$scope = eligibilityReportsAssessed($joint, $refScreening, $refElig);
assertEquals(45, count($scope['ids']), "Scope keeps 45 reports assessed (57 decided by both)");
assertEquals(10, $scope['not_retrieved'], "Scope leaves out 10 reports not retrieved");
assertEquals(2, $scope['screening_divergence'], "Scope leaves out 2 records from screening divergence");
assertCondition(md5(serialize($refScreening)) === $beforeScreening && md5(serialize($refElig)) === $beforeElig,
    "Decisions are only read, never changed");

// Kappa on exactly that scope (second reviewer excludes 3 of the 32 inclusions)
$ratingsA = []; $ratingsB = [];
foreach ($scope['ids'] as $id) { $ratingsA[$id] = $refElig[$id]['decision']; }
$ratingsB = $ratingsA; $ratingsB[1] = 'exclude'; $ratingsB[2] = 'exclude'; $ratingsB[3] = 'exclude';
$resScope = computeCohenKappa($ratingsA, $ratingsB);
assertEquals(45, $resScope['n'], "Kappa computed on the 45 reports of the scope");
assertEquals(0.848, round($resScope['kappa'], 3), "Kappa on the scope is 0.848");

// An explicit not_retrieved flag also counts, and a normal exclusion with flag 0 stays in the scope
$s2 = eligibilityReportsAssessed([1, 2], [1 => 'include', 2 => 'include'], [
    1 => ['decision' => 'exclude', 'reason' => 'Outro motivo', 'not_retrieved' => 1],
    2 => ['decision' => 'exclude', 'reason' => 'Texto completo indisponível', 'not_retrieved' => 0],
]);
assertEquals(1, count($s2['ids']), "Flag 1 leaves out; flag 0 keeps in the scope");

// =========================================================================
// Suite 4: Dashboard counters (real code in lib/PrismaReasons.php, used by pages/dashboard.php)
// =========================================================================
echo "\n4. Dashboard counters (PRISMA 2020 terms):\n";

// 240 records screened: 185 excluded, 55 sought; of the 55, 10 not retrieved, 13 excluded and 32 included
$ids = range(1, 240); $dec = [];
for ($i = 1; $i <= 185; $i++)   { $dec[$i] = ['screening' => 'exclude']; }
for ($i = 186; $i <= 195; $i++) { $dec[$i] = ['screening' => 'include', 'eligibility' => 'exclude', 'not_retrieved' => true]; }
for ($i = 196; $i <= 208; $i++) { $dec[$i] = ['screening' => 'include', 'eligibility' => 'exclude', 'not_retrieved' => false]; }
for ($i = 209; $i <= 240; $i++) { $dec[$i] = ['screening' => 'include', 'eligibility' => 'include', 'not_retrieved' => false]; }
$c = prismaDashboardCounts($ids, $dec);
assertEquals(240, $c['screened'], "Records screened = 240");
assertEquals(55, $c['sought'], "Reports sought for retrieval = 55");
assertEquals(10, $c['not_retrieved'], "Reports not retrieved = 10 (separate counter)");
assertEquals(45, $c['elig_assessed'], "Reports assessed for eligibility = 45");
assertEquals(32, $c['included'], "Included = 32");
assertEquals(198, $c['excluded'], "Excluded = 185 + 13, without the 10 not retrieved");
assertEquals(0, $c['pending'], "No record pending");

// Pending, uncertain and sought without a full-text decision
$c2 = prismaDashboardCounts([1, 2, 3], [2 => ['screening' => 'uncertain'], 3 => ['screening' => 'include']]);
assertCondition($c2['pending'] === 1 && $c2['screened'] === 2 && $c2['sought'] === 1 && $c2['elig_assessed'] === 0 && $c2['excluded'] === 0,
    "Pending, uncertain and sought-without-decision are counted correctly");

// =========================================================================
// Suite 5: gtranslate widget is optional and off by default (config/ui.php)
// =========================================================================
echo "\n5. Optional gtranslate widget:\n";

$ui = __DIR__ . '/../config/ui.php';
$probe = function (?string $value) use ($ui): string {
    $env = $value === null ? 'env -u GTRANSLATE_ENABLED ' : 'env GTRANSLATE_ENABLED=' . escapeshellarg($value) . ' ';
    $code = 'require ' . var_export($ui, true) . '; echo GTRANSLATE_ENABLED ? "on" : "off";';
    return trim((string) shell_exec($env . escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code)));
};
if (is_file(__DIR__ . '/../config/ui.local.php')) {
    echo "  [SKIP] config/ui.local.php exists in this checkout; default not tested\n";
} else {
    assertEquals('off', $probe(null), "Off when GTRANSLATE_ENABLED is not set");
    assertEquals('off', $probe('false'), "Off with GTRANSLATE_ENABLED=false");
    assertEquals('on', $probe('true'), "On with GTRANSLATE_ENABLED=true");
}
$index = (string) file_get_contents(__DIR__ . '/../index.php');
$pos = strpos($index, 'cdn.gtranslate.net');
$guard = strrpos(substr($index, 0, (int) $pos), 'if (GTRANSLATE_ENABLED)');
assertCondition($pos !== false && $guard !== false && strpos(substr($index, $guard, $pos - $guard), 'endif') === false,
    "index.php loads the gtranslate script only inside if (GTRANSLATE_ENABLED)");

echo "\n--------------------------------------------------\n";
echo "Summary: $passedTests / $totalTests tests passed.\n";

if ($passedTests === $totalTests) {
    echo "Result: SUCCESS\n";
    exit(0);
} else {
    echo "Result: FAILURE\n";
    exit(1);
}
