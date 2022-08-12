<?php

namespace App\Http\Controllers;

use App\Constants\Api;
use App\Constants\Message;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'numeric'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => FALSE,
                'message' => Message::ERR_SHOPBE_WRONG_INFORMATION,
                'errors' => $validator->errors()
            ], 422);
        }

        $err = $this->userService->doCreate($request->only([
            'name',
            'email',
            'password',
            'phone_number',
            'role'
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

        list($rows, $paging, $err) = $this->userService->getList([
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
        list($data, $err) = $this->userService->getView($id);
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
        $err = $this->userService->doUpdate($id, array_filter($request->only([
            'name',
            'phone_number',
            'role'
        ])));
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
        $forceDelete = $request->get('force', '0');
        $err = $this->userService->doDelete($id, $forceDelete === '0');
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
