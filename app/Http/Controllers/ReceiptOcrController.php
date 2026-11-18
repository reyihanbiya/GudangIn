<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\Persediaan;
use App\Models\Supplier;
use App\Services\ReceiptOcrException;
use App\Services\ReceiptOcrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReceiptOcrController extends Controller
{
    public function create(): View
    {
        return view('transactions.ocr-upload');
    }

    public function process(Request $request, ReceiptOcrService $ocr): RedirectResponse
    {
        $data = $request->validate([
            'receipt' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $token = (string) Str::uuid();
        $file = $data['receipt'];
        $path = $file->storeAs('receipts', $token.'.'.$file->extension(), 'local');

        if (! is_string($path) || $path === '') {
            return back()->withErrors(['receipt' => 'File nota tidak dapat disimpan.']);
        }

        try {
            $text = $ocr->extract(Storage::disk('local')->path($path));
        } catch (ReceiptOcrException $exception) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['receipt' => $exception->getMessage()]);
        }

        $request->session()->put('receipt_drafts.'.$token, [
            'employee_id' => Auth::guard('employee')->id(),
            'file_path' => $path,
            'hasil_ocr' => $text,
        ]);

        return redirect()->route('employee.pembelian.ocr.review', $token);
    }

    public function review(Request $request, string $token): View
    {
        $draft = $request->session()->get('receipt_drafts.'.$token);

        abort_unless(
            is_array($draft)
                && ($draft['employee_id'] ?? null) === Auth::guard('employee')->id()
                && Storage::disk('local')->exists($draft['file_path'] ?? ''),
            404
        );

        return view('transactions.form', [
            'type' => 'Pembelian',
            'action' => route('employee.pembelian.ocr.store'),
            'counterparts' => Supplier::query()->orderBy('nama_supplier')->get(),
            'products' => Persediaan::query()->orderBy('nama_barang')->get(),
            'date' => today()->toDateString(),
            'showStatus' => true,
            'ocrText' => $draft['hasil_ocr'],
            'receiptToken' => $token,
        ]);
    }

    public function discard(Request $request, string $token): RedirectResponse
    {
        $draft = $request->session()->get('receipt_drafts.'.$token);

        if (
            is_array($draft)
            && ($draft['employee_id'] ?? null) === Auth::guard('employee')->id()
        ) {
            Storage::disk('local')->delete($draft['file_path'] ?? '');
            $request->session()->forget('receipt_drafts.'.$token);
        }

        return redirect()->route('employee.pembelian.index');
    }

    public function download(Pembelian $pembelian): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $receipt = $pembelian->notaPembelian()->firstOrFail();
        $disk = Storage::disk('local');

        abort_unless($disk->exists($receipt->file_path), 404);

        return $disk->download($receipt->file_path, basename($receipt->file_path));
    }
}
