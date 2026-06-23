<?php

namespace App\Http\Controllers;

use App\Models\FormulaVariable;
use Illuminate\Http\JsonResponse;

class FormulaVariableController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => FormulaVariable::all(['name', 'description']),
        ]);
    }
}
