<?php

namespace App\Http\Controllers;

use App\DTO\TransferRequestDTO;
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

        $dto = new TransferRequestDTO(
            payerId: $data['payer'],
            payeeId: $data['payee'],
            amount: $data['value']
        );
        
        try {
            $transfer = $this->service->transfer($dto);
            return response($transfer, 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
