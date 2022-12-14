<?php

namespace App\Http\Controllers;

use App\Constants\Api;
use App\Constants\Message;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string'],
            'user_id' => ['required', 'int'],
            'amount' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => FALSE,
                'message' => Message::ERR_SHOPBE_WRONG_INFORMATION,
                'errors' => $validator->errors()
            ], 422);
        }

        $err = $this->transactionService->doCreate($request->only([
            'type',
            'user_id',
            'order_id',
            'amount',
            'description'
        ]));
        if ($err instanceof \Exception){
            return response()->json([
                'success' => FALSE,
                'message' => $err->getMessage()
            ]);
        }

        return response()->json([
            'success' => TRUE,
            'message' => Message::MSG_SHOPBE_CREATE_SUCCESS
        ]);
    }

    public function list(Request $request)
    {
        if($request->has('filters')){
            $validator = Validator::make($request->all(), [
                'filters' => ['required', 'array'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => FALSE,
                    'message' => Message::ERR_SHOPBE_WRONG_INFORMATION,
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        $page = $request->has('page') ? $request->page : NULL;
        $size = $request->get('size', Api::LIST_DEFAULT_PAGING_SIZE);
        $filters = $request->get('filters', []);

        list($rows, $paging, $err) = $this->transactionService->getList([
            'page' => $page,
            'size' => $size,
            'filters' => $filters
        ]);
        if ($err instanceof \Exception){
            return response()->json([
                'success' => FALSE,
                'data' => [
                    'paging' => $paging,
                    'rows' => []
                ],
                'message' => $err->getMessage()
            ]);
        }

        return response()->json([
            'success' => TRUE,
            'data' => [
                'paging' => $paging,
                'rows' => $rows
            ],
            'message' => ''
        ]);
    }

    public function view(int $id)
    {
        list($data, $err) = $this->transactionService->getView($id);
        if ($err instanceof \Exception){
            return response()->json([
                'success' => FALSE,
                'data' => [],
                'message' => $err->getMessage()
            ]);
        }

        return response()->json([
            'success' => TRUE,
            'data' => $data,
            'message' => ''
        ]);
    }

    public function update(int $id, Request $request)
    {
        $err = $this->transactionService->doUpdate($id, $request->only([
            'type',
            'user_id',
            'order_id',
            'amount',
            'description'
        ]));
        if ($err instanceof \Exception){
            return response()->json([
                'success' => FALSE,
                'message' => $err->getMessage()
            ]);
        }

        return response()->json([
            'success' => TRUE,
            'message' => Message::MSG_SHOPBE_UPDATE_SUCCESS
        ]);
    }

    public function delete(string $id, Request $request)
    {
        $err = $this->transactionService->doDelete($id);
        if ($err instanceof \Exception){
            return response()->json([
                'success' => FALSE,
                'message' => $err->getMessage()
            ]);
        }

        return response()->json([
            'success' => TRUE,
            'message' => Message::MSG_SHOPBE_DELETE_SUCCESS
        ]);
    }
}
