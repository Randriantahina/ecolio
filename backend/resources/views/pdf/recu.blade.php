<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Reçu {{ $recu['numero'] }}</title>
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
  h1 { font-size: 15px; margin: 0 0 2px; }
  .muted { color: #64748b; }
  .head { width: 100%; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 10px; }
  .head td { vertical-align: top; }
  .right { text-align: right; }
  .num { font-size: 15px; font-weight: bold; }
  .info td { padding: 2px 0; }
  .lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
  .lines td, .lines th { padding: 5px 0; border-top: 1px solid #e2e8f0; text-align: left; }
  .lines .amount { text-align: right; }
  .total th, .total td { border-top: 2px solid #94a3b8; font-size: 13px; font-weight: bold; }
  .void { color: #dc2626; font-size: 30px; font-weight: bold; border: 3px solid #dc2626; padding: 2px 12px; text-align: center; margin: 8px 0; }
  .sign { margin-top: 40px; text-align: right; }
  .sign span { display: inline-block; width: 140px; border-top: 1px solid #94a3b8; text-align: center; padding-top: 2px; color: #64748b; }
</style>
</head>
<body>
@php($ar = fn (int $n) => number_format($n, 0, ',', ' ').' Ar')
<table class="head"><tr>
  <td>
    <h1>{{ $recu['ecole']['nom'] ?? '' }}</h1>
    <div class="muted">{{ $recu['ecole']['adresse'] ?? '' }}</div>
    <div class="muted">{{ $recu['ecole']['telephone'] ?? '' }}</div>
  </td>
  <td class="right">
    <div class="muted">{{ strtoupper($recu['mention']) }}</div>
    <div class="num">N° {{ $recu['numero'] }}</div>
    <div class="muted">{{ \Illuminate\Support\Carbon::parse($recu['date_paiement'])->locale('fr')->isoFormat('D MMMM YYYY') }}</div>
  </td>
</tr></table>

@if($recu['annule'])
  <div class="void">ANNULÉ</div>
@endif

<table class="info">
  <tr><td class="muted" width="90">Élève</td><td><strong>{{ $recu['eleve']['nom'] }} {{ $recu['eleve']['prenoms'] }}</strong></td></tr>
  <tr><td class="muted">Matricule</td><td>{{ $recu['eleve']['matricule'] }}</td></tr>
  @if($recu['eleve']['classe'])<tr><td class="muted">Classe</td><td>{{ $recu['eleve']['classe'] }}</td></tr>@endif
  <tr><td class="muted">Mode</td><td>{{ ['especes' => 'Espèces', 'mvola' => 'MVola', 'orange_money' => 'Orange Money', 'airtel_money' => 'Airtel Money', 'virement' => 'Virement', 'cheque' => 'Chèque'][$recu['mode']] ?? $recu['mode'] }}</td></tr>
  @if($recu['reference_externe'])<tr><td class="muted">Référence</td><td>{{ $recu['reference_externe'] }}</td></tr>@endif
</table>

<table class="lines">
  @foreach($recu['lignes'] as $l)
    <tr><td>{{ $l['libelle'] }}</td><td class="amount">{{ $ar($l['montant_ar']) }}</td></tr>
  @endforeach
  <tr class="total"><th>Total payé</th><td class="amount">{{ $ar($recu['total_ar']) }}</td></tr>
  @if($recu['reste_a_payer_ar'] !== null)
    <tr><td class="muted">Reste à payer</td><td class="amount muted">{{ $ar($recu['reste_a_payer_ar']) }}</td></tr>
  @endif
</table>

@if($recu['annule'] && $recu['motif_annulation'])
  <p class="muted">Motif de l’annulation : {{ $recu['motif_annulation'] }}</p>
@endif

<div class="sign"><span>Signature</span></div>
</body>
</html>
