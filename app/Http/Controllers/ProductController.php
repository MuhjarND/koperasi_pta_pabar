<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = DB::table('products')
            ->select(
                'products.*',
                DB::raw('(select coalesce(sum(qty),0) from sale_items where sale_items.product_id = products.id) as sold_qty')
            )
            ->orderBy('name')
            ->get();

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'nullable|string|max:40',
            'unit' => 'nullable|string|max:30',
            'price' => 'required|numeric|min:0',
            'modal' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        DB::table('products')->insert([
            'name' => $payload['name'],
            'sku' => $payload['sku'] ?? null,
            'unit' => $payload['unit'] ?? 'pcs',
            'price' => $payload['price'],
            'modal' => $payload['modal'],
            'stock' => $payload['stock'],
            'description' => $payload['description'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            return redirect()->route('products.index');
        }

        return view('products.edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $payload = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'nullable|string|max:40',
            'unit' => 'nullable|string|max:30',
            'price' => 'required|numeric|min:0',
            'modal' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        DB::table('products')
            ->where('id', $id)
            ->update([
                'name' => $payload['name'],
                'sku' => $payload['sku'] ?? null,
                'unit' => $payload['unit'] ?? 'pcs',
                'price' => $payload['price'],
                'modal' => $payload['modal'],
                'stock' => $payload['stock'],
                'description' => $payload['description'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function report(Request $request)
    {
        $products = DB::table('products')
            ->leftJoin(DB::raw('(select product_id, sum(qty) as sold_qty from sale_items group by product_id) as sales'), 'products.id', '=', 'sales.product_id')
            ->select('products.*', DB::raw('coalesce(sales.sold_qty,0) as sold_qty'))
            ->orderBy('products.name')
            ->get();

        $rows = [];
        $totals = [
            'total_modal' => 0,
            'hpp' => 0,
            'revenue' => 0,
            'profit' => 0,
            'stock_value' => 0,
        ];

        foreach ($products as $product) {
            $modal = (float) ($product->modal ?? 0);
            $price = (float) ($product->price ?? 0);
            $stock = (int) ($product->stock ?? 0);
            $sold = (int) ($product->sold_qty ?? 0);
            $totalQty = $stock + $sold;
            $totalModal = $modal * $totalQty;
            $hpp = $modal * $sold;
            $revenue = $price * $sold;
            $profit = $revenue - $hpp;
            $stockValue = $modal * $stock;

            $totals['total_modal'] += $totalModal;
            $totals['hpp'] += $hpp;
            $totals['revenue'] += $revenue;
            $totals['profit'] += $profit;
            $totals['stock_value'] += $stockValue;

            $rows[] = [
                'name' => $product->name,
                'total_qty' => $totalQty,
                'modal' => $modal,
                'total_modal' => $totalModal,
                'sold' => $sold,
                'hpp' => $hpp,
                'price' => $price,
                'revenue' => $revenue,
                'profit' => $profit,
                'stock' => $stock,
                'stock_value' => $stockValue,
            ];
        }

        $cashierId = $request->session()->get('auth.id');
        $cashierName = DB::table('users')->where('id', $cashierId)->value('name');
        $printedAt = now()->locale('id')->translatedFormat('d F Y, H:i');
        $qrPayload = [
            'type' => 'laporan_koperasi_mart',
            'cashier' => $cashierName ?? '-',
            'ts' => now()->timestamp,
        ];
        $qrImage = $this->buildReportQr($qrPayload);

        $pdf = PDF::loadView('products.report', [
            'rows' => $rows,
            'totals' => $totals,
            'printedAt' => $printedAt,
            'cashierName' => $cashierName ?? '-',
            'qrImage' => $qrImage,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-koperasi-mart.pdf');
    }

    public function verifyReport(Request $request)
    {
        $payloadData = null;
        $payload = $request->query('payload');
        if ($payload) {
            $decoded = base64_decode($payload, true);
            if ($decoded) {
                $json = json_decode($decoded, true);
                if (is_array($json)) {
                    $payloadData = $json;
                }
            }
        }

        $signedAt = null;
        if (!empty($payloadData['ts'])) {
            try {
                $signedAt = Carbon::createFromTimestamp((int) $payloadData['ts'])
                    ->locale('id')
                    ->translatedFormat('d F Y, H:i');
            } catch (\Exception $e) {
                $signedAt = null;
            }
        }

        return view('products.verify', [
            'payload' => $payloadData,
            'signedAt' => $signedAt,
        ]);
    }

    private function buildReportQr(array $payload)
    {
        $encodedPayload = base64_encode(json_encode($payload));
        $verifyUrl = route('products.report.verify') . '?payload=' . urlencode($encodedPayload);

        $folder = 'qr';
        $publicFolder = public_path($folder);
        if (!is_dir($publicFolder)) {
            mkdir($publicFolder, 0755, true);
        }

        $fileName = 'qr_report_' . md5($encodedPayload) . '.png';
        $filePath = $publicFolder . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($filePath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($filePath));
        }

        $qrBinary = $this->downloadQrImage($verifyUrl);
        if (!$qrBinary) {
            return null;
        }

        file_put_contents($filePath, $qrBinary);

        $logoPath = public_path('logo_koperasi.png');
        if (is_file($logoPath) && function_exists('imagecreatefromstring')) {
            $qrImage = imagecreatefromstring($qrBinary);
            $logoImage = imagecreatefromstring(file_get_contents($logoPath));
            if ($qrImage && $logoImage) {
                $qrWidth = imagesx($qrImage);
                $qrHeight = imagesy($qrImage);
                $logoSize = (int) ($qrWidth * 0.22);
                $logoX = (int) (($qrWidth - $logoSize) / 2);
                $logoY = (int) (($qrHeight - $logoSize) / 2);
                imagealphablending($qrImage, true);
                imagesavealpha($qrImage, true);
                imagecopyresampled(
                    $qrImage,
                    $logoImage,
                    $logoX,
                    $logoY,
                    0,
                    0,
                    $logoSize,
                    $logoSize,
                    imagesx($logoImage),
                    imagesy($logoImage)
                );
                imagepng($qrImage, $filePath);
                imagedestroy($qrImage);
                imagedestroy($logoImage);
            }
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($filePath));
    }

    private function downloadQrImage(string $payload)
    {
        $google = 'https://chart.googleapis.com/chart?chs=260x260&cht=qr&chl=' . urlencode($payload);
        $fallback = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($payload);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
            ],
        ]);

        $data = @file_get_contents($google, false, $context);
        if ($data === false || strlen($data) < 100) {
            $data = @file_get_contents($fallback, false, $context);
        }

        if (($data === false || strlen($data) < 100) && function_exists('curl_init')) {
            $data = $this->curlFetch($google);
            if ($data === false || strlen($data) < 100) {
                $data = $this->curlFetch($fallback);
            }
        }

        return ($data && strlen($data) > 100) ? $data : null;
    }

    private function curlFetch(string $url)
    {
        $ch = curl_init($url);
        if (!$ch) {
            return false;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
