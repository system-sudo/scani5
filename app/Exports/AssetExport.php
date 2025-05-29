<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Events\BeforeExport;

class AssetExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    /**
     * @param $asset
     * @param $password
     */
    protected $asset;



    public function __construct($asset)
    {
        $this->asset = $asset;
 
    }
    public function collection()
    {
        return collect($this->asset);
    }



    public function headings(): array
    {
        // Define the headings for your Excel file
        return [
            'Host ID',
            'Host name',
            'Resource ID',
            'IP address v4',
            'IP address v6',
            'Os',
            'Risk to infrastructure score',
            'Severity',
            'Type',
            'Agent status',
            'Last user logged',
            'Last scanned',
            'Last system boot',
            'Last checked in',
            'Vulnerabilities count',
        ];
    }
}
