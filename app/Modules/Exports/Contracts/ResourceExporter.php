<?php

namespace App\Modules\Exports\Contracts;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface ResourceExporter
{
    public function download(Request $request, User $actor): BinaryFileResponse;
}
