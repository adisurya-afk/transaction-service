<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\GlobalResource;
use App\Http\Resources\GlobalCollection;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
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
     * List item.
     *
     * @param \Illuminate\Http\Request
     *
     * @return Response|\Illuminate\Http\JsonResponse|object
     */
    public function index(Request $request)
    {
        $qSort = $request->query('sort');
        $qName = $request->query('name');

        $items = Item::select('*');
        if ($qSort != null) $items = $items->orderBy('updated_at', $qSort);
        else $items = $items->orderBy('updated_at', 'desc');

        if ($qName != null) $items = $items->where('items.name', 'ilike', '%'.$qName.'%');

        $items = $items->get();
        $request->message = 'Success';
        $request->data = $items;
        $request->meta = null;
        return (new GlobalResource($request))->response()->setStatusCode(200);
    }

    /**
     * Count total items.
     *
     * @param \Illuminate\Http\Request
     *
     * @return Response|\Illuminate\Http\JsonResponse|object
     */
    public function countTotalItems(Request $request)
    {
        $items = Item::select(
            'items.*',
            DB::raw("CAST(coalesce((select sum(total) from transactions where items_id = items.id and type = 'IN'), 0) - coalesce((select sum(total) from transactions where items_id = items.id and type = 'OUT'), 0) as INTEGER) as total")
        )->get();
        $request->message = 'Success';
        $request->data = $items;
        $request->meta = null;
        return (new GlobalResource($request))->response()->setStatusCode(200);
    }

    /**
     * Show item with id.
     *
     * @param \Illuminate\Http\Request
     * @param  string  $id
     *
     * @return Response|\Illuminate\Http\JsonResponse|object
     */
    public function show(Request $request, $id)
    {
        $item = Item::find($id);
        $request->message = 'Success';
        $request->data = $item;
        return (new GlobalResource($request))->response()->setStatusCode(200);
    }

    /**
     * Create item with name.
     *
     * @param \Illuminate\Http\Request
     * @bodyParam  string  $name
     *
     * @return Response|\Illuminate\Http\JsonResponse|object
     */
    public function create(Request $request)
    {
        // Check if item is already
        $checkItem = Item::firstWhere('name', $request->name);
        if ($checkItem) {
            $request->message = 'Item already exists';
            return (new ErrorResource($request))->response()->setStatusCode(400);
        }

        $newItem = new Item();
        $newItem->name = $request->name;
        $newItem->created_by = $request->created_by;

        $newItem->save();

        $request->message = 'Success';
        $request->data = $newItem;
        return (new GlobalResource($request))->response()->setStatusCode(201);
    }

    /**
     * Update item with id.
     *
     * @param \Illuminate\Http\Request
     * @param  int  $id
     * @bodyParam  string  $name
     *
     * @return Response|\Illuminate\Http\JsonResponse|object
     */
    public function update(Request $request, $id)
    {
        // Check if item is already
        $checkItem = Item::firstWhere('name', $request->name);
        if ($checkItem) {
            $request->message = 'Item already exists';
            return (new ErrorResource($request))->response()->setStatusCode(400);
        }

        $newItem = Item::find($id);
        $newItem->name = $request->name;

        $newItem->save();

        $request->message = 'Success';
        $request->data = $newItem;
        return (new GlobalResource($request))->response()->setStatusCode(200);
    }

    /**
     * Delete item with id.
     *
     * @param \Illuminate\Http\Request
     * @param  int  $id
     *
     * @return Response|\Illuminate\Http\JsonResponse|object
     */
    public function delete(Request $request, $id)
    {
        $checkItem = Item::find($id);
        if (!$checkItem) {
            $request->message = 'Item not found';
            return (new ErrorResource($request))->response()->setStatusCode(404);
        }
        $checkItem->delete();

        $request->message = 'Success';
        $request->data = $checkItem;
        return (new GlobalResource($request))->response()->setStatusCode(200);
    }
}
