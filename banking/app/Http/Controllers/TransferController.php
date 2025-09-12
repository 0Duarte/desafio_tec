<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Services\TransferService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TransferController extends Controller
{
    protected TransferService $service;

    public function __construct(TransferService $service)
    {
        $this->service = $service;
    }

    public function store(TransferRequest $request)
    {
        $data = $request->validated();

        try {
            $transfer = $this->service->transfer($data['payer'], $data['payee'], $data['value']);
            return response()->json($transfer, 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
