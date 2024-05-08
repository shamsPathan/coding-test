<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;
use App\Enums\AccountType;


class TransactionController extends Controller
{
    public function index()
    {
        $user = User::with('transactions')->find(auth()->user()->id);

        $balance = $user->transactions->sum('amount') - $user->transactions->sum('fee');

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'account_type' => $user->account_type,
            'balance' => $balance,
            'transactions' => $user->transactions,
        ];
    }

    public function showDeposits()
    {
        $deposits = Transaction::where('transaction_type', 'deposit')->where('user_id', auth()->user()->id)->get();

        return response()->json($deposits, 200);
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01', // Assuming minimum deposit amount is 0.01
        ]);

        $user = User::findOrFail(auth()->user()->id);

        // Update user's balance
        $user->balance += $request->amount;
        $user->save();

        // Create deposit transaction
        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->transaction_type = 'deposit';
        $transaction->amount = $request->amount;
        $transaction->fee = 0; // No fee for deposits
        $transaction->date = now();
        $transaction->save();

        return response()->json(['message' => 'Deposit successful'], 200);
    }

    public function showWithdrawals()
    {
        $withdrawals = Transaction::where('transaction_type', 'withdrawal')->where('user_id', auth()->user()->id)->get();

        return response()->json($withdrawals, 200);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = User::findOrFail(auth()->user()->id);

        $withdrawalAmount = $request->amount;
        $balance = $user->balance;

        // Calculate withdrawal fee based on account type
        $withdrawalFeeRate = ($user->account_type === AccountType::Individual()->value) ? 0.015 : 0.025;
        $withdrawalFee = $withdrawalAmount * $withdrawalFeeRate;

        // Apply free withdrawal conditions for Individual accounts
        if ($user->account_type === AccountType::Individual()->value) {
            $today = now();
            $isFriday = $today->dayOfWeek === 5; // 5 represents Friday

            if ($isFriday) {
                $withdrawalFee = 0;
            }

            // The first 1K withdrawal per transaction is free, and the remaining amount will be charged.



            $monthlyWithdrawals = $user->transactions()
                ->where('transaction_type', 'withdrawal')
                ->whereMonth('date', $today->month)
                ->sum('amount');


            if ($monthlyWithdrawals + $withdrawalAmount <= 5000) {
                $withdrawalFee = 0;
            } else {

                if ($withdrawalAmount <= 1000) {
                    $withdrawalFee = 0;
                } else {

                    if (($monthlyWithdrawals + $withdrawalAmount) > 5000) {
                        $withdrawalFee = ($monthlyWithdrawals + $withdrawalAmount - 5000 - 1000) * $withdrawalFeeRate;
                    }
                }
            }
        }

        // Decrease withdrawal fee for Business accounts after total withdrawal of 50K
        if ($user->account_type === AccountType::Business()->value) {
            $totalWithdrawals = $user->transactions()
                ->where('transaction_type', 'withdrawal')
                ->sum('amount');

            if ($totalWithdrawals >= 50000) {
                $withdrawalFeeRate = 0.015;
                $withdrawalFee = $withdrawalAmount * $withdrawalFeeRate;
            }
        }


        // Calculate new balance
        $newBalance = $balance - ($withdrawalAmount + $withdrawalFee);

        if ($newBalance >= 0) {
            // Update user's balance
            $user->balance = $newBalance;
            $user->save();

            // Create withdrawal transaction
            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->transaction_type = 'withdrawal';
            $transaction->amount = $withdrawalAmount;
            $transaction->fee = $withdrawalFee;
            $transaction->date = now();
            $transaction->save();


            return response()->json(['message' => 'Withdrawal successful'], 200);
        } else {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }
    }
}
