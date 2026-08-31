<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalesGoal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesGoalController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type') === 'monthly' ? 'monthly' : 'weekly';
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 12;
        $totalPeriods = $this->totalPeriods($type);
        $page = min($page, (int) ceil($totalPeriods / $perPage));
        $offset = ($page - 1) * $perPage;
        $periods = $this->periods($type, $offset, min($perPage, $totalPeriods - $offset));
        $goals = SalesGoal::where('period_type', $type)->orderBy('effective_from')->get();
        $sales = Sale::whereBetween('sold_at', [$periods->last()['start'], $periods->first()['end']])->get();

        $evaluations = $periods->map(function ($period, $index) use ($goals, $sales, $page) {
            $goal = $goals->last(fn (SalesGoal $goal) => $goal->effective_from->lte($period['start']));
            $actual = (float) $sales->filter(fn (Sale $sale) => $sale->sold_at->betweenIncluded($period['start'], $period['end']))->sum('gross_total');
            $target = $goal ? (float) $goal->target_amount : null;
            $difference = $target === null ? null : $actual - $target;
            $current = $index === 0 && $page === 1;
            $daysRemaining = $current ? max(1, now()->startOfDay()->diffInDays($period['end']->copy()->startOfDay()) + 1) : 0;

            return $period + [
                'goal' => $goal,
                'actual' => $actual,
                'target' => $target,
                'difference' => $difference,
                'percentage' => $target ? ($actual / $target) * 100 : 0,
                'current' => $current,
                'status' => $target === null ? 'not_configured' : ($actual >= $target ? 'achieved' : ($current ? 'in_progress' : 'missed')),
                'needed_per_day' => $current && $target ? max(0, $target - $actual) / $daysRemaining : 0,
            ];
        });

        $history = new LengthAwarePaginator($evaluations, $totalPeriods, $perPage, $page, [
            'path' => route('admin.finance.goals'),
            'query' => ['type' => $type],
        ]);

        return view('admin.finance.goals', [
            'type' => $type,
            'history' => $history,
            'weeklyGoal' => $this->currentGoal('weekly'),
            'monthlyGoal' => $this->currentGoal('monthly'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'period_type' => 'required|in:weekly,monthly',
            'target_amount' => 'required|numeric|min:0.01|max:9999999999',
        ], [
            'required' => 'O campo :attribute é obrigatório.',
            'in' => 'Escolha uma periodicidade válida.',
            'numeric' => 'Informe um valor válido para a meta.',
            'min' => 'A meta deve ser maior que zero.',
            'max' => 'O valor da meta ultrapassou o limite permitido.',
        ], ['period_type' => 'periodicidade', 'target_amount' => 'valor da meta']);

        $start = $this->currentPeriod($data['period_type'])['start'];
        SalesGoal::updateOrCreate(
            ['period_type' => $data['period_type'], 'effective_from' => $start->toDateString()],
            ['target_amount' => $data['target_amount']],
        );

        $label = $data['period_type'] === 'weekly' ? 'semanal' : 'mensal';

        return redirect()->route('admin.finance.goals', ['type' => $data['period_type']])->with('success', "Meta {$label} salva. Ela vale a partir do período atual.");
    }

    private function currentGoal(string $type): ?SalesGoal
    {
        return SalesGoal::where('period_type', $type)->where('effective_from', '<=', $this->currentPeriod($type)['start'])->latest('effective_from')->first();
    }

    private function periods(string $type, int $offset, int $count)
    {
        $current = $this->currentPeriod($type)['start'];

        return collect(range($offset, $offset + $count - 1))->map(function ($distance) use ($type, $current) {
            $start = $type === 'weekly'
                ? $current->copy()->subWeeks($distance)
                : $current->copy()->subMonthsNoOverflow($distance)->startOfMonth();
            $end = $type === 'weekly' ? $start->copy()->addDays(6)->endOfDay() : $start->copy()->endOfMonth()->endOfDay();

            return ['start' => $start->startOfDay(), 'end' => $end];
        });
    }

    private function currentPeriod(string $type): array
    {
        $start = $type === 'weekly'
            ? now()->startOfWeek(Carbon::SUNDAY)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        return ['start' => $start, 'end' => $type === 'weekly' ? $start->copy()->addDays(6)->endOfDay() : $start->copy()->endOfMonth()->endOfDay()];
    }

    private function totalPeriods(string $type): int
    {
        $firstSale = Sale::min('sold_at');
        $firstGoal = SalesGoal::where('period_type', $type)->min('effective_from');
        $firstDate = collect([$firstSale, $firstGoal])->filter()->min();

        if (! $firstDate) {
            return 12;
        }

        $first = Carbon::parse($firstDate);
        $current = $this->currentPeriod($type)['start'];
        $periods = $type === 'weekly'
            ? (int) floor($first->startOfWeek(Carbon::SUNDAY)->diffInWeeks($current)) + 1
            : (int) floor($first->startOfMonth()->diffInMonths($current)) + 1;

        return max(12, $periods);
    }
}
