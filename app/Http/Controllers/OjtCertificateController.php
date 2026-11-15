<?php

namespace App\Http\Controllers;

use App\Models\Placement;
use App\Services\OjtDocumentData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OjtCertificateController extends Controller
{
    public function __invoke(Request $request, Placement $placement, OjtDocumentData $documents)
    {
        $this->authorize('downloadCertificate', $placement);

        $placement->load(['student.user', 'student.institution', 'internship', 'employer.user', 'clearedBy:id,name']);

        // The details it was issued with (frozen at clearance), or the current ones for older certificates.
        $info = OjtDocumentData::withSnapshot($documents->values($placement), $placement->certificate_snapshot);
        if ($blocked = $documents->blockedResponse($request, $placement, 'certificate', $info)) {
            return $blocked;
        }

        $pdf = Pdf::loadView('ojt.certificate', [
            'placement' => $placement,
            'info' => $info,
            'approvedHours' => $placement->approvedHours(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('ojt-certificate-'.$placement->certificate_number.'.pdf');
    }
}
