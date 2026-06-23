<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Completion — {{ $request->subject }}</title>
    <style>
        @page { margin: 40px 50px; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }
        h2 {
            font-size: 13px;
            margin: 20px 0 8px;
            color: #111827;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
        }
        .meta {
            color: #6b7280;
            font-size: 10px;
            margin-bottom: 20px;
        }
        .kv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .kv-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .kv-table td.label {
            width: 140px;
            color: #6b7280;
        }
        .signer-block {
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .signer-block .name {
            font-weight: bold;
            font-size: 12px;
        }
        .signer-block .role {
            color: #6b7280;
            font-size: 10px;
        }
        .signer-block .sig-line {
            margin-top: 6px;
        }
        .signer-block .typed-sig {
            font-family: serif;
            font-style: italic;
            font-size: 16px;
            color: #111827;
        }
        .signer-block img.drawn-sig {
            max-height: 50px;
            max-width: 200px;
        }
        .event {
            margin-bottom: 4px;
        }
        .event .seq {
            color: #9ca3af;
            width: 30px;
            display: inline-block;
        }
        .event .type {
            font-weight: bold;
            width: 120px;
            display: inline-block;
            text-transform: capitalize;
        }
        .event .ts {
            color: #6b7280;
            font-size: 10px;
        }
        .hash {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            color: #6b7280;
            word-break: break-all;
        }
        .chain-ok { color: #059669; }
        .chain-bad { color: #dc2626; font-weight: bold; }
        .footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Certificate of Completion</h1>
    <div class="meta">Audit trail for document signed electronically</div>

    <table class="kv-table">
        <tr>
            <td class="label">Subject</td>
            <td>{{ $request->subject }}</td>
        </tr>
        <tr>
            <td class="label">Request ID</td>
            <td>{{ $request->id }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>{{ ucfirst($request->status) }}</td>
        </tr>
        <tr>
            <td class="label">Created</td>
            <td>{{ $request->created_at?->format('Y-m-d H:i:s T') }}</td>
        </tr>
        @if ($request->completed_at)
        <tr>
            <td class="label">Completed</td>
            <td>{{ $request->completed_at->format('Y-m-d H:i:s T') }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Hash chain</td>
            <td class="{{ $chainValid ? 'chain-ok' : 'chain-bad' }}">
                {{ $chainValid ? 'Verified — no tampering detected' : 'INVALID — chain verification failed' }}
            </td>
        </tr>
    </table>

    <h2>Signers</h2>
    @foreach ($request->signers as $signer)
        <div class="signer-block">
            <div class="name">{{ $signer->displayName() }}</div>
            @if ($signer->role)
                <div class="role">{{ $signer->role }}</div>
            @endif
            <table class="kv-table">
                <tr>
                    <td class="label">Signing order</td>
                    <td>{{ $signer->signing_order }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td>{{ ucfirst($signer->status) }}</td>
                </tr>
                @if ($signer->signed_at)
                <tr>
                    <td class="label">Signed at</td>
                    <td>{{ $signer->signed_at->format('Y-m-d H:i:s T') }}</td>
                </tr>
                @endif
                @if ($signer->signature_method)
                <tr>
                    <td class="label">Method</td>
                    <td>{{ ucfirst($signer->signature_method) }}</td>
                </tr>
                @endif
                @if ($signer->decline_reason)
                <tr>
                    <td class="label">Decline reason</td>
                    <td>{{ $signer->decline_reason }}</td>
                </tr>
                @endif
            </table>

            @if ($signer->signature_image_path && $signerImagesBase64[$signer->id] ?? null)
                <div class="sig-line">
                    <img src="{{ $signerImagesBase64[$signer->id] }}" class="drawn-sig" alt="signature">
                </div>
            @elseif ($signer->signature_typed_name)
                <div class="sig-line typed-sig">{{ $signer->signature_typed_name }}</div>
            @endif

            @if ($signer->signature_hash)
                <div class="hash">Signature SHA-512: {{ $signer->signature_hash }}</div>
            @endif
        </div>
    @endforeach

    <h2>Activity Log</h2>
    @foreach ($request->events as $event)
        <div class="event">
            <span class="seq">#{{ $event->sequence }}</span>
            <span class="type">{{ str_replace('_', ' ', $event->event_type) }}</span>
            <span class="ts">{{ $event->created_at?->format('Y-m-d H:i:s T') }}</span>
            @if ($event->actor_type)
                &nbsp;· by {{ $event->actor_type }}
            @endif
        </div>
    @endforeach

    <div class="footer">
        This certificate was generated automatically and contains a complete audit trail of the signing process.
        Electronic signatures captured here are legally binding under Philippine R.A. 8792 (E-Commerce Act).
    </div>
</body>
</html>
