<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require FCPATH.'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class Home extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('home_model');
	}

	public function index()
	{
		$this->load->view('index');
	}
	public function spreadsheet_format_download()
	{
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="hello_world.xlsx"');
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A1', 'S.No');
		$sheet->setCellValue('B1', 'Product Name');
		$sheet->setCellValue('C1', 'Quantity');
		$sheet->setCellValue('D1', 'Price');

		$writer = new Xlsx($spreadsheet);
		$writer->save("php://output");
	}
	public function spreadsheet_import()
	{
		$upload_file=$_FILES['upload_file']['name'];
		$extension=pathinfo($upload_file,PATHINFO_EXTENSION);
		if($extension=='csv')
		{
			$reader= new \PhpOffice\PhpSpreadsheet\Reader\Csv();
		} else if($extension=='xls')
		{
			$reader= new \PhpOffice\PhpSpreadsheet\Reader\Xls();
		} else
		{
			$reader= new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		}
		$spreadsheet=$reader->load($_FILES['upload_file']['tmp_name']);
		$sheetdata=$spreadsheet->getActiveSheet()->toArray();
		$sheetcount=count($sheetdata);
		if($sheetcount>1)
		{
			$data=array();
			for ($i=1; $i < $sheetcount; $i++) { 
				$product_name=$sheetdata[$i][1];
				$product_qty=$sheetdata[$i][2];
				$product_price=$sheetdata[$i][3];
				$data[]=array(
					'product_name'=>$product_name,
					'product_quantity'=>$product_qty,
					'product_price'=>$product_price,
				);
			}
			$inserdata=$this->home_model->insert_batch($data);
			if($inserdata)
			{
				$this->session->set_flashdata('message','<div class="alert alert-success">Successfully Added.</div>');
				redirect('home');
			} else {
				$this->session->set_flashdata('message','<div class="alert alert-danger">Data Not uploaded. Please Try Again.</div>');
				redirect('home');
			}
		}
	}
	public function spreadsheet_export()
	{
		//fetch my data
		$productlist=$this->home_model->product_list();
		
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="product.xlsx"');
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A1', 'S.No');
		$sheet->setCellValue('B1', 'Product Name');
		$sheet->setCellValue('C1', 'Quantity');
		$sheet->setCellValue('D1', 'Price');
		$sheet->setCellValue('E1', 'Subtotal');

		$sn=2;
		foreach ($productlist as $prod) {
			//echo $prod->product_name;
			$sheet->setCellValue('A'.$sn,$prod->product_id);
			$sheet->setCellValue('B'.$sn,$prod->product_name);
			$sheet->setCellValue('C'.$sn,$prod->product_quantity);
			$sheet->setCellValue('D'.$sn,$prod->product_price);
			$sheet->setCellValue('E'.$sn,'=C'.$sn.'*D'.$sn);
			$sn++;
		}
		//TOTAL
		$sheet->setCellValue('D8','Total');
		$sheet->setCellValue('E8','=SUM(E2:E'.($sn-1).')');

		$writer = new Xlsx($spreadsheet);
		$writer->save("php://output");
	}
}
