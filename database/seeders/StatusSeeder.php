<?php

namespace Database\Seeders;

use App\Models\PurchaseStatus;
use App\Models\RequisitionStatus;
use App\Models\StockStatus;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sets = [
            RequisitionStatus::class => [
                ['code' => 'stock_pending',  'name' => 'Awaiting Stock Check', 'color' => 'info',    'sort_order' => 1],
                ['code' => 'for_purchasing', 'name' => 'For Procurement',      'color' => 'warning', 'sort_order' => 2],
                ['code' => 'received',       'name' => 'Received',             'color' => 'success', 'sort_order' => 3],
                ['code' => 'returned',  'name' => 'Returned for Revision', 'color' => 'danger', 'sort_order' => 4],
                ['code' => 'cancelled', 'name' => 'Cancelled',             'color' => 'gray',   'sort_order' => 5],
            ],
            StockStatus::class => [
                ['code' => 'unchecked',    'name' => 'Not checked',  'color' => 'gray',    'sort_order' => 1],
                ['code' => 'available',    'name' => 'Available',    'color' => 'success', 'sort_order' => 2],
                ['code' => 'low_stock',    'name' => 'Low Stock',    'color' => 'warning', 'sort_order' => 3],
                ['code' => 'out_of_stock', 'name' => 'Out of Stock', 'color' => 'danger',  'sort_order' => 4],
            ],
            PurchaseStatus::class => [
                ['code' => 'pending',  'name' => 'Pending',  'color' => 'gray',    'sort_order' => 1],
                ['code' => 'ordered',  'name' => 'Ordered',  'color' => 'warning', 'sort_order' => 2],
                ['code' => 'received', 'name' => 'Received', 'color' => 'success', 'sort_order' => 3],
            ],
        ];

        foreach ($sets as $model => $rows) {
            foreach ($rows as $row) {
                $model::firstOrCreate(['code' => $row['code']], $row);
            }
        }
    }
}
