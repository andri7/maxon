<?php
class User_model extends CI_Model {

private $primary_key='user_id';
private $table_name='user';

function __construct(){
	parent::__construct();
}
	function get_paged_list($limit=10,$offset=0,
	$order_column='',$order_type='asc')
	{
                $nama='';
                if(isset($_GET['nama'])){
                    $nama=$_GET['nama'];
                }
                if($nama!='')$this->db->where("username like '%$nama%'");

		if (empty($order_column)||empty($order_type))
		$this->db->order_by($this->primary_key,'asc');
		else
		$this->db->order_by($order_column,$order_type);
		return $this->db->get($this->table_name,$limit,$offset);
	}
	function count_all(){
		return $this->db->count_all($this->table_name);
	}
	function get_by_id($id){
		$this->db->where($this->primary_key,$id);
		return $this->db->get($this->table_name);
	}
        function info($id){
            $data=$this->get_by_id($id)->row();
            if(count($data)){    
                $ret='<br/><strong>'.$id.' - '.$data->username.'</strong><br/>'
                        .$data->cid.'<br/>';
            } else $ret='';
            return $ret;
        }
	function save($data){
		$this->db->insert($this->table_name,$data);
		return $this->db->insert_id();
	}
	function update($id,$data){
		$jobs=$data['jobs'];
		unset($data['jobs']);
		$this->db->where($this->primary_key,$id);
		$this->db->update($this->table_name,$data);
		if($jobs){
			$this->load->model('user_jobs_model');
			$data_jobs['jobs']=$jobs;
			$data_jobs['user_id']=$id;
			$this->user_jobs_model->update($id,$data_jobs);
		}
		
	}
	function delete($id){
		$this->db->where($this->primary_key,$id);
		$this->db->delete($this->table_name);
		$this->user_jobs_model->delete_by_user($id);
	}
 function get_login_info($user_id)
 {
	 $this->db->where('$user_id', $user_id);
	 $this->db->limit(1);
	 $query = $this->db->get($this->table);
	 return ($query->num_rows() > 0) ? $query->row() : FALSE;
 }
}