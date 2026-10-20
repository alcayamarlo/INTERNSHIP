<?php

namespace App\Http\Controllers;

use App\Models\Placement;
use Barryvdh\DomPDF\Facade\Pdf;

class OjtCertificateController extends Controller
{
    public function __invoke(Placement $placement)
    {
        $this->authorize('downloadCertificate', $placement);

        $placement->load(['student.user', 'student.institution', 'internship', 'employer', 'evaluation', 'clearedBy:id,name']);

        $pdf = Pdf::loadView('ojt.certificate', [
            'placement' => $placement,
            'approvedHours' => $placement->approvedHours(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('ojt-certificate-'.$placement->certificate_number.'.pdf');
    }
}
