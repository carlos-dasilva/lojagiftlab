<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ShippingQuoteService;
use Illuminate\Http\Request;
use RuntimeException;

class ShippingController extends Controller
{
    public function __invoke(Product $product, Request $request, ShippingQuoteService $service)
    {
        abort_unless($product->status->value === 'published', 404);
        $data = $request->validate(['postal_code' => ['required', 'regex:/^\d{5}-?\d{3}$/']], ['postal_code.required' => 'Informe seu CEP.', 'postal_code.regex' => 'Informe um CEP válido com 8 números.']);
        $product->load('salesLinks');
        try {
            return response()->json(['quotes' => $service->quote($product, $data['postal_code'])]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
