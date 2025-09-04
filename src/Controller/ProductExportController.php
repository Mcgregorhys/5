<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ProductExportController extends AbstractController
{
    #[Route('/products/export/{format}', name: 'products_export')]
    public function export(string $format, ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

//export do PDF
        if ($format === 'pdf')
             {
                

        $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', true);
            $options->set('chroot', $this->getParameter('kernel.project_dir') . '/public');
            $dompdf = new Dompdf($options);

             // Renderowanie HTML z Twig
        $html = $this->renderView('product/pdf.html.twig', [
            'products' => $products,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="products.pdf"',
            ]
        );
    }
//******************************************************************************* */

        if ($format === 'xls') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Nazwa');
            $sheet->setCellValue('C1', 'Cena');

            $row = 2;
            foreach ($products as $product) {
                $sheet->setCellValue('A' . $row, $product->getId());
                $sheet->setCellValue('B' . $row, $product->getNazwaProduktu());
                $sheet->setCellValue('C' . $row, $product->getCenaNetto());
                 // 🔹 Wstawienie obrazka
                if ($product->getImageFilename()) {
                    $drawing = new Drawing();
                    $drawing->setPath('uploads/products/' . $product->getImageFilename());
                    $drawing->setHeight(50); // wysokość w px
                    $drawing->setCoordinates('D' . $row);
                    $drawing->setWorksheet($sheet);
                }
                $row++;
            }

            $writer = new Xlsx($spreadsheet);
            $temp_file = tempnam(sys_get_temp_dir(), 'products');
            $writer->save($temp_file);

            return new Response(
                file_get_contents($temp_file),
                200,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="products.xlsx"',
                ]
            );
        }

        // fallback np. CSV
        if ($format === 'csv') {
            $response = new Response();
            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="products.csv"');

            $handle = fopen('php://output', 'w+');
            fputcsv($handle, ['ID', 'Nazwa', 'Cena'], ';');

            foreach ($products as $product) {
                fputcsv($handle, [$product->getId(), $product->getNazwaProduktu(), $product->getCenaNetto()], ';');
            }

            fclose($handle);
            return $response;
        }

        throw $this->createNotFoundException("Nieobsługiwany format: $format");
    }
}
