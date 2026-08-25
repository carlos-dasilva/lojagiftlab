<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesChannel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    public function index()
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        $sales = Sale::whereBetween('sold_at', [$from, $to])->get();
        $paid = Payable::whereBetween('paid_at', [$from, $to])->sum('amount');

        return view('admin.finance.index', [
            'income' => $sales->sum('net_total'),
            'expenses' => (float) $paid,
            'pending' => (float) Payable::whereNull('paid_at')->sum('amount'),
            'salesCount' => $sales->sum('quantity'),
            'entries' => $this->entries($from, $to)->take(8),
        ]);
    }

    public function sales()
    {
        return view('admin.finance.sales', [
            'sales' => Sale::with(['product', 'channel'])->latest('sold_at')->latest()->paginate(20),
            'products' => Product::orderBy('name')->get(),
            'channels' => SalesChannel::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeSale(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sales_channel_id' => 'nullable|exists:sales_channels,id',
            'quantity' => 'required|integer|min:1|max:99999',
            'unit_price' => 'required|numeric|min:0.01|max:9999999999',
            'shipping_income' => 'nullable|numeric|min:0|max:9999999999',
            'fee' => 'nullable|numeric|min:0|max:9999999999',
            'sold_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ], $this->messages(), ['product_id' => 'produto', 'sales_channel_id' => 'canal de venda', 'quantity' => 'quantidade', 'unit_price' => 'valor unitário', 'shipping_income' => 'frete recebido', 'fee' => 'taxas', 'sold_at' => 'data da venda', 'notes' => 'observações']);

        $product = Product::findOrFail($data['product_id']);
        $data['product_name'] = $product->name;
        $data['shipping_income'] = $data['shipping_income'] ?? 0;
        $data['fee'] = $data['fee'] ?? 0;
        Sale::create($data);

        return back()->with('success', 'Venda registrada com sucesso.');
    }

    public function destroySale(Sale $sale)
    {
        $sale->delete();

        return back()->with('success', 'Registro de venda excluído.');
    }

    public function payables()
    {
        return view('admin.finance.payables', ['payables' => Payable::orderByRaw('paid_at IS NOT NULL')->orderBy('due_date')->paginate(20)]);
    }

    public function storePayable(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string|max:180',
            'category' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01|max:9999999999',
            'installments' => 'nullable|integer|min:1|max:120',
            'due_date' => 'required|date',
            'paid' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ], $this->messages(), ['description' => 'descrição', 'category' => 'categoria', 'amount' => 'valor total', 'installments' => 'número de parcelas', 'due_date' => 'primeiro vencimento', 'notes' => 'observações']);

        $installments = (int) ($data['installments'] ?? 1);
        $totalInCents = (int) round(((float) $data['amount']) * 100);
        $baseInCents = intdiv($totalInCents, $installments);
        $remainingCents = $totalInCents % $installments;
        $firstDueDate = Carbon::parse($data['due_date']);
        $group = $installments > 1 ? (string) Str::uuid() : null;

        DB::transaction(function () use ($data, $installments, $baseInCents, $remainingCents, $firstDueDate, $group, $request) {
            foreach (range(1, $installments) as $number) {
                Payable::create([
                    'installment_group' => $group,
                    'description' => $data['description'],
                    'category' => $data['category'] ?? null,
                    'amount' => ($baseInCents + ($number <= $remainingCents ? 1 : 0)) / 100,
                    'installment_number' => $number,
                    'installments_total' => $installments,
                    'due_date' => $firstDueDate->copy()->addMonthsNoOverflow($number - 1)->toDateString(),
                    'paid_at' => $request->boolean('paid') ? now() : null,
                    'notes' => $data['notes'] ?? null,
                ]);
            }
        });

        return back()->with('success', $installments > 1 ? "Compra registrada em {$installments} parcelas." : 'Conta registrada com sucesso.');
    }

    public function togglePayable(Payable $payable)
    {
        $payable->update(['paid_at' => $payable->paid_at ? null : now()]);

        return back()->with('success', $payable->fresh()->paid_at ? 'Conta marcada como paga.' : 'Conta reaberta.');
    }

    public function destroyPayable(Payable $payable)
    {
        $payable->delete();

        return back()->with('success', 'Conta excluída.');
    }

    public function statement(Request $request)
    {
        $data = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfMonth();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfMonth();
        $entries = $this->entries($from, $to);

        return view('admin.finance.statement', [
            'entries' => $entries,
            'from' => $from,
            'to' => $to,
            'income' => $entries->where('type', 'income')->sum('amount'),
            'expenses' => $entries->where('type', 'expense')->sum('amount'),
        ]);
    }

    private function entries(Carbon $from, Carbon $to)
    {
        $sales = Sale::with('channel')->whereBetween('sold_at', [$from, $to])->get()->map(fn (Sale $sale) => [
            'date' => $sale->sold_at,
            'type' => 'income',
            'description' => $sale->quantity.'× '.$sale->product_name,
            'detail' => $sale->channel?->name ?: 'Venda direta',
            'amount' => $sale->net_total,
        ]);
        $expenses = Payable::whereNotNull('paid_at')->whereBetween('paid_at', [$from, $to])->get()->map(fn (Payable $payable) => [
            'date' => $payable->paid_at,
            'type' => 'expense',
            'description' => $payable->description,
            'detail' => $payable->category ?: 'Conta paga',
            'amount' => (float) $payable->amount,
        ]);

        return $sales->concat($expenses)->sortByDesc('date')->values();
    }

    private function messages(): array
    {
        return ['required' => 'O campo :attribute é obrigatório.', 'numeric' => 'Informe um valor válido em :attribute.', 'integer' => 'O campo :attribute deve ser um número inteiro.', 'min' => 'O campo :attribute deve ser no mínimo :min.', 'max' => 'O campo :attribute ultrapassou o limite permitido.', 'date' => 'Informe uma data válida em :attribute.', 'exists' => 'A opção escolhida em :attribute não é válida.'];
    }
}
