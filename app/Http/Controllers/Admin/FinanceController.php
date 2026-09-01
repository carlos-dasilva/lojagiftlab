<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditSale;
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
        $receivedCredits = CreditSale::with('items')->whereBetween('received_at', [$from, $to])->get();
        $paid = Payable::whereBetween('paid_at', [$from, $to])->sum('amount');

        return view('admin.finance.index', [
            'income' => $sales->sum('net_total') + $receivedCredits->sum('net_total'),
            'expenses' => (float) $paid,
            'pending' => (float) Payable::whereNull('paid_at')->sum('amount'),
            'salesCount' => $sales->sum('quantity') + CreditSale::whereBetween('received_at', [$from, $to])->with('items')->get()->sum(fn (CreditSale $credit) => $credit->items->sum('quantity')),
            'receivables' => CreditSale::with('items')->whereNull('received_at')->get()->sum('net_total'),
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
            'product_id' => ['required', function ($attribute, $value, $fail) {
                if ($value !== 'other' && ! Product::whereKey($value)->exists()) {
                    $fail('Escolha um produto válido ou a opção Outro / item avulso.');
                }
            }],
            'item_name' => 'nullable|required_if:product_id,other|string|max:160',
            'sales_channel_id' => 'nullable|exists:sales_channels,id',
            'quantity' => 'required|integer|min:1|max:99999',
            'unit_price' => 'required|numeric|min:0.01|max:9999999999',
            'shipping_income' => 'nullable|numeric|min:0|max:9999999999',
            'fee' => 'nullable|numeric|min:0|max:9999999999',
            'sold_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ], $this->messages(), ['product_id' => 'produto ou item avulso', 'item_name' => 'nome do item avulso', 'sales_channel_id' => 'canal de venda', 'quantity' => 'quantidade', 'unit_price' => 'valor unitário', 'shipping_income' => 'frete recebido', 'fee' => 'taxas', 'sold_at' => 'data da venda', 'notes' => 'observações']);

        $generic = $data['product_id'] === 'other';
        $product = $generic ? null : Product::findOrFail($data['product_id']);
        $data['product_id'] = $product?->id;
        $data['product_name'] = $product?->name ?? trim($data['item_name']);
        unset($data['item_name']);
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

    public function credits()
    {
        return $this->creditsView();
    }

    public function editCredit(CreditSale $credit)
    {
        return $this->creditsView($credit->load('items'));
    }

    public function storeCredit(Request $request)
    {
        $this->saveCredit($request);

        return back()->with('success', 'Venda fiada registrada. Ela entrará no caixa somente quando for marcada como recebida.');
    }

    public function updateCredit(Request $request, CreditSale $credit)
    {
        $this->saveCredit($request, $credit);

        return redirect()->route('admin.finance.credits')->with('success', 'Venda fiada atualizada com sucesso.');
    }

    public function toggleCreditReceived(Request $request, CreditSale $credit)
    {
        if ($credit->received_at) {
            $credit->update(['received_at' => null]);

            return back()->with('success', 'Pagamento reaberto e removido do extrato e das vendas.');
        }

        $data = $request->validate([
            'received_on' => 'required|date|after_or_equal:'.$credit->sold_at->toDateString().'|before_or_equal:'.now()->toDateString(),
        ], ['required' => 'Confirme a data do recebimento.', 'date' => 'Informe uma data de recebimento válida.', 'after_or_equal' => 'O recebimento não pode ser anterior à venda.', 'before_or_equal' => 'O recebimento não pode estar no futuro.']);
        $credit->update(['received_at' => Carbon::parse($data['received_on'])->setTime(12, 0)]);

        return back()->with('success', 'Pagamento recebido e adicionado ao extrato e às vendas.');
    }

    public function toggleCreditDelivered(CreditSale $credit)
    {
        $credit->update(['delivered_at' => $credit->delivered_at ? null : now()]);

        return back()->with('success', $credit->fresh()->delivered_at ? 'Pedido marcado como entregue.' : 'Pedido marcado como não entregue.');
    }

    public function destroyCredit(CreditSale $credit)
    {
        $credit->delete();

        return back()->with('success', 'Venda fiada excluída.');
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
        $credits = CreditSale::with(['channel', 'items'])->whereBetween('received_at', [$from, $to])->get()->map(fn (CreditSale $credit) => [
            'date' => $credit->received_at,
            'type' => 'income',
            'description' => $credit->items->count().' '.($credit->items->count() === 1 ? 'item' : 'itens').' para '.$credit->customer_name,
            'detail' => 'Fiado recebido de '.$credit->customer_name,
            'amount' => $credit->net_total,
        ]);

        return $sales->concat($credits)->concat($expenses)->sortByDesc('date')->values();
    }

    private function creditsView(?CreditSale $editing = null)
    {
        return view('admin.finance.credits', [
            'credits' => CreditSale::with(['items', 'channel'])->orderByRaw('received_at IS NOT NULL')->latest('sold_at')->latest()->paginate(20),
            'products' => Product::orderBy('name')->get(),
            'channels' => SalesChannel::where('active', true)->orderBy('name')->get(),
            'pendingTotal' => CreditSale::with('items')->whereNull('received_at')->get()->sum('net_total'),
            'editing' => $editing,
        ]);
    }

    private function saveCredit(Request $request, ?CreditSale $credit = null): CreditSale
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:160',
            'customer_contact' => 'nullable|string|max:160',
            'sales_channel_id' => 'nullable|exists:sales_channels,id',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.item_name' => 'nullable|required_without:items.*.product_id|string|max:160',
            'items.*.quantity' => 'required|integer|min:1|max:99999',
            'items.*.unit_price' => 'required|numeric|min:0.01|max:9999999999',
            'shipping_income' => 'nullable|numeric|min:0|max:9999999999',
            'fee' => 'nullable|numeric|min:0|max:9999999999',
            'sold_at' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:sold_at',
            'delivered' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ], $this->messages(), ['customer_name' => 'cliente', 'customer_contact' => 'contato', 'sales_channel_id' => 'canal de venda', 'items' => 'itens', 'items.*.product_id' => 'produto', 'items.*.item_name' => 'nome do item avulso', 'items.*.quantity' => 'quantidade', 'items.*.unit_price' => 'valor unitário', 'shipping_income' => 'frete', 'fee' => 'taxas', 'sold_at' => 'data da venda', 'due_date' => 'previsão de pagamento', 'notes' => 'observações']);

        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id')->filter())->get()->keyBy('id');
        $items = collect($data['items'])->values()->map(function ($item, $order) use ($products) {
            $product = filled($item['product_id'] ?? null) ? $products->get((int) $item['product_id']) : null;

            return [
                'product_id' => $product?->id,
                'item_name' => $product?->name ?? trim($item['item_name']),
                'quantity' => (int) $item['quantity'],
                'unit_price' => $item['unit_price'],
                'order' => $order,
            ];
        });
        $first = $items->first();

        return DB::transaction(function () use ($data, $items, $first, $request, $credit) {
            $header = [
                'product_id' => $first['product_id'],
                'product_name' => $first['item_name'],
                'quantity' => $first['quantity'],
                'unit_price' => $first['unit_price'],
                'customer_name' => $data['customer_name'],
                'customer_contact' => $data['customer_contact'] ?? null,
                'sales_channel_id' => $data['sales_channel_id'] ?? null,
                'shipping_income' => $data['shipping_income'] ?? 0,
                'fee' => $data['fee'] ?? 0,
                'sold_at' => $data['sold_at'],
                'due_date' => $data['due_date'] ?? null,
                'delivered_at' => $request->boolean('delivered') ? ($credit?->delivered_at ?? now()) : null,
                'notes' => $data['notes'] ?? null,
            ];

            if ($credit) {
                $credit->update($header);
                $credit->items()->delete();
            } else {
                $credit = CreditSale::create($header);
            }

            $credit->items()->createMany($items->all());

            return $credit;
        });
    }

    private function messages(): array
    {
        return ['required' => 'O campo :attribute é obrigatório.', 'required_if' => 'O campo :attribute é obrigatório para esta opção.', 'numeric' => 'Informe um valor válido em :attribute.', 'integer' => 'O campo :attribute deve ser um número inteiro.', 'min' => 'O campo :attribute deve ser no mínimo :min.', 'max' => 'O campo :attribute ultrapassou o limite permitido.', 'date' => 'Informe uma data válida em :attribute.', 'after_or_equal' => 'O campo :attribute não pode ser anterior à data da venda.', 'exists' => 'A opção escolhida em :attribute não é válida.'];
    }
}
