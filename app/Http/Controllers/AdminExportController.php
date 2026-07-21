<?php

namespace App\Http\Controllers;

use App\Modules\Exports\Actions\ResourceExportRegistry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminExportController extends Controller
{
    public function __construct(private readonly ResourceExportRegistry $exports) {}

    public function __invoke(Request $request, string $resource): BinaryFileResponse
    {
        return $this->exports->download(
            $resource,
            $request,
            $this->actor($request),
        );
    }
}
