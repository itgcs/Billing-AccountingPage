<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Accountnumber;
use App\Models\BalanceAccount;
use App\Models\Accountcategory;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class BalanceController extends Controller
{
    // public function indexBalance(Request $request)
    // {
    //     session()->flash('preloader', true);
    //     session()->flash('page', (object)[
    //         'page' => 'AccountNumber',
    //         'child' => 'Database Balance',
    //     ]);

    //     try {
    //         $form = (object) [
    //             'sort' => $request->sort ?? null,
    //             'order' => $request->order ?? 'desc',
    //             'search' => $request->search ?? null,
    //             'date' => $request->date ?? null,
    //             'type' => $request->type ?? null,
    //         ];

    //         $query = BalanceAccount::with(['accountnumber' => function ($q) use ($request) {
    //             if ($request->filled('date')) {
    //                 $searchMonth = date('Y-m-01', strtotime($request->date));
    //                 $q->where('month', $searchMonth);
    //             }
    //         }]);

    //         // Filter berdasarkan parameter pencarian
    //         if ($request->filled('search')) {
    //             $query->where(function ($q) use ($request) {
    //                 $q->where('name', 'LIKE', '%' . $request->search . '%')
    //                     ->orWhere('account_no', 'LIKE', '%' . $request->search . '%')
    //                     ->orWhere('amount', 'LIKE', '%' . $request->search . '%')
    //                     ->orWhere('created_at', 'LIKE', '%' . $request->search . '%');
    //             });
    //         }

    //         // Mengatur urutan berdasarkan parameter yang dipilih
    //         if ($request->filled('sort') && $request->filled('order')) {
    //             $query->orderBy($request->sort, $request->order);
    //         } else {
    //             $query->orderBy('created_at', 'desc');
    //         }

    //         $data = $query->paginate(25);

    //         $categories = Accountcategory::all();

    //         return view('components.account.balance-index')
    //             ->with('data', $data)
    //             ->with('categories', $categories)
    //             ->with('form', $form);
    //     } catch (Exception $err) {
    //         return dd($err);
    //     }
    // }

    public function indexBalance(Request $request)
    {
        session()->flash('preloader', true);
        session()->flash('page', (object)[
            'page' => 'AccountNumber',
            'child' => 'Database Balance',
        ]);

        try {
            $form = (object) [
                'sort' => $request->sort ?? null,
                'order' => $request->order ?? null,
                'search' => $request->search ?? null,
                'date' => $request->date ?? null,
                // 'type' => $request->type ?? null,
            ];

            $query = BalanceAccount::with('accountnumber');

            if ($request->filled('date')) {
                $searchMonth = date('Y-m-01', strtotime($request->date));
                $query->whereDate('month', '>=', $searchMonth)
                    ->whereDate('month', '<', date('Y-m-01', strtotime($searchMonth . ' +1 month')));
            }

            // Filter berdasarkan parameter pencarian
            if ($request->filled('search')) {
                $query->whereHas('accountnumber', function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('account_no', 'LIKE', '%' . $request->search . '%');
                });
            }

            // Mengatur urutan berdasarkan parameter yang dipilih
            if ($request->filled('sort')) {
                if ($request->sort === 'oldest') {
                    $query->orderBy('month', 'asc');
                } elseif ($request->sort === 'newest') {
                    $query->orderBy('month', 'desc');
                }
            }

            $data = $query->paginate(25);

            $categories = Accountcategory::all();

            return view('components.account.balance-index')
                ->with('data', $data)
                ->with('categories', $categories)
                ->with('form', $form);
        } catch (Exception $err) {
            return dd($err);
        }
    }


    public function createBalance()
    {
        $accountNumbers = Accountnumber::all();
        return view('components.account.create-balance', compact('accountNumbers'));
    }

    public function storeBalance(Request $request)
    {
        // Manipulasi nilai debit dan kredit sebelum validasi
        $debitValues = array_map(function ($value) {
            return str_replace('.', '', str_replace('Rp', '', $value));
        }, $request->debit);

        $creditValues = array_map(function ($value) {
            return str_replace('.', '', str_replace('Rp', '', $value));
        }, $request->credit);

        $request->merge([
            'debit' => $debitValues,
            'credit' => $creditValues,
        ]);

        // Log input data
        Log::info('Request data:', $request->all());

        // Validasi data
        $request->validate([
            'accountnumber_id.*' => 'required|exists:accountnumbers,id',
            'debit.*' => 'required|numeric',
            'credit.*' => 'required|numeric',
            'month.*' => 'required|date_format:Y-m',
        ]);

        // Log setelah validasi berhasil
        Log::info('Validation passed');

        // Penyimpanan data
        foreach ($request->accountnumber_id as $key => $value) {
            $selectedMonth = $request->month[$key];
            $defaultDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();

            // Log data yang akan disimpan
            Log::info('Saving balance account', [
                'accountnumber_id' => $value,
                'debit' => $request->debit[$key],
                'credit' => $request->credit[$key],
                'month' => $defaultDate->toDateString(), 
            ]);

            BalanceAccount::create([
                'accountnumber_id' => $value,
                'debit' => $request->debit[$key],
                'credit' => $request->credit[$key],
                'month' => $defaultDate->toDateString(),
            ]);
        }

        // Log setelah penyimpanan berhasil
        Log::info('Balance accounts created successfully.');

        return redirect()->route('balance.index')->with('success', 'Balance accounts created successfully.');
    }


    public function deleteBalance($id)
    {
        try {

            $balanceAccount = BalanceAccount::findOrFail($id);
            $balanceAccount->delete();

            return response()->json(['message' => 'Balance Account deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete Balance Account.']);
        }
    }



    // public function postMonthlyBalances(Request $request)
    // {
    //     $balance = BalanceAccount::findOrFail($request->balance_id);

    //     // Check if already posted for current month and year
    //     if ($balance->isPostedForMonth(now()->month, now()->year)) {
    //         return redirect()->route('balance.index')->with('error', 'Balance already posted for the current month.');
    //     }

    //     $balance->postMonthly();

    //     return redirect()->route('balance.index')->with('success', 'Balance posted for the current month.');
    // }

    // public function unpostMonthlyBalances(Request $request)
    // {
    //     $balance = BalanceAccount::findOrFail($request->balance_id);

    //     // Check if already unposted for current month and year
    //     if (!$balance->isPostedForMonth(now()->month, now()->year)) {
    //         return redirect()->route('balance.index')->with('error', 'Balance is not posted for the current month.');
    //     }

    //     $balance->unpostMonthly();

    //     return redirect()->route('balance.index')->with('success', 'Balance unposted for the current month.');
    // }
}
