<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    /** The signed-in student's certificates. */
    public function index(Request $request)
    {
        $certificates = $request->user()
            ->certificates()
            ->with('course')
            ->get();

        return view('academy.certificates', [
            'certificates' => $certificates,
        ]);
    }

    /** Stream a certificate PDF to its owner. */
    public function download(Request $request, Certificate $certificate)
    {
        abort_unless($certificate->user_id === $request->user()->id, 403);
        abort_unless($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path), 404);

        return Storage::disk('public')->download(
            $certificate->pdf_path,
            'certificate-'.$certificate->number.'.pdf'
        );
    }

    /** Public verification page — anyone with the number can confirm a certificate. */
    public function verify(string $number)
    {
        $certificate = Certificate::with(['course', 'user'])
            ->where('number', $number)
            ->first();

        return view('academy.certificate-verify', [
            'certificate' => $certificate,
            'number' => $number,
        ]);
    }
}
