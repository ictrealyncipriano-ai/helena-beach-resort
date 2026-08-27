<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\CottageDateBlock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Month-grid availability calendar. Shows every cottage's blocked nights for
 * the selected month, coloured by whether the block is a pending hold, a
 * booked stay, or a manual admin block, so overlapping state is easy to spot.
 */
class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $month = $this->resolveMonth($request->get('month'));
        $monthStart = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();

        $blocks = CottageDateBlock::with('inquiry')
            ->whereBetween('date', [
                $monthStart->toDateString(),
                $monthStart->endOfMonth()->toDateString(),
            ])
            ->get()
            ->groupBy('cottage_id');

        $calendar = Cottage::orderBy('sort_order')->get()->map(function ($cottage) use ($monthStart, $blocks) {
            return [
                'cottage' => $cottage,
                'weeks' => $this->buildWeeks($monthStart, $blocks->get($cottage->id, collect())),
            ];
        });

        return view('admin.availability.index', [
            'calendar' => $calendar,
            'month' => $month,
            'monthLabel' => $monthStart->format('F Y'),
            'prev' => $monthStart->subMonth()->format('Y-m'),
            'next' => $monthStart->addMonth()->format('Y-m'),
            'today' => today()->format('Y-m-d'),
        ]);
    }

    /**
     * Turn one month of blocks into weeks (Monday-first), one cell per day.
     * Each cell carries the date and whether it is free or held, plus the
     * type and reason of the hold.
     *
     * @param  Collection<int, CottageDateBlock>  $blocks
     * @return array<int, array<int, array<string, mixed>|null>>
     */
    private function buildWeeks(CarbonInterface $monthStart, $blocks): array
    {
        $daysInMonth = $monthStart->daysInMonth;
        $lead = ($monthStart->dayOfWeek + 6) % 7;
        $cells = (int) ceil(($lead + $daysInMonth) / 7) * 7;

        $weeks = [];
        $week = [];

        for ($i = 0; $i < $cells; $i++) {
            $dayNo = $i - $lead + 1;

            if ($dayNo < 1 || $dayNo > $daysInMonth) {
                $week[] = null;
            } else {
                $date = $monthStart->addDays($dayNo - 1);
                $block = $blocks->first(fn ($b) => $b->date->toDateString() === $date->toDateString());

                $week[] = $this->cell($date, $block);
            }

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return $weeks;
    }

    /**
     * @return array<string, mixed>
     */
    private function cell(CarbonInterface $date, ?CottageDateBlock $block): array
    {
        $type = null;
        $reason = null;

        if ($block) {
            $reason = $block->reason;

            if ($block->inquiry_id === null) {
                $type = Inquiry::METHOD_MANUAL;
            } elseif (is_string($reason) && str_contains($reason, 'Booked')) {
                $type = 'booked';
            } else {
                $type = 'pending';
            }
        }

        return [
            'dateLabel' => $date->format('D j'),
            'date' => $date->toDateString(),
            'isToday' => $date->toDateString() === today()->format('Y-m-d'),
            'type' => $type,
            'reason' => $reason,
            'inquiry' => $block?->inquiry,
        ];
    }

    /**
     * Normalize the ?month=YYYY-MM query, falling back to the current month for
     * anything that is not a parseable year-month (never fails open on garbage).
     */
    private function resolveMonth(?string $value): string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return now()->format('Y-m');
        }

        return $value;
    }
}
