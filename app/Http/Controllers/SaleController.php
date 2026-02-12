<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        $sales = DB::table('sales')
            ->leftJoin('users', 'sales.cashier_id', '=', 'users.id')
            ->leftJoin('sale_items as si', 'sales.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'si.product_id', '=', 'p.id')
            ->select(
                'sales.id',
                'sales.buyer_name',
                'sales.total_amount',
                'sales.created_at',
                'users.name as cashier_name',
                DB::raw('coalesce(sum(si.qty), 0) as total_qty'),
                DB::raw("group_concat(distinct coalesce(p.name, 'Produk') order by p.name separator ', ') as items_summary"),
                DB::raw('coalesce(sum((si.price - coalesce(p.modal, 0)) * si.qty), 0) as profit_amount')
            )
            ->groupBy(
                'sales.id',
                'sales.buyer_name',
                'sales.total_amount',
                'sales.created_at',
                'users.name'
            )
            ->orderByDesc('sales.created_at')
            ->get();

        return view('sales.index', [
            'sales' => $sales,
        ]);
    }

    public function create()
    {
        $products = DB::table('products')
            ->select('id', 'name', 'price', 'stock', 'unit')
            ->orderBy('name')
            ->get();

        $members = DB::table('users')
            ->select('name')
            ->where('role', '!=', 'superadmin')
            ->orderBy('name')
            ->get();

        return view('sales.create', [
            'products' => $products,
            'members' => $members,
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'buyer_name' => 'nullable|string|max:150',
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $now = now();
            $itemsData = [];
            $total = 0;

            foreach ($payload['items'] as $item) {
                $product = DB::table('products')
                    ->where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new \Exception('Produk tidak ditemukan.');
                }

                if ($product->stock < $item['qty']) {
                    throw new \Exception('Stok produk ' . $product->name . ' tidak mencukupi.');
                }

                $subtotal = $product->price * $item['qty'];
                $total += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'stock' => $product->stock - $item['qty'],
                        'updated_at' => $now,
                    ]);
            }

            $saleId = DB::table('sales')->insertGetId([
                'buyer_name' => $payload['buyer_name'] ?? null,
                'total_amount' => $total,
                'cashier_id' => $request->session()->get('auth.id'),
                'note' => $payload['note'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($itemsData as &$itemData) {
                $itemData['sale_id'] = $saleId;
            }
            unset($itemData);

            DB::table('sale_items')->insert($itemsData);

            DB::table('mart_cash_entries')->insert([
                'entry_date' => $now->toDateString(),
                'direction' => 'in',
                'description' => 'Penjualan (' . ($payload['buyer_name'] ?? 'Umum') . ')',
                'amount' => $total,
                'category' => 'penjualan',
                'status' => 'approved',
                'created_by' => $request->session()->get('auth.id'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()
                ->withErrors(['items' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('sales.index')
            ->with('success', 'Transaksi penjualan berhasil disimpan.');
    }
}
