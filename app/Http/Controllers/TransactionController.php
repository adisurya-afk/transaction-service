<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\GlobalResource;
use App\Http\Resources\GlobalCollection;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * List Transaction.
     * 
     * @param \Illuminate\Http\Request
     * 
     * @return Response
     */
    public function index(Request $request)
    {
        $qSort = $request->query('sort');
        $qName = $request->query('name');

        $transactions = Transaction::join('items', 'items.id', '=', 'transactions.items_id');

        if ($qSort != null) $transactions = $transactions->orderBy('date', $qSort);
        else $transactions = $transactions->orderBy('date', 'desc');

        if ($qName != null) $transactions = $transactions->where('items.name', $qName);

        $transactions = $transactions->get(['transactions.*', 'items.name as items_name']);
        $request->message = 'Success';
        $request->data = $transactions;
        $request->meta = null;
        return (new GlobalResource($request))->response()->setStatusCode(200);
    }

    /**
     * Show Transaction with id.
     *
     * @param \Illuminate\Http\Request
     * @param  string  $id
     * 
     * @return Response
     */
    public function show(Request $request, $id)
    {
        $transaction = Transaction::find($id);
        $request->message = 'Success';
        $request->data = $transaction;
        return (new GlobalResource($request))->response()->setStatusCode(200);
    }

    /**
     * Create transaction.
     *
     * @param \Illuminate\Http\Request
     * @bodyParam  int  $items_id
     * @bodyParam  int  $users_id
     * @bodyParam  string  $type
     * @bodyParam  string  $total
     * 
     * @return Response
     */
    public function create(Request $request)
    {
        // check trx type out
        if ($request->type == "OUT") {
            $checkTrxIn = Transaction::select(DB::raw('SUM(total) as total'))->where("items_id", "=", $request->items_id)->where("type", "=", "IN")->first();
            $checkTrxOut = Transaction::select(DB::raw('SUM(total) as total'))->where("items_id", "=", $request->items_id)->where("type", "=", "OUT")->first();
            
            // check no stock
            $total = $checkTrxIn->total - $checkTrxOut->total;
            if ($total < 1) {
                $request->message = 'No Stocks';
                return (new ErrorResource($request))->response()->setStatusCode(400);
            }

            // check request total
            $total = $total - $request->total;
            if ($total < 0) {
                $request->message = 'No Stocks';
                return (new ErrorResource($request))->response()->setStatusCode(400);
            }
        }
        // Save Data
        $transaction = new Transaction();
        $transaction->items_id = $request->items_id;
        $transaction->users_id = $request->users_id;
        $transaction->type = $request->type;
        $transaction->total = $request->total;
        $transaction->date = $request->date;

        $transaction->save();

        $request->message = 'Success';
        $request->data = $transaction;
        return (new GlobalResource($request))->response()->setStatusCode(201);
    }

    /**
     * Delete transaction with id.
     *
     * @param \Illuminate\Http\Request
     * @param  int  $id
     * 
     * @return Response
     */
    public function delete(Request $request, $id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            $request->message = 'Transaction not found';
            return (new ErrorResource($request))->response()->setStatusCode(404);
        }
        $transaction->delete();

        $request->message = 'Success';
        $request->data = $transaction;
        return (new GlobalResource($request))->response()->setStatusCode(200);
    }

    /**
     * Get transaction for graphic.
     *
     * @param \Illuminate\Http\Request
     * @param  int  $id
     * 
     * @return Response
     */
    public function GetTrxGraphic($year)
    {
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        $data = Transaction::select(DB::raw('SUM(total) as total'), DB::raw("to_char(date, 'Month') as month"))
            ->whereYear('date', $year)
            ->groupBy(DB::raw("to_char(date, 'YYYY-MM'), to_char(date, 'Month')"))
            ->get();
        
        $indexedData = [];
        // dd($data);
        foreach ($data as $item) {
            $indexedData[trim($item->month)] = (int)$item->total;
        }
        
        $result = [];
        foreach ($months as $month) {
            $formattedMonth = strtolower($month);
            $total = isset($indexedData[$month]) ? $indexedData[$month] : 0;
            $result[] = ['month' => $formattedMonth, 'total' => $total];
        }
        
        // Untuk menambahkan bulan yang belum ada dalam data
        for ($i = count($result); $i < 12; $i++) {
            $result[] = ['month' => strtolower($months[$i]), 'total' => 0];
        }

        return response()->json([
            'message' => 'Success',
            'data' => $result,
            'meta' => null,
        ])->setStatusCode(200);
    }
}
