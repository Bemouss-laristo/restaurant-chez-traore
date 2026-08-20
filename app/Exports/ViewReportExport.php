<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

/**
 * Export Excel générique : convertit une vue-tableau Blade en fichier .xlsx.
 * La même vue sert aussi à générer le PDF, pour ne pas dupliquer la mise en forme.
 */
class ViewReportExport implements FromView
{
    public function __construct(
        private readonly string $view,
        private readonly array $data,
    ) {
    }

    public function view(): View
    {
        return view($this->view, $this->data);
    }
}
