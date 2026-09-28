<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a real, valid PDF invoice file using hand-written PDF
 * syntax — no external library (dompdf, mpdf, etc.), since this
 * environment has no Composer available to install one. PDF is a
 * documented, text-based format for a document this simple (a header,
 * a line-item table, totals): this writes the object stream, content
 * stream (using PDF's own text-drawing operators against a standard
 * built-in font, so nothing needs embedding), xref table, and trailer
 * directly. The result is a genuine PDF any reader can open — not a
 * mock or a renamed HTML file.
 */
class PdfInvoiceService
{
    private const FONT = '/F1 12 Tf';

    public function generate(Invoice $invoice): string
    {
        $lines = $this->buildContentLines($invoice);
        $pdf = $this->buildPdf($lines);

        $path = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($path, $pdf);

        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * @return array<array{x: int, y: int, size: int, text: string, bold?: bool}>
     */
    private function buildContentLines(Invoice $invoice): array
    {
        $agency = $invoice->agency;
        $lines = [];
        $y = 760;

        $lines[] = ['x' => 50, 'y' => $y, 'size' => 20, 'text' => 'HealthsBridge', 'bold' => true];
        $lines[] = ['x' => 400, 'y' => $y, 'size' => 16, 'text' => 'INVOICE', 'bold' => true];
        $y -= 30;
        $lines[] = ['x' => 400, 'y' => $y, 'size' => 11, 'text' => $invoice->invoice_number];
        $y -= 40;

        $lines[] = ['x' => 50, 'y' => $y, 'size' => 10, 'text' => 'Bill To:', 'bold' => true];
        $lines[] = ['x' => 400, 'y' => $y, 'size' => 10, 'text' => 'Invoice Date: ' . $invoice->created_at->format('M d, Y')];
        $y -= 16;
        $lines[] = ['x' => 50, 'y' => $y, 'size' => 10, 'text' => $agency?->name ?? 'N/A'];
        $lines[] = ['x' => 400, 'y' => $y, 'size' => 10, 'text' => 'Due Date: ' . $invoice->due_date->format('M d, Y')];
        $y -= 16;
        if ($agency?->email) {
            $lines[] = ['x' => 50, 'y' => $y, 'size' => 10, 'text' => $agency->email];
        }
        $lines[] = ['x' => 400, 'y' => $y, 'size' => 10, 'text' => 'Status: ' . $invoice->status->label()];
        $y -= 40;

        $lines[] = ['x' => 50, 'y' => $y, 'size' => 10, 'text' => 'Description', 'bold' => true];
        $lines[] = ['x' => 480, 'y' => $y, 'size' => 10, 'text' => 'Amount', 'bold' => true];
        $y -= 8;
        $lines[] = ['x' => 50, 'y' => $y, 'size' => 10, 'text' => str_repeat('-', 95)];
        $y -= 20;

        foreach ($invoice->items as $item) {
            $lines[] = ['x' => 50, 'y' => $y, 'size' => 10, 'text' => $this->truncate($item->description, 60)];
            $lines[] = ['x' => 480, 'y' => $y, 'size' => 10, 'text' => '$' . number_format((float) $item->amount, 2)];
            $y -= 18;
        }

        $y -= 10;
        $lines[] = ['x' => 350, 'y' => $y, 'size' => 10, 'text' => 'Subtotal:'];
        $lines[] = ['x' => 480, 'y' => $y, 'size' => 10, 'text' => '$' . number_format((float) $invoice->subtotal_amount, 2)];
        $y -= 16;

        if ((float) $invoice->tax_amount > 0) {
            $lines[] = ['x' => 350, 'y' => $y, 'size' => 10, 'text' => 'Tax:'];
            $lines[] = ['x' => 480, 'y' => $y, 'size' => 10, 'text' => '$' . number_format((float) $invoice->tax_amount, 2)];
            $y -= 16;
        }

        if ((float) $invoice->refunded_amount > 0) {
            $lines[] = ['x' => 350, 'y' => $y, 'size' => 10, 'text' => 'Refunded:'];
            $lines[] = ['x' => 480, 'y' => $y, 'size' => 10, 'text' => '-$' . number_format((float) $invoice->refunded_amount, 2)];
            $y -= 16;
        }

        $lines[] = ['x' => 350, 'y' => $y, 'size' => 12, 'text' => 'Total:', 'bold' => true];
        $lines[] = ['x' => 480, 'y' => $y, 'size' => 12, 'text' => '$' . number_format((float) $invoice->total_amount, 2), 'bold' => true];
        $y -= 50;

        $lines[] = ['x' => 50, 'y' => $y, 'size' => 9, 'text' => 'Thank you for your business with HealthsBridge.'];

        return $lines;
    }

    private function truncate(string $text, int $max): string
    {
        return strlen($text) > $max ? substr($text, 0, $max - 1) . '.' : $text;
    }

    private function escapePdfString(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /**
     * @param  array<array{x: int, y: int, size: int, text: string, bold?: bool}>  $lines
     */
    private function buildPdf(array $lines): string
    {
        $stream = "BT\n";
        foreach ($lines as $line) {
            $font = ($line['bold'] ?? false) ? '/F2' : '/F1';
            $stream .= sprintf(
                "%s %d Tf\n1 0 0 1 %d %d Tm\n(%s) Tj\n",
                $font,
                $line['size'],
                $line['x'],
                $line['y'],
                $this->escapePdfString($line['text'])
            );
        }
        $stream .= "ET";

        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /MediaBox [0 0 612 792] /Contents 6 0 R >>";
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
        $objects[6] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }
}
