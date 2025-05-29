<?php
namespace App\TCPDF;


use Elibyy\TCPDF\Facades\TCPDF;


class PDF extends TCPDF
{

    public static function Setup($orgId)
    {

        $client_logo = getLogo($orgId)->dark_logo;
        self::SetCreator('SQ1Security | Scani5 Team');
        self::SetAuthor('SQ1Security');
        self::SetTitle('Scani5 Assessment Report');
        self::SetSubject('Scani5 Report');
        self::SetKeywords('Scani5 Assessment Report');
        self::setHeaderCallback(function ($pdf) use($client_logo) {
            $header = '';
            if($client_logo){
                $pdf->Image($client_logo, 15, 10, 35, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            $secqureone = url('/images/scanify_logo.png');
            $pdf->Image($secqureone, 160, 10, 30, '', 'PNG', '', 'L', false, 300, '', false, false, 0, false, false, false);
            $pdf->SetY(25);
            $pdf->SetFont('helvetica', '', 14);
            $pdf->Cell(210, 25, $header, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        });
        self::setFooterCallback(function ($pdf) {
            $content = 'CONFIDENTIAL AND PROPRIETARY - DO NOT REPRODUCE';
            $url = 'sq1.security';
            $pdf->SetY(0);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(255, 255, 255);
            $bottom = url('/images/footer-without-text.png');
            $pdf->Image($bottom, -50, 283, 300, "", "PNG", "", "T", false, 300, "", false, false, 0, false, false, false);
            $pdf->SetXY(10, 280);
            $pdf->Write(20, $content);
            $pdf->SetXY(170, 280);
            $pdf->Write(20, $url);
        });
        self::SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        self::SetHeaderMargin(PDF_MARGIN_HEADER);
        self::SetFooterMargin(PDF_MARGIN_FOOTER);
        self::SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        self::setImageScale(PDF_IMAGE_SCALE_RATIO);
        self::SetFont('helvetica', '', 12);
    }

}
