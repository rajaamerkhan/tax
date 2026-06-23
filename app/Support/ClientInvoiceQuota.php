<?php

namespace App\Support;

use App\Models\Client;
use Illuminate\Support\Carbon;

class ClientInvoiceQuota
{
    public function firstLimitError(?int $clientId, iterable $invoiceDates, ?string $environment = null): ?string
    {
        $client = $clientId ? Client::query()->find($clientId) : null;

        if (! $client) {
            return null;
        }

        $requestedByMonth = [];

        foreach ($invoiceDates as $invoiceDate) {
            $month = Carbon::parse($invoiceDate)->startOfMonth();
            $key = $month->format('Y-m');
            $requestedByMonth[$key] ??= ['month' => $month, 'count' => 0];
            $requestedByMonth[$key]['count']++;
        }

        foreach ($requestedByMonth as $entry) {
            $limit = (int) $client->max_invoices_per_month;
            $used = $client->invoiceCountForMonth($entry['month'], $environment);

            if ($used + $entry['count'] > $limit) {
                return sprintf(
                    'Monthly invoice limit reached for %s. Limit: %d, used: %d, requested: %d.',
                    $entry['month']->format('F Y'),
                    $limit,
                    $used,
                    $entry['count'],
                );
            }
        }

        return null;
    }
}
