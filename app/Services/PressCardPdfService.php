<?php

namespace App\Services;

use App\Models\PressCard;
use Illuminate\Support\Facades\View;

class PressCardPdfService
{
    public function html(PressCard $card): string
    {
        return View::make('pdf.press-card', $this->viewData($card))->render();
    }

    /**
     * PDF через DomPDF, если пакет установлен; иначе HTML-файл для печати.
     */
    public function downloadResponse(PressCard $card)
    {
        $filename = 'press-card-' . $card->card_number . '.pdf';
        $data = $this->viewData($card, forPrint: true);

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.press-card', $data)
                ->setPaper([0, 0, 242.65, 153.07], 'landscape');

            return $pdf->download($filename);
        }

        return response($this->html($card), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="' . str_replace('.pdf', '.html', $filename) . '"',
        ]);
    }

    public function viewData(PressCard $card, bool $forPrint = false): array
    {
        $photoSrc = null;
        if ($card->photo_path) {
            $photoSrc = $forPrint
                ? storage_path('app/public/' . $card->photo_path)
                : asset('storage/' . $card->photo_path);
        }

        return [
            'card' => $card,
            'forPrint' => $forPrint,
            'photoSrc' => $photoSrc,
        ];
    }
}
