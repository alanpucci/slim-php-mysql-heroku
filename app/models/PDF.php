<?php
use Fpdf\Fpdf;
class PDF
{
    public function hacerPDF($lista){
        
        $pdf = new Fpdf(); 
        $pdf->AddPage();

        $pdf->SetFont('Helvetica','',16);
        $pdf->Cell(60,4,'Alan Ezequiel Pucci',0,1,'C');
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(60,4,'La comanda',0,1,'C');
        $pdf->Cell(60,4,'Listado de comandas',0,1,'C');
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(25,10, 'Fecha creacion', 1);
        $pdf->Cell(15,10, 'Mesa', 1);
        $pdf->Cell(20,10, 'Cliente', 1);
        $pdf->Cell(25,10, 'Estado', 1);
        $pdf->Cell(25,10, 'Preparacion(min)', 1);
        $pdf->Cell(70,10, 'Pedidos', 1);
        $pdf->Ln();
        
        // PRODUCTOS
        foreach ($lista as $item) {
            $pdf->Cell(25,10, $item->fecha_creacion, 1);
            $pdf->Cell(15,10, $item->mesa, 1);
            $pdf->Cell(20,10, $item->nombre_cliente, 1);
            $pdf->Cell(25,10, $item->estado, 1);
            $pdf->Cell(25,10, $item->tiempo_preparacion, 1);
            $stringPedidos="";
            foreach ($item->pedidos as $key) {
                $stringPedidos = $stringPedidos.$key["nombre"]."(".$key["cantidad"]."), ";
            }
            $pdf->Cell(70,10, $stringPedidos, 1);
            $pdf->Ln();
        }
        $pdf->Output($this->destinoPDF(),'f');
        $pdf->Output($this->destinoPDF(),'i');
        return;
    }

    public function destinoPDF(){
        if(!file_exists("Comandas/")){
            mkdir("Comandas/",0777,true);
        }
        $date = new DateTime("now");
        $tiempoAhora = $date->format('Y-m-d-H_i_s');
        $nombreArchivo = "listaComandas_".$tiempoAhora.".pdf";
        $destino = "Comandas/".$nombreArchivo;
        return $destino;
    }
}