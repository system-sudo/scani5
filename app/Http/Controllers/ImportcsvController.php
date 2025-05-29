<?php

namespace App\Http\Controllers;

use App\ResponseApi;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Support\Str;

class ImportcsvController extends Controller
{
    use ResponseApi;

    public function importCsv(Request $request, string $type)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:2048',
        ], [
            'file.required' => 'File is required.',
        ]);

        try {
            $className = '\\App\\Imports\\' . Str::studly($type) . 'Import';
            if (!class_exists($className)) {
                return $this->sendError("Invalid import type: $type");
            }

            return $this->handleImport($request, $className, $type);
        } catch (\Exception $e) {
            return $this->sendError('An error occurred during import.', $e->getMessage());
        }
    }

    private function handleImport(Request $request, string $importClass, string $type)
    {
        try {
            $import = new $importClass();
            Excel::import($import, $request->file('file'));
            return $this->sendResponse(null, Str::studly($type) . ' Import successful');
        } catch (ValidationException $e) {
            return $this->sendError('An error occurred during import.', $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('An error occurred during import.', $e->getMessage());
        }
    }
}
