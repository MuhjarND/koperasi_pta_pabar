<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SavingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/daftar/{token}', [PublicRegistrationController::class, 'show'])->name('public.register');
Route::post('/daftar/{token}', [PublicRegistrationController::class, 'store'])->name('public.register.submit');
Route::get('/verifikasi-form/{token}', [LoanController::class, 'verifyForm'])->name('loans.verify');
Route::get('/qr/verify', [LoanController::class, 'verifyForm'])->name('qr.verify');
Route::get('/produk/laporan/verify', [ProductController::class, 'verifyReport'])->name('products.report.verify');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['session.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/saldo', [BalanceController::class, 'index'])->name('saldo.index');
    Route::post('/saldo', [BalanceController::class, 'store'])
        ->middleware('role:bendahara')
        ->name('saldo.update');
    Route::post('/saldo/{id}/verify', [BalanceController::class, 'verify'])
        ->middleware('role:sekretaris|superadmin')
        ->name('saldo.verify');
    Route::get('/saldo/export', [BalanceController::class, 'export'])->name('saldo.export');

    Route::get('/simpanan', [SavingsController::class, 'index'])->name('savings.index');
    Route::post('/simpanan', [SavingsController::class, 'store'])
        ->middleware('role:bendahara')
        ->name('savings.store');
    Route::post('/simpanan/{user}/{month}', [SavingsController::class, 'updateMonth'])
        ->middleware('role:bendahara')
        ->where('month', '\d{4}-\d{2}')
        ->name('savings.updateMonth');
    Route::delete('/simpanan/{user}/{month}', [SavingsController::class, 'destroyMonth'])
        ->middleware('role:bendahara')
        ->where('month', '\d{4}-\d{2}')
        ->name('savings.destroyMonth');
    Route::post('/simpanan/arus-kas', [SavingsController::class, 'postToCash'])
        ->middleware('role:bendahara')
        ->name('savings.post');
    Route::get('/pemotongan', [DeductionController::class, 'index'])->name('deductions.index');
    Route::post('/pemotongan', [DeductionController::class, 'store'])
        ->middleware('role:bendahara')
        ->name('deductions.store');
    Route::delete('/pemotongan/{user}', [DeductionController::class, 'destroy'])
        ->middleware('role:bendahara')
        ->name('deductions.destroy');
    Route::post('/pemotongan/{id}/verify', [DeductionController::class, 'verify'])
        ->middleware('role:bendahara_kantor|superadmin')
        ->name('deductions.verify');

    Route::prefix('anggota-data')->middleware('role:superadmin|sekretaris')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('members.index');
        Route::get('/create', [MemberController::class, 'create'])->name('members.create');
        Route::post('/', [MemberController::class, 'store'])->name('members.store');
        Route::get('/{id}/edit', [MemberController::class, 'edit'])->name('members.edit');
        Route::post('/{id}', [MemberController::class, 'update'])->name('members.update');
    });

    Route::prefix('undangan-anggota')->middleware('role:sekretaris')->group(function () {
        Route::get('/', [InviteController::class, 'index'])->name('invites.index');
        Route::post('/', [InviteController::class, 'store'])->name('invites.store');
    });

    Route::prefix('users')->middleware('role:superadmin')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/{id}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::post('/{id}', [AdminUserController::class, 'update'])->name('users.update');
    });

    Route::prefix('produk')->middleware('role:sekretaris')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('/laporan', [ProductController::class, 'report'])->name('products.report');
        Route::get('/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
        Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::post('/{id}', [ProductController::class, 'update'])->name('products.update');
    });

    Route::prefix('penjualan')->middleware('role:sekretaris')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/', [SaleController::class, 'store'])->name('sales.store');
    });

    Route::prefix('laporan')->middleware('role:bendahara')->group(function () {
        Route::get('/bulanan', [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/tahunan', [ReportController::class, 'yearly'])->name('reports.yearly');
        Route::get('/shu/pdf/{section}', [ReportController::class, 'shuPdf'])->name('reports.shu.pdf');
        Route::get('/laba-rugi/pdf', [ReportController::class, 'labaRugiPdf'])->name('reports.laba-rugi.pdf');
        Route::get('/{type}', [ReportController::class, 'type'])->name('reports.type');
    });

    Route::get('/dashboard/superadmin', [DashboardController::class, 'superadmin'])
        ->middleware('role:superadmin')
        ->name('dashboard.superadmin');

    Route::get('/dashboard/sekretaris', [DashboardController::class, 'sekretaris'])
        ->middleware('role:sekretaris')
        ->name('dashboard.sekretaris');

    Route::get('/dashboard/bendahara', [DashboardController::class, 'bendahara'])
        ->middleware('role:bendahara')
        ->name('dashboard.bendahara');

    Route::get('/dashboard/ketua', [DashboardController::class, 'ketua'])
        ->middleware('role:ketua')
        ->name('dashboard.ketua');

    Route::get('/dashboard/bendahara_kantor', [DashboardController::class, 'bendaharaKantor'])
        ->middleware('role:bendahara_kantor')
        ->name('dashboard.bendahara_kantor');

    Route::get('/dashboard/anggota', [DashboardController::class, 'anggota'])
        ->middleware('role:anggota')
        ->name('dashboard.anggota');

    Route::prefix('anggota')->middleware('role:anggota')->group(function () {
        Route::get('/pinjaman', [LoanController::class, 'memberIndex'])->name('anggota.loans.index');
        Route::get('/pinjaman/create', [LoanController::class, 'memberCreate'])->name('anggota.loans.create');
        Route::post('/pinjaman', [LoanController::class, 'memberStore'])->name('anggota.loans.store');
        Route::get('/pinjaman/peserta', [LoanPaymentController::class, 'index'])->name('anggota.loans.payments');
        Route::post('/pinjaman/peserta', [LoanPaymentController::class, 'store'])->name('anggota.loans.payments.store');
    });

    Route::prefix('sekretaris')->middleware('role:sekretaris')->group(function () {
        Route::get('/pinjaman', [LoanController::class, 'sekretarisIndex'])->name('sekretaris.loans.index');
        Route::get('/pinjaman/{id}', [LoanController::class, 'sekretarisShow'])->name('sekretaris.loans.show');
        Route::post('/pinjaman/{id}/review', [LoanController::class, 'sekretarisReview'])
            ->name('sekretaris.loans.review');
    });

    Route::prefix('bendahara')->middleware('role:bendahara')->group(function () {
        Route::get('/pinjaman', [LoanController::class, 'bendaharaIndex'])->name('bendahara.loans.index');
        Route::get('/pinjaman/peserta', [LoanPaymentController::class, 'index'])->name('bendahara.loans.payments');
        Route::post('/pinjaman/peserta', [LoanPaymentController::class, 'store'])->name('bendahara.loans.payments.store');
        Route::post('/pinjaman/peserta/pelunasan/{id}/approve', [LoanPaymentController::class, 'approveSettlement'])
            ->name('bendahara.loans.payments.settlement.approve');
        Route::get('/pinjaman/{id}', [LoanController::class, 'bendaharaShow'])->name('bendahara.loans.show');
        Route::post('/pinjaman/{id}/approve', [LoanController::class, 'bendaharaApprove'])
            ->name('bendahara.loans.approve');
    });

    Route::prefix('ketua')->middleware('role:ketua')->group(function () {
        Route::get('/pinjaman', [LoanController::class, 'ketuaIndex'])->name('ketua.loans.index');
        Route::get('/pinjaman/{id}', [LoanController::class, 'ketuaShow'])->name('ketua.loans.show');
        Route::post('/pinjaman/{id}/approve', [LoanController::class, 'ketuaApprove'])
            ->name('ketua.loans.approve');
    });
});
