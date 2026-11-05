<?php

namespace App\Models\Grn;

use CodeIgniter\Model;

class StockSummaryModel extends Model
{
    protected $table = 'stock';

    /**
     * Full stock ledger (GRN + usage note), with a running balance per item.
     * If a startdate is given, an "Opening Balance" row is prepended per item summing
     * everything before it, so the running balance shown for the filtered range still reconciles.
     *
     * @param array $filters ['stock_card' (optional), 'startdate' (optional), 'enddate' (optional)]
     * @return array
     */
    public function getLedger(array $filters): array
    {
        $hasItem = !empty($filters['stock_card']);
        $hasStart = !empty($filters['startdate']);

        $openingBalances = [];
        if ($hasStart) {
            $sql = "
                SELECT s.Stock_Card, COALESCE(SUM(s.Dr_Qty - s.Cr_Qty), 0) as opening_balance
                FROM stock s
                WHERE COALESCE(s.app_date) < ?
            ";
            $binds = [$filters['startdate']];

            if ($hasItem) {
                $sql .= " AND s.Stock_Card = ?";
                $binds[] = $filters['stock_card'];
            }

            $sql .= " GROUP BY s.Stock_Card";

            foreach ($this->db->query($sql, $binds)->getResultArray() as $row) {
                $openingBalances[$row['Stock_Card']] = $row['opening_balance'];
            }
        }

        $sql = "
            SELECT
                s.ID,
                s.Stock_Card,
                COALESCE(s.tym, s.app_date, so.Date) as txn_date,
                CASE
                    WHEN s.txt LIKE 'Usage Note - Tour:%' THEN COALESCE(c.manual_id, TRIM(SUBSTRING(s.txt, LOCATE(':', s.txt) + 1)))
                    WHEN so.Ref IS NOT NULL THEN CONCAT('GRN ', COALESCE(so.GRN_no, ''))
                    ELSE '-'
                END as tour,
                COALESCE(NULLIF(s.Descrip, ''), sc.Descrip) as description,
                sc.Descrip as item_description,
                s.Dr_Qty as debit,
                s.Cr_Qty as credit
            FROM stock s
            LEFT JOIN stock_card sc ON sc.Stock_Card = s.Stock_Card
            LEFT JOIN stock_orders so ON so.Ref = s.Ref AND so.GRN_Type = 'GRN'
            LEFT JOIN consignee c ON c.tour_id COLLATE utf8mb4_general_ci = CONVERT(TRIM(SUBSTRING(s.txt, LOCATE(':', s.txt) + 1)) USING utf8mb4) COLLATE utf8mb4_general_ci
                AND s.txt LIKE 'Usage Note - Tour:%'
            WHERE 1=1
        ";

        $binds = [];

        if ($hasItem) {
            $sql .= " AND s.Stock_Card = ?";
            $binds[] = $filters['stock_card'];
        }
        if ($hasStart) {
            $sql .= " AND COALESCE(s.tym, s.app_date, so.Date) >= ?";
            $binds[] = $filters['startdate'];
        }
        if (!empty($filters['enddate'])) {
            $sql .= " AND COALESCE(s.tym, s.app_date, so.Date) <= ?";
            $binds[] = $filters['enddate'];
        }

        $sql .= " ORDER BY s.Stock_Card, COALESCE(s.app_date) IS NULL, COALESCE(s.app_date), s.ID";

        $ledgerRows = $this->db->query($sql, $binds)->getResultArray();

        $rows = [];
        $runningBalances = $openingBalances;
        $seenItems = [];

        $totals = [
            'total_debit' => 0,
            'total_credit' => 0,
            'total_opening' => 0,
        ];

        foreach ($ledgerRows as $row) {
            $code = $row['Stock_Card'];

            if (!in_array($code, $seenItems, true)) {
                $seenItems[] = $code;
                if ($hasStart) {
                    $opening = $runningBalances[$code] ?? 0;
                    $totals['total_opening'] += $opening;
                    $rows[] = [
                        'ID' => null,
                        'Stock_Card' => $code,
                        'txn_date' => null,
                        'tour' => '-',
                        'description' => $row['item_description'] . ' (Opening Balance)',
                        'debit' => null,
                        'credit' => null,
                        'running_balance' => $opening,
                        'is_opening_row' => true,
                    ];
                }
                if (!isset($runningBalances[$code])) {
                    $runningBalances[$code] = 0;
                }
            }

            $totals['total_debit'] += $row['debit'];
            $totals['total_credit'] += $row['credit'];

            $runningBalances[$code] += ($row['debit'] - $row['credit']);
            $row['running_balance'] = $runningBalances[$code];
            $row['is_opening_row'] = false;
            $rows[] = $row;
        }

        $totals['total_difference'] = $totals['total_debit'] - $totals['total_credit'];
        $totals['grand_closing_balance'] = $totals['total_opening'] + $totals['total_difference'];
        $totals['row_count'] = count($ledgerRows);
        $totals['item_count'] = count($seenItems);

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
