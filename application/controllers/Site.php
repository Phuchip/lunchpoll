<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Site extends CI_Controller {

    function __construct()
	{
		parent::__construct();
		$this->load->model('Site_model');
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		Globals::checkLogin();
	}
    
	public function index()
	{
		$data['arrFood'] = self::getDataPollFoodUser();

		$data['title'] = 'Hôm nay ăn gì ?';
		$data['content'] = 'site/home';
		$this->load->view('site/index',$data);
    }

	function check_login()
	{
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$output = array('error' => false);
		$login = $this->Site_model->check_login($email, $password);
		if($login){
			if (empty($login->avatar)) {
				$characterName = Globals::getCharacterName($login->username);
				$avatar = Globals::make_avatar($login->id,$characterName);
				$this->Site_model->update_data('user',['avatar'=>$avatar],['id'=>$login->id]);
			}else{
				$avatar = $login->avatar;
			}
			$user = [
				'id'	=> $login->id,
				'username' => $login->username,
				'avatar'=> $avatar,
				'active'=> 1,
				'loginType'=> 'account'
			];
			$this->session->set_userdata('user',$user);
			Globals::setCookie('user_id',$this->encryption->encrypt($login->id));
			Globals::setCookie('user_email',$this->encryption->encrypt($login->email));
			$this->Site_model->update_data('user',['active'=>1],['id'=>$login->id]);
			$output['message'] = 'Đăng nhập thành công. Đang chuyển hướng...';
		}
		else{
			$output['error'] = true;
			$output['message'] = 'Sai thông tin đăng nhập. Vui lòng kiểm tra lại!';
		}
		echo json_encode($output);
	}
	function register()
	{
		$username = $this->input->post('username');
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$repassword = $this->input->post('repassword');
		$output = array('error' => false);
		$query = $this->db->where(['username'=>$username])->or_where(['email'=>$email])->get('user');
		if ($password != $repassword) {
			$output['error'] = true;
			$output['message'] = 'Mật khẩu nhập không giống nhau !';
		}elseif ($query->num_rows() > 0) {
			$output['error'] = true;
			$output['message'] = 'Tài khoản đã được sử dụng!';
		}else {
			$register = $this->Site_model->register($username,$email,$password);
			$characterName = Globals::getCharacterName($username);
			$avatar = Globals::make_avatar($this->db->insert_id(),$characterName);
			$this->Site_model->update_data('user',['avatar'=>$avatar],['id'=>$this->db->insert_id()]);
			if ($register) {
				$output['message'] = 'Đăng ký tài khoản thành công. Vui lòng đăng nhập...';
			}else{
				$output['message'] = 'Có lỗi xảy ra xin vui lòng thử lại sau...';
			}
		}
		echo json_encode($output);
	}
	function logout()
	{
		$this->Site_model->update_data('user',['active'=>0],['id'=>$this->session->user['id']]);
		$this->session->unset_userdata('user');
		Globals::unsetCookie(['user_id','user_email']);
		redirect();
	}

	public function today_result()
	{
		$data['result']= $this->db->query('SELECT pd.id,f.name as name,f.description FROM `poll_date` pd LEFT JOIN food f ON f.id = pd.food_id WHERE pd.date = "'.date('Y-m-d').'" ORDER BY total DESC LIMIT 1 ')->row();
		$data['title'] = 'Kết quả hôm nay';
		$data['content'] = 'site/today_result';
		$this->load->view('site/index',$data);
	}

	function getDataPollFoodUser()
	{
		$totalPoll = $this->db->select('SUM(total) AS total')->where(['date'=>date('Y-m-d')])->get('poll_date')->row()->total;
		$dataPoll = $this->db->query('SELECT f.id as id,f.name as name,f.image as image,pd.total as total,pd.poll_by as poll_by FROM food f LEFT JOIN (SELECT total,poll_by,food_id FROM poll_date WHERE `date` = "'.date('Y-m-d').'") as pd ON pd.food_id = f.id WHERE f.status = 1 GROUP BY f.id ORDER BY total DESC')->result();
		foreach ($dataPoll as $key => $value) {
			if ($value->poll_by) {
				$dataUser = $this->db->query('SELECT `id`,`username`, `avatar` FROM `user` WHERE `id` IN('.$value->poll_by.')')->result_array();
				$dataUser = array_column($dataUser,null,'id');
			}else{
				$dataUser = [];
			}
			$percent = $value->total?round(($value->total/$totalPoll)*100,2):0;
			$data[$value->id] = [
				'food_id' => $value->id,
				'percent' => $percent.'%',
				'name' => $value->name,
				'image' => $value->image,
				'user' => $dataUser
			];
		}
		return $data;
	}

	function setcookie($name,$value,$exprire = null)
	{
		$exprire =$exprire ? $exprire : time() + (86400 * 30);
		$cookie = array(
			'name'   => $name,
			'value'  => $value,                            
			'expire' => (int)$exprire,                                                                                   
			'secure' => TRUE
		);
		$this->input->set_cookie($cookie);
	}
}

/* End of file Site.php */
/* Location: ./application/controllers/Site.php */