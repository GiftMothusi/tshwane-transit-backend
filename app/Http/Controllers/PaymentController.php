<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Ticket;
use App\Models\BusRoute;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth as Authentication;

class PaymentController extends Controller
{
    /**
     * Get user's wallet information and recent transactions
     */
    public function getWallet()
    {
        try {
            $user = Authentication::user();
            $wallet = $user->wallet;

            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'currency' => 'ZAR'
                ]);
            }

            $transactions = $wallet->transactions()
                ->with('route:id,name,route_number')
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'status' => $transaction->status,
                        'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                        'reference' => $transaction->reference,
                        'payment_method' => $transaction->payment_method
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'balance' => $wallet->balance,
                    'currency' => $wallet->currency,
                    'transactions' => $transactions
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch wallet:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch wallet information'
            ], 500);
        }
    }

    /**
     * Process wallet top-up
     */
    public function topupWallet(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:1000',
            'payment_method' => 'required|in:credit_card,instant_eft,debit_card'
        ]);

        try {
            $user = Authentication::user();
            $wallet = $user->wallet;

            DB::beginTransaction();

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'topup',
                'amount' => $request->amount,
                'status' => 'pending',
                'reference' => Transaction::generateReference('TOP'),
                'payment_method' => $request->payment_method,
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            // Simulate payment gateway integration
            // In production, this would be replaced with actual payment gateway logic
            $paymentSuccessful = true;

            if ($paymentSuccessful) {
                $wallet->addFunds($request->amount);
                $transaction->update(['status' => 'completed']);
                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Wallet topped up successfully',
                    'data' => [
                        'new_balance' => $wallet->balance,
                        'transaction_id' => $transaction->id
                    ]
                ]);
            }

            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Payment failed'
            ], 400);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Topup failed:', [
                'error' => $e->getMessage(),
                'user_id' => Authentication::id(),
                'amount' => $request->amount
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process top-up'
            ], 500);
        }
    }

    /**
     * Purchase a bus ticket
     */
    public function purchaseTicket(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:bus_routes,id',
            'departure_time' => 'required|date|after:now'
        ]);

        try {
            $user = Authentication::user();
            $wallet = $user->wallet;
            $route = BusRoute::findOrFail($request->route_id);

            if (!$wallet->canAfford($route->fare)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient balance'
                ], 400);
            }

            DB::beginTransaction();

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'ticket_purchase',
                'amount' => $route->fare,
                'status' => 'pending',
                'reference' => Transaction::generateReference('TIX'),
                'metadata' => [
                    'route_number' => $route->route_number,
                    'departure_time' => $request->departure_time,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            // Deduct fare from wallet
            if ($wallet->deductFunds($route->fare)) {
                // Create ticket
                $ticket = Ticket::create([
                    'user_id' => $user->id,
                    'route_id' => $route->id,
                    'transaction_id' => $transaction->id,
                    'valid_from' => Carbon::parse($request->departure_time),
                    'valid_until' => Carbon::parse($request->departure_time)->addHours(4),
                    'status' => 'active',
                    'qr_code' => Ticket::generateQRCode(),
                    'metadata' => [
                        'purchase_price' => $route->fare,
                        'route_name' => $route->name
                    ]
                ]);

                $transaction->update(['status' => 'completed']);
                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ticket purchased successfully',
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'qr_code' => $ticket->qr_code,
                        'valid_from' => $ticket->valid_from,
                        'valid_until' => $ticket->valid_until,
                        'new_balance' => $wallet->balance
                    ]
                ]);
            }

            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment'
            ], 400);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Ticket purchase failed:', [
                'error' => $e->getMessage(),
                'user_id' => Authentication::id(),
                'route_id' => $request->route_id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to purchase ticket'
            ], 500);
        }
    }

    /**
     * Get user's active tickets
     */
    public function getActiveTickets()
    {
        try {
            $tickets = Authentication::user()->tickets()
                ->with('route:id,name,route_number')
                ->where('status', 'active')
                ->where('valid_until', '>', now())
                ->get()
                ->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'route_name' => $ticket->route->name,
                        'route_number' => $ticket->route->route_number,
                        'valid_from' => $ticket->valid_from->format('Y-m-d H:i:s'),
                        'valid_until' => $ticket->valid_until->format('Y-m-d H:i:s'),
                        'qr_code' => $ticket->qr_code
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $tickets
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch active tickets:', [
                'error' => $e->getMessage(),
                'user_id' => Authentication::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active tickets'
            ], 500);
        }
    }
}
