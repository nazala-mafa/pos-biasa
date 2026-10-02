<?php

namespace App\Actions\Outlets;

use App\Imports\OutletDataImport;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportOutletData
{
    /**
     * Import a multi-sheet workbook (one sheet per menu) into the outlet.
     */
    public function handle(Team $outlet, UploadedFile|string $file): OutletDataImport
    {
        $import = new OutletDataImport;

        Excel::import($import, $file);

        DB::transaction(fn () => $import->apply($outlet));

        return $import;
    }
}
