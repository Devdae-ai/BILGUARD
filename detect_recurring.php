<?php

const AMOUNT_TOLERANCE = 0.08;
const INTERVAL_TOLERANCE = 0.20;
const MIN_OCCURRENCES = 2;
const KNOWN_CYCLES = [7, 14, 30, 60, 90, 180, 365];

function normalize_merchant(string $raw): string {
    $key = strtolower(trim($raw));
    $key = preg_replace('/[^a-z0-9]+/', ' ', $key);
    $key = trim(preg_replace('/\s+/', ' ', $key));
    return $key;
}

function snap_to_cycle(float $avgDays): int {
    $closest = KNOWN_CYCLES[0];
    $bestDiff = abs($avgDays - $closest);
    foreach (KNOWN_CYCLES as $cycle) {
        $diff = abs($avgDays - $cycle);
        if ($diff < $bestDiff) {
            $bestDiff = $diff;
            $closest = $cycle;
        }
    }
    if ($bestDiff <= $closest * 0.3) {
        return $closest;
    }
    return (int) round($avgDays);
}

function stddev(array $nums): float {
    $n = count($nums);
    if ($n < 2) return 0.0;
    $mean = array_sum($nums) / $n;
    $sum = 0.0;
    foreach ($nums as $v) $sum += ($v - $mean) ** 2;
    return sqrt($sum / ($n - 1));
}

function cluster_by_amount(array $txns): array {
    usort($txns, fn($a, $b) => $a['amount'] <=> $b['amount']);
    $clusters = [];
    $current = [];
    $currentAvg = null;

    foreach ($txns as $t) {
        if ($currentAvg === null) {
            $current = [$t];
            $currentAvg = (float) $t['amount'];
            continue;
        }
        $tolerance = max($currentAvg * AMOUNT_TOLERANCE, 5);
        if (abs($t['amount'] - $currentAvg) <= $tolerance) {
            $current[] = $t;
            $sum = 0;
            foreach ($current as $c) $sum += $c['amount'];
            $currentAvg = $sum / count($current);
        } else {
            $clusters[] = $current;
            $current = [$t];
            $currentAvg = (float) $t['amount'];
        }
    }
    if ($current) $clusters[] = $current;
    return $clusters;
}

function detect_recurring_for_user(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare('SELECT id, merchant, merchant_key, amount, txn_date FROM transactions WHERE user_id = ? ORDER BY merchant_key, txn_date ASC');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $byMerchant = [];
    foreach ($rows as $r) {
        $byMerchant[$r['merchant_key']][] = $r;
    }

    $found = [];

    foreach ($byMerchant as $merchantKey => $txns) {
        foreach (cluster_by_amount($txns) as $cluster) {
            if (count($cluster) < MIN_OCCURRENCES) continue;

            usort($cluster, fn($a, $b) => strcmp($a['txn_date'], $b['txn_date']));
            $dates = array_map(fn($c) => new DateTime($c['txn_date']), $cluster);

            $gaps = [];
            for ($i = 1; $i < count($dates); $i++) {
                $gaps[] = (int) $dates[$i]->diff($dates[$i - 1])->days;
            }
            $gaps = array_values(array_filter($gaps, fn($g) => $g > 0));
            if (count($gaps) < 1) continue;

            $avgGap = array_sum($gaps) / count($gaps);
            if ($avgGap < 3) continue;

            $sd = stddev($gaps);
            $spreadRatio = $avgGap > 0 ? $sd / $avgGap : 1;

            if (count($gaps) === 1 || $spreadRatio <= INTERVAL_TOLERANCE) {
                $intervalDays = snap_to_cycle($avgGap);
                $amountSum = 0;
                foreach ($cluster as $c) $amountSum += $c['amount'];
                $avgAmount = round($amountSum / count($cluster), 2);
                $lastDate = end($dates);
                $nextExpected = (clone $lastDate)->modify("+{$intervalDays} days");
                $merchantName = $cluster[count($cluster) - 1]['merchant'];

                $upsert = $pdo->prepare('
                    INSERT INTO recurring_payments
                        (user_id, merchant, merchant_key, avg_amount, interval_days, occurrences, last_txn_date, next_expected_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, "active")
                    ON DUPLICATE KEY UPDATE
                        merchant = VALUES(merchant),
                        avg_amount = VALUES(avg_amount),
                        interval_days = VALUES(interval_days),
                        occurrences = VALUES(occurrences),
                        last_txn_date = VALUES(last_txn_date),
                        next_expected_date = VALUES(next_expected_date),
                        status = IF(status = "dismissed", "dismissed", "active")
                ');
                $upsert->execute([
                    $userId, $merchantName, $merchantKey, $avgAmount, $intervalDays,
                    count($cluster), $lastDate->format('Y-m-d'), $nextExpected->format('Y-m-d')
                ]);

                $found[] = [
                    'merchant' => $merchantName,
                    'avg_amount' => $avgAmount,
                    'interval_days' => $intervalDays,
                    'occurrences' => count($cluster),
                    'next_expected_date' => $nextExpected->format('Y-m-d'),
                ];
            }
        }
    }

    return $found;
}

function interval_label(int $days): string {
    return match (true) {
        $days <= 8  => 'weekly',
        $days <= 16 => 'every 2 weeks',
        $days <= 35 => 'every 30 days',
        $days <= 70 => 'every 60 days',
        $days <= 100 => 'quarterly',
        $days <= 200 => 'every 6 months',
        default => 'yearly',
    };
}