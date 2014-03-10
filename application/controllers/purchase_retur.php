<?php if(!defined('BASEPATH')) exit('No direct script access allowd');

class Purchase_retur extends CI_Controller {
        private $limit=10;
        private $sql="select purchase_order_number,po_date,amount, 
                i.supplier_number,c.supplier_name,c.city,i.warehouse_code
                from purchase_order i
                left join suppliers c on c.supplier_number=i.supplier_number
                where i.potype='R'";
        private $controller='purchase_retur';
        private $primary_key='nomor_bukti';
        private $file_view='purchase/retur';
        private $table_name='purchase_order';
	function __construct()
	{
		parent::__construct();
 		$this->load->helper(array('url','form','browse_select','mylib_helper'));
        $this->load->library('sysvar');
        $this->load->library('javascript');
        $this->load->library('template');
		$this->load->library('form_validation');
		$this->load->model('purchase_order_model');
		$this->load->model('supplier_model');
		$this->load->model('inventory_model');
		 
	}
	function set_defaults($record=NULL){
            $data=data_table($this->table_name,$record);
            $data['mode']='';
            $data['message']='';
            if($record==NULL)$data['purchase_order_number']=$this->nomor_bukti();
			$data['po_date']= date("Y-m-d");
            $data['potype']='R';
			return $data;
	}
	function nomor_bukti($add=false)
	{
		$key="Retur Pembelian Numbering";
		if($add){
		  	$this->sysvar->autonumber_inc($key);
		} else {			
			return $this->sysvar->autonumber($key,0,'!PR~$00001');
		}
	}
	function index()
	{	
            
            $this->browse();
           
	}
	function get_posts(){
            $data=data_table_post($this->table_name);
            return $data;
	}
	function save(){
		$this->load->model('purchase_order_lineitems_model');
		$this->_set_rules();
		 if ($this->form_validation->run()=== TRUE){
			$data=$this->get_posts();
            $data['potype']='R';
			//-- save header --- //
			$nomor=$this->nomor_bukti();
			$data['purchase_order_number']=$nomor;
			$this->purchase_order_model->save($data);
			$this->nomor_bukti(true);
			//-- save detail --- //
			$qty=$this->input->post('qty');
			$line=$this->input->post('line_number');
			 
			for($i=0;$i<count($qty);$i++){
				if($qty[$i]>0){
					$rpoline=$this->purchase_order_lineitems_model->get_by_id($line[$i])->result_array();
					if($rpoline[0]){
						$rpoline['purchase_order_number']=$nomor;
						unset($rpoline[0]['line_number']);
						$this->purchase_order_lineitems_model->save($rpoline[0]);
					}
				}
			}
			$this->purchase_order_model->recalc($nomor);
            header('location: '.base_url().'index.php/purchase_retur/view/'.$nomor);
         }
	}
	function add($nomor_faktur)
	{
		$this->load->model('purchase_order_lineitems_model');
	    $data=$this->set_defaults();
		$data['mode']='add';
		$data['message']='';
        $data['supplier_list']=$this->supplier_model->select_list();
		$faktur=$this->purchase_order_model->get_by_id($nomor_faktur)->row();
		$data['supplier_number']=$faktur->supplier_number;
		$data['supplier_info']=$this->supplier_model->info($faktur->supplier_number);
		$data['po_ref']=$nomor_faktur;
		$data['items']=$this->purchase_order_lineitems_model->lineitems($nomor_faktur);
		$this->template->display_form_input('purchase/retur_proses',$data,'');			
	}
	function update()
	{
		 $data=$this->set_defaults();
		 $this->_set_rules();
		 $id=$this->input->post('purchase_order_number');
		 if ($this->form_validation->run()=== TRUE){
			$data=$this->get_posts();
		 	$data['potype']='R';
			$this->purchase_order_model->update($id,$data);
            $message='Update Success';
		} else {
			$message='Error Update';
		}
                
 		$this->view($id,$message);		
	}
	 
        
	function view($id,$message=null){
		 $data['id']=$id;
		 $model=$this->purchase_order_model->get_by_id($id)->result_array();
		 $data=$this->set_defaults($model[0]);
		 $data['mode']='view';
         $data['message']=$message;
         $data['supplier_list']=$this->supplier_model->select_list();  
         $data['supplier_info']=$this->supplier_model->info($data['supplier_number']);
		 $this->session->set_userdata('_right_menu','');
         $this->session->set_userdata('purchase_order_number',$id);
         $this->template->display('purchase/retur',$data);                 
	}
   
	function _set_rules(){	
		 $this->form_validation->set_rules('purchase_order_number','Nomor Bukti Retur', 'required|trim');
		 $this->form_validation->set_rules('po_date','Tanggal','callback_valid_date');
	}
    function browse($offset=0,$limit=50,$order_column='purchase_order_number',$order_type='asc'){
		$data['controller']=$this->controller;
		$data['fields_caption']=array('Nomor Bukti','Tanggal','Jumlah','Kode Supplier','Nama Supplier','Kota','Gudang');
		$data['fields']=array('purchase_order_number','po_date','amount', 
                'supplier_number','supplier_name','city','warehouse_code');
		$data['field_key']='purchase_order_number';
		$data['caption']='DAFTAR PURCHASE RETUR';

		$this->load->library('search_criteria');
		
		$faa[]=criteria("Dari","sid_date_from","easyui-datetimebox");
		$faa[]=criteria("S/d","sid_date_to","easyui-datetimebox");
		$faa[]=criteria("Nomor BUkti","sid_po_number");
		$faa[]=criteria("Supplier","sid_supplier");
		$data['criteria']=$faa;
        $this->template->display_browse2($data);            
    }
    function browse_data($offset=0,$limit=10,$nama=''){
    	if($this->input->get('sid_po_number')){
    		$sql=$this->sql." and purchase_order_number='".$this->input->get('sid_po_number')."'";
		} else {
			$d1= date( 'Y-m-d H:i:s', strtotime($this->input->get('sid_date_from')));
			$d2= date( 'Y-m-d H:i:s', strtotime($this->input->get('sid_date_to')));
			$sql=$this->sql." and po_date between '".$d1."' and '".$d2."'";
			if($this->input->get('sid_supplier'))$sql.=" and supplier_name like '".$this->input->get('sid_supplier')."%'";
		}
        echo datasource($sql);
    }	 
  
	function delete($id){
	 	$this->purchase_order_model->delete($id);
        $this->browse();
	}
	function lineitems($nomor){
		$this->load->model('purchase_order_lineitems_model');
		echo $this->purchase_order_lineitems_model->browse($nomor);
    }
    function add_item(){            
        if(!$this->input->get('purchase_order_number')){
        	echo "Nomor bukti tidak diisi.";
			return false;
		}
        $data['purchase_order_number']=$this->input->post('purchase_order_number');
        $this->load->model('inventory_model');
        $data['item_lookup']=$this->inventory_model->item_list();
        $this->load->view('purchase/purchase_retur_add_item',$data);
    }   
        function save_item(){
        	if(!$this->input->post('item_number')){
        		echo "Pilih nama barang !";return false;
        	} 
            $this->load->model('purchase_order_lineitems_model');
            $item_no=$this->input->post('item_number');
            $data['purchase_order_number']=$this->input->post('purchase_order_number');
            $data['item_number']=$item_no;
            $data['quantity']=$this->input->post('quantity');
            $data['description']=$this->inventory_model->get_by_id($data['item_number'])->row()->description;
            $data['unit']=$this->input->post('unit');
            $data['price']=$this->input->post('price');
            $data['total_price']=$data['quantity']*$data['price'];
            $this->purchase_order_lineitems_model->save($data);
        }        
        function delete_item($id){
            $this->load->model('purchase_order_lineitems_model');
            return $this->purchase_order_lineitems_model->delete($id);
        }        
        function print_retur($nomor){
            $this->load->helper('mylib_helper');
            $this->load->model('suppliers_model');
            $invoice=$this->purchase_order_model->get_by_id($nomor)->row();
            $data['purchase_order_number']=$invoice->purchase_order_number;
            $data['po_date']=$invoice->po_date;
            $data['supplier_number']=$invoice->supplier_number;
            $data['amount']=$invoice->amount;
            $data['terms']=$invoice->terms;
            $caption='';
            $sql="select item_number,description,quantity,unit,price,amount 
                from purchase_order_lineitems i
                where purchase_order_number='".$nomor."'";
            $caption='';$class='';$field_key='';$offset='0';$limit=100;
            $order_column='';$order_type='asc';
            $item=browse_select($sql, $caption, $class, $field_key, $offset, $limit, 
                        $order_column, $order_type,false);
            $data['lineitems']=$item;
            $data['supplier_info']=$this->suppliers_model->info($data['supllier_number']);
            $data['header']=company_header();            
            $this->load->view('purchase_invoice_print',$data);
        }
		function add_jurnal($purchase_order_number)
		{
			
		}
}