<div class="statement-list">
@forelse($entries as $entry)
    <article><span class="statement-icon {{ $entry['type'] }}">{{ $entry['type'] === 'income' ? '↓' : '↑' }}</span><div><strong>{{ $entry['description'] }}</strong><small>{{ $entry['date']->format('d/m/Y') }} · {{ $entry['detail'] }}</small></div><b class="{{ $entry['type'] === 'income' ? 'money-in' : 'money-out' }}">{{ $entry['type'] === 'income' ? '+' : '-' }} R$ {{ number_format($entry['amount'], 2, ',', '.') }}</b></article>
@empty
    <div class="finance-empty">Nenhuma movimentação neste período.</div>
@endforelse
</div>
