<?php


namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithCustomStartCell
{
    protected $type;
    protected $rowCount = 0;

    public function __construct($type = 'all')
    {
        $this->type = $type;
    }

    public function collection()
    {
        $users = match ($this->type) {
            'students' => User::with('nfcCard')->where('role_id', 3)->get(),
            'faculty' => User::with('nfcCard')->where('role_id', 2)->get(),
            'management', 'directors' => User::with('nfcCard')->where('role_id', 4)->get(),
            'admin' => User::with('nfcCard')->where('role_id', 1)->get(),
            default => User::with('nfcCard')->where('role_id', '!=', 1)->get(),
        };

        $this->rowCount = $users->count();
        return $users;
    }

    public function startCell(): string
    {
        return 'A5'; // Table starts at row 5, leaving 1-4 for the title header
    }

    public function headings(): array
    {
        return [
            'No.',
            'ID Number / LRN', 
            'First Name', 
            'Last Name', 
            'Email Address', 
            'Grade Level', 
            'Strand', 
            'Section', 
            'NFC Card UID', 
            'Phone Number', 
            'Parent / Guardian', 
            'Parent Contact'
        ];
    }

    public function map($user): array
    {
        static $counter = 1;

        return [
            $counter++,
            $user->id_number ?? 'N/A',
            $user->first_name ?? $user->name,
            $user->last_name ?? '',
            $user->email,
            $user->grade_level ?? 'N/A',
            $user->strand ?? 'N/A',
            $user->section ?? 'N/A',
            $user->nfcCard->tag_id ?? 'No Card',
            $user->phone_number ?? 'N/A',
            $user->parent_name ?? 'N/A',
            $user->parent_phone_number ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // 1. Set Report Header Rows (Rows 1 to 3)
        $sheet->setCellValue('A1', 'SIATRACK INSTITUTIONAL DIRECTORY');
        $sheet->setCellValue('A2', 'Category Records: ' . strtoupper($this->type));
        $sheet->setCellValue('A3', 'Generated On: ' . date('F d, Y - h:i A'));

        // Style the Header Text
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('555555');

        // Calculate the last row of the table
        $lastRow = 5 + $this->rowCount;

        // 2. Apply Borders and Alignment to the Table Data (Rows 5 to Last Row)
        $tableRange = 'A5:L' . max(5, $lastRow);
        
        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'CBD5E1'], // Clean light gray gridlines
                ],
            ],
        ]);

        return [
            // 3. Style Table Column Headers (Row 5)
            5 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '8B1818'] // SIATRACK Maroon Theme Header
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }
}