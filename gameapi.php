
<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require(APPPATH . '/libraries/REST_Controller.php');

class App extends REST_Controller {

    function __construct() {

        error_reporting(0);
        parent::__construct();
        $this->baseurl = $this->config->item('base_url');    // App base URL
        $this->load->model('common_model');                    // Adding your model to use for api
    }

    /**
     * ****************************************************************
     *    Function Name :    check_empty                             *
     *    Functionality : To check whether the field is empty or not.*
     *    @param          : $data,$message,$numeric                  *
     * ****************************************************************
     * */
    public function check_empty($data, $message = '', $numeric = false) {
        $message = (!empty($message)) ? $message : 'Invalid data';
        if (empty($data)) {

            $data = array('status' => 0, 'message' => $message);
            $this->response($data, 200);
        }
        if ($numeric) {
            if (!is_numeric($data)) {
                $data = array('status' => 0, 'message' => 'Invalid data');
                $this->response($data, 200);
            }
        }
    }

    /**
     * *********************************************************************************
     *    Function Name :    check_integer_empty                                      * 
     *    Functionality : To check whether the field is empty or not for numeric data.*
     *    @param          : $data,$message,$numeric                                   *
     * *********************************************************************************
     * */
    public function check_integer_empty($data, $message = '') {
        $message = (!empty($message)) ? $message : 'Invalid data';

        if (is_numeric($data)) {
            
        } else {
            $data = array('status' => 0, 'message' => $message);
            $this->response($data, 200);
        }
    }

    /**
     * ******************************************************************
     * Function Name :    is_email_exists                              * 
     * Functionality : To check whether the email already exist or not.*
     * @param          : column name                                   *
     * @return      : input data                                       *
     * ******************************************************************
     * */
    public function is_email_exists($column_name) {
        return $this->common_model->is_exists(__FUNCTION__, 'ws_users', array('email' => $column_name));
    }

    /**
     * *******************************************************************************
     * Function Name : registration                                                 *
     * Functionality : Register user                                                *
     * @author       : pratibha sinha                                               *
     * @param        : file   profile_pic   profile pic for user                    *
     * @param        : string fullname      fullname of the user                    *
     * @param        : string email         email id of the user                    *
     * @param        : string phone         phone number of the user                *
     * @param        : int    celebrity     whether user is celebrity               *
     * @param        : string user_type     1 = normal user , -1 = social user      *
     * @param        : string password      password of the user                    *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function registration_post() {
        $this->load->helper(array('file'));
        $this->load->library('upload');

        $first_name = $this->input->post('first_name');
        $this->check_empty($first_name, 'Please add first_name');

        $last_name = $this->input->post('last_name');
        $this->check_empty($last_name, 'Please add last_name');

        $gender = $this->input->post('gender');
        
        $fullname = $first_name . ' ' . $last_name;

        $email_val = $this->input->post('email');
        $this->check_empty($email_val, 'Please add email');

        $phone = $this->input->post('phone');
        
        $celebrity = $this->input->post('celebrity');
        $this->check_integer_empty($celebrity, 'Please add celebrity value');

        /* if the form is valid */
        if ($this->form_validation->run('user_signup')) {

            $email = strtolower($email_val);

            /* check if email already exists in users table */
            $user_exist_data = $this->common_model->findWhere($table = 'ws_users', array('email' => $email, 'activated' => 1), $multi_record = false, $order = '');

            /* if the user exists */
            if (isset($user_exist_data) && (!empty($user_exist_data))) {
                if ($user_exist_data['email'] != '') {
                    /* user already exists */
                    $data = array(
                        'status' => 0,
                        'message' => 'Email id already exists'
                    );
                    $this->response($data, 200);
                }
            }

            /* if the user phone exists */
            /* check if email already exists in users table */
            $phone_exist_data = $this->common_model->findWhere($table = 'ws_users', array('phone' => $phone, 'activated' => 1), $multi_record = false, $order = '');
            if (isset($phone_exist_data) && (!empty($phone_exist_data))) {
                if ($phone_exist_data['phone'] != '') {
                    /* user already exists */
                    $data = array(
                        'status' => 0,
                        'message' => 'Phone no. already exists'
                    );
                    $this->response($data, 200);
                }
            }

            /* if the image file exists as input parameter */

            if (isset($_FILES['profile_pic']['name'])) {
                //provide config values
                $file_name = $_FILES['profile_pic']['name'];
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);

                $config['upload_path'] = './uploads/profile';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '500000';
                $config['max_width'] = '52400';
                $config['max_height'] = '57680';
                $config['file_name'] = 'profile' . rand() . '.' . $ext;

                $this->upload->initialize($config);

                //if the profile pic could not be uploaded
                if (!$this->upload->do_upload('profile_pic')) {
                    print_r($this->upload->display_errors());
                    $this->session->set_flashdata('errors', $this->upload->display_errors());
                } else {
                    $profile_path = "uploads/profile/" . $config['file_name'];
                }
            }

            $password = $this->input->post('password');
            $this->check_empty($password, 'Please add password');

            $post_data = array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'gender' => (!empty($gender) ? $gender : ''),
                'fullname' => $fullname,
                'email' => $email,
                'phone' => (!empty($phone) ? $phone : ''),
                'created' => date('Y-m-d h:i'),
                'password' => md5($password),
                'profile_pic' => (!empty($profile_path) ? $profile_path : ''),
                'celebrity' => $celebrity
            );


            $last_id = $this->common_model->add('ws_users', $post_data);
            if ($last_id) {

                //sound notification set
                $sound_post_data = array('user_id' => $last_id);
                $sound_id = $this->common_model->add('ws_sound_notification', $sound_post_data);

                $data = array(
                    'status' => 1,
                    'message' => 'Registration Successful',
                    'user_id' => $last_id
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Unknown Error: Unable to Register.'
                );
            }
        } else {
            $error = strip_tags(validation_errors());
            $error = str_replace(PHP_EOL, '', $error);
            $data = array(
                'status' => 0,
                'message' => $error
            );
        }
        $this->response($data, 200);
    }

    public function registration_new_post() {

        $deviceType = $this->input->post('deviceType');
        $this->check_empty($deviceType, 'Please add deviceType');

        $deviceToken = $this->input->post('deviceToken');
        
        $this->load->helper(array('file'));
        $this->load->library('upload');

        $unique_name = $this->input->post('unique_name');
        $this->check_empty($unique_name, 'Please add unique_name');

        $fullname = $this->input->post('fullname');
        $this->check_empty($fullname, 'Please add fullname');

        $gender = $this->input->post('gender');
        
        $email_val = $this->input->post('email');
        
        $phone = $this->input->post('phone');
        
        /* check if email already exists in users table */
        if (!empty($email_val)) {
            $email = strtolower($email_val);
            $user_exist_data = $this->common_model->findWhere($table = 'ws_users', array('email' => $email, 'activated' => 1), $multi_record = false, $order = '');
            if (isset($user_exist_data) && (!empty($user_exist_data))) {
                if ($user_exist_data['email'] != '') {
                    /* user already exists */
                    $data = array(
                        'status' => 401,
                        'message' => 'Email id already exists'
                    );
                    $this->response($data, 200);
                }
            }
        } else {
            $email = '';
        }


        /* check if email already exists in users table */
        $unique_name = strtolower($unique_name);
        $unique_name_data = $this->common_model->findWhere($table = 'ws_users', array('unique_name' => $unique_name, 'activated' => 1), $multi_record = false, $order = '');

        /* if the user exists */
        if (isset($unique_name_data) && (!empty($unique_name_data))) {
            if ($unique_name_data['unique_name'] != '') {
                /* user already exists */
                $data = array(
                    'status' => 402,
                    'message' => 'unique_name already exists'
                );
                $this->response($data, 200);
            }
        }

        /* check if phone already exists in users table */
        if (!empty($phone)) {
            $phone_exist_data = $this->common_model->findWhere($table = 'ws_users', array('phone' => $phone, 'activated' => 1), $multi_record = false, $order = '');
            if (isset($phone_exist_data) && (!empty($phone_exist_data))) {
                if ($phone_exist_data['phone'] != '') {
                    /* user already exists */
                    $data = array(
                        'status' => 0,
                        'message' => 'Phone no. already exists'
                    );
                    $this->response($data, 200);
                }
            }
        }
        /* if the image file exists as input parameter */

        if (isset($_FILES['profile_pic']['name'])) {
            //provide config values
            $file_name = $_FILES['profile_pic']['name'];
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);

            $config['upload_path'] = './uploads/profile';
            $config['allowed_types'] = 'gif|jpg|png';
            $config['max_size'] = '500000';
            $config['max_width'] = '52400';
            $config['max_height'] = '57680';
            $config['file_name'] = 'profile' . rand() . '.' . $ext;

            $this->upload->initialize($config);

            //if the profile pic could not be uploaded
            if (!$this->upload->do_upload('profile_pic')) {
                print_r($this->upload->display_errors());
                $this->session->set_flashdata('errors', $this->upload->display_errors());
            } else {
                $profile_path = "uploads/profile/" . $config['file_name'];
            }
        }

        $password = $this->input->post('password');
        $this->check_empty($password, 'Please add password');

        $hashToStoreInDb = password_hash($password, PASSWORD_BCRYPT);

        $token = $this->generateRandomString();
        $login_name = $first_name . $last_name . $token;

        $post_data = array(
            'deviceType' => (!empty($deviceType) ? $deviceType : ''),
            'deviceToken' => (!empty($deviceToken) ? $deviceToken : ''),
            'gender' => (!empty($gender) ? $gender : ''),
            'fullname' => urldecode($fullname),
            'email' => (!empty($email) ? $email : ''),
            'phone' => (!empty($phone) ? $phone : ''),
            'unique_name' => $unique_name,
            'created' => date('Y-m-d h:i'),
            'password' => $hashToStoreInDb,
            'md5_pwd' => 0,
            'profile_pic' => (!empty($profile_path) ? $profile_path : ''),
        );
        $this->db->insert('ws_users', $post_data);
        $last_id = $this->db->insert_id();

        if ($last_id) {

            //default follow start
            $this->follow_friend_common($last_id);
            //default follow end

            //default badge add
            if($deviceType == 'ios')
            {
                $this->db->insert('ws_pushnotifications', array('device_token' => $deviceToken , 'user_id' => $last_id));
            }
            //default badge add

            //sound notification set
            $sound_post_data = array('user_id' => $last_id);
            $sound_id = $this->common_model->add('ws_sound_notification', $sound_post_data);

            //notification set
            $this->db->insert('ws_notification_set', $post_data = array('user_id' => $last_id));

            $base_url = $this->baseurl;

            /* check if the credentials exist */
            $user_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $last_id), $multi_record = false, $order = '');
            //chk notification set
            $user_notification_data = $this->common_model->findWhere($table = 'ws_notification_set', $where_data = array('user_id' => $last_id), $multi_record = false, $order = '');

            $data = array(
                'status' => 1,
                'message' => 'Registration Successful',
                'user_id' => $last_id,
                'email_address' => (!empty($user_data['email']) ? $user_data['email'] : ''),
                'unique_name' => (!empty($user_data['unique_name']) ? $user_data['unique_name'] : ''),
                'fullname' => (!empty($user_data['fullname']) ? $user_data['fullname'] : ''),
                'profile_type' => (!empty($user_data['profile_type']) ? $user_data['profile_type'] : ''),
                'profile_pic' => (!empty($user_data['profile_pic']) ? $base_url . $user_data['profile_pic'] : ''),
                'post_notification' => (isset($user_notification_data['post']) ? $user_notification_data['post'] : ''),
                'vote_notification' => (isset($user_notification_data['vote']) ? $user_notification_data['vote'] : ''),
                'comment_notification' => (isset($user_notification_data['comment']) ? $user_notification_data['comment'] : ''),
                'tag_notification' => (isset($user_notification_data['tag']) ? $user_notification_data['tag'] : ''),
                'group_notification' => (isset($user_notification_data['group']) ? $user_notification_data['group'] : ''),
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Unknown Error: Unable to Register.'
            );
        }
        $this->response($data, 200);
    }

    public function change_pwd_post()
    {
        
        /*$users = $this->db->order_by('id', 'desc')->get_where('ws_users' , array('md5_pwd' => 1))->result_array();
        //echo $this->db->last_query();die;
        foreach($users as $user)
        {
            $id = $user['id']; 
            $password = $user['password1'];
            $hashToStoreInDb = password_hash($password, PASSWORD_BCRYPT);
            $this->db->where('id' , $id);
            $this->db->update('ws_users', array('password' =>$hashToStoreInDb));
            $Data[] = array('password' => $password , 'hash_pwd' => $hashToStoreInDb);
        }
        
        $data = array(
            'status' => 1,
            'message' => 'Success',
            'data' => $Data
        );
        $this->response($data, 200);*/

        $id = 1477;
        $password = 12345;
        $hashToStoreInDb = password_hash($password, PASSWORD_BCRYPT);
        $this->db->where('id' , $id);
        $this->db->update('ws_users', array('password' =>$hashToStoreInDb , 'md5_pwd' => 0));
        $data = array('password' => $password , 'hash_pwd' => $hashToStoreInDb);
        $this->response($data, 200);
    }

    public function edit_profile_type_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $profile_type = $this->input->post('profile_type');
        $this->check_empty($profile_type, 'Please add profile_type');

        $user_detail = $this->db->get_where('ws_users' , array('id' => $user_id))->row_array();
        if($user_detail)
        {
            $this->db->where('id',$user_id);
            $this->db->update('ws_users', array('profile_type' =>$profile_type));
            $data = array(
                'status' => 1,
                'message' => 'Success'
            );
        }
        else
        {
            $data = array(
                'status' => 0,
                'message' => 'user id does not exist'
            );
        }    
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name :    login                                                     *
     * Functionality :    login user(normal and social users)                       *
     * @author       :    pratibha sinha                                            * 
     * @param        :    string   email                                            *
     * @param        :    string   password   password                              *
     * revision 0    :    author changes_made                                       *
     * *******************************************************************************
     * */
    public function login_post() {
        $deviceType = $this->input->post('deviceType');
        $this->check_empty($deviceType, 'Please add deviceType');

        $deviceToken = $this->input->post('deviceToken');
        
        $login_id = $this->input->post('login_id');
        $this->check_empty($login_id, 'Please enter login_id');

        $password = $this->input->post('password');
        $this->check_empty($password, 'Please enter password');

        $where_data = array();
        $this->db->where("(email='" . $login_id . "' OR LOWER(unique_name)='" . strtolower($login_id) . "') AND activated = 1 ", NULL, FALSE);
        
        /* check if the credentials exist */
        $user_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');
        if (empty($user_data)) {
            $data = array(
                'status' => 0,
                'message' => 'Please enter the valid details'
            );
            $this->response($data, 200); // 200 being the HTTP response code
        } else {
            //set devicetoken and devicetype

            $passwordIsOldFlag = $user_data['md5_pwd'];
            $existingHashFromDb = $user_data['password'];

            $passwordCompare = ($passwordIsOldFlag == 1)? md5($password): $password;
            
            $isPasswordCorrect = password_verify($passwordCompare, $existingHashFromDb);
            if($isPasswordCorrect == false)
            {
                $data = array(
                    'status' => 0,
                    'message' => 'Wrong password'
                );
                $this->response($data, 200); // 200 being the HTTP response code
            }
            $this->db->where('id',$user_data['id']);
            $this->db->update('ws_users', array(
                                          'deviceType' =>$deviceType ,
                                          'deviceToken' => (!empty($deviceToken) ? $deviceToken : '') ));

            $base_url = $this->baseurl;

            //default badge add
            if($deviceType == 'ios')
            {
                $this->db->insert('ws_pushnotifications', array('device_token' => $deviceToken , 'user_id' => $user_data['id']));
            }
            //default badge add

            //chk notification set
            $user_notification_data = $this->common_model->findWhere($table = 'ws_notification_set', $where_data = array('user_id' => $user_data['id']), $multi_record = false, $order = '');
            $data = array(
                'status' => 1,
                'user_id' => $user_data['id'],
                'email_address' => (!empty($user_data['email']) ? $user_data['email'] : ''),
                'unique_name' => (!empty($user_data['unique_name']) ? $user_data['unique_name'] : ''),
                'fullname' => $user_data['fullname'],
                'profile_type' => (!empty($user_data['profile_type']) ? $user_data['profile_type'] : ''),
                'profile_pic' => (!empty($user_data['profile_pic']) ? $base_url . $user_data['profile_pic'] : ''),
                'post_notification' => (isset($user_notification_data['post']) ? $user_notification_data['post'] : ''),
                'vote_notification' => (isset($user_notification_data['vote']) ? $user_notification_data['vote'] : ''),
                'comment_notification' => (isset($user_notification_data['comment']) ? $user_notification_data['comment'] : ''),
                'tag_notification' => (isset($user_notification_data['tag']) ? $user_notification_data['tag'] : ''),
                'group_notification' => (isset($user_notification_data['group']) ? $user_notification_data['group'] : ''),
            );
        $this->response($data, 200); // 200 being the HTTP response code
        }
    }

    public function logout_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        //set devicetoken and devicetype
        $this->db->where('id',$user_id);
        $this->db->update('ws_users', array('deviceType' =>'' , 'deviceToken' => '' ));

        $data = array(
                'status' => 1,
                'message' => 'success'
                );
        $this->response($data, 200); // 200 being the HTTP response code
    }

    public function update_deviceToken_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $deviceToken = $this->input->post('deviceToken');
        $this->check_empty($deviceToken, 'Please enter deviceToken');

        //set devicetoken
        $this->db->where('id',$user_id);
        $this->db->update('ws_users', array('deviceToken' => $deviceToken ));

        $data = array(
                'status' => 1,
                'message' => 'success'
                );
        $this->response($data, 200); // 200 being the HTTP response code
    }

    public function update_badgecount_post()
    {
        $deviceToken = $this->input->post('deviceToken');
        $this->check_empty($deviceToken, 'Please enter deviceToken');

        //set devicetoken
        $this->db->where('device_token',$deviceToken);
        $this->db->update('ws_pushnotifications', array('badgecount' => 0 ));

        $data = array(
                'status' => 1,
                'message' => 'success'
                );
        $this->response($data, 200); // 200 being the HTTP response code
    }

    public function update_countrycode_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $country_code = $this->input->post('country_code');
        $this->check_empty($country_code, 'Please enter country_code');

        $country_label = $this->input->post('country_label');
        $this->check_empty($country_label, 'Please enter country_label');

        //set devicetoken
        $this->db->where('id',$user_id);
        $this->db->update('ws_users', array('country_code' => $country_code ,'country_label' => $country_label));

        $data = array(
                'status' => 1,
                'message' => 'success'
                );
        $this->response($data, 200); // 200 being the HTTP response code
    }

    public function get_filename($profile_pic)
    {
        $homepage = file_get_contents($profile_pic);
        $file_name = 'profile' . time() . '.jpg';
        $profile_path = "uploads/profile/" . $file_name;
        $handle = fopen($profile_path,"w+");
        fputs($handle, $homepage);
        fclose($handle);
        return $profile_path;
    }

    public function login_facebook_post()
    {
        $deviceType = $this->input->post('deviceType');
        $this->check_empty($deviceType, 'Please add deviceType');

        $deviceToken = $this->input->post('deviceToken');
        
        $base_url = $this->baseurl;
        $social_id = $this->input->post('social_id');
        $this->check_empty($social_id, 'Please enter social_id');

        $fullname = $this->input->post('fullname');
        $this->check_empty($fullname, 'Please enter fullname');

        $profile_pic = $this->input->post('profile_pic');
        //$this->check_empty($profile_pic, 'Please enter profile_pic');

        $email_val = $this->input->post('email');
        
        $gender = $this->input->post('gender');
        $social_type = $this->input->post('social_type');
        //$this->check_empty($social_type, 'Please enter social_type');
        
        /* check if phone already exists in users table */
        
        if($social_type == '')
        {
            $social_type = 'facebook';
        }

        if($profile_pic != '')
        {
            $profile_path = $this->get_filename($profile_pic);
        }
        
        $socialid_exist_data = $this->common_model->findWhere($table = 'ws_users', array('social_id' => $social_id, 'social_type' => $social_type, 'activated' => 1), $multi_record = false, $order = '');
        if (isset($socialid_exist_data) && (!empty($socialid_exist_data))) {
            if ($socialid_exist_data['social_id'] != '') {
                
                if (!empty($email_val)) {
                    $email = strtolower($email_val);
                    if($email == $socialid_exist_data['email'])
                    {

                    }
                    else
                    {
                        $user_exist_data = $this->common_model->findWhere($table = 'ws_users', array('email' => $email, 'activated' => 1), $multi_record = false, $order = '');
                        if (isset($user_exist_data) && (!empty($user_exist_data))) {
                            if ($user_exist_data['email'] != '') {
                                /* user already exists */
                                $data = array(
                                    'status' => 0,
                                    'message' => 'Email id already exists'
                                );
                                $this->response($data, 200);
                            }
                        }
                    }
                } else {
                    $email = '';
                }

                /* user already exists */
                $last_id = $socialid_exist_data['id'];
                $this->db->where('id',$last_id);
                $this->db->update('ws_users', array(
                                               // 'fullname' => urldecode($fullname),
                                                //'social_id' => $social_id,
                                                //'social_type' => $social_type,
                                                //'unique_name' => '',
                                               // 'created' => date('Y-m-d h:i'),
                                               // 'profile_pic' => (!empty($profile_path) ? $profile_path : ''),
                                                'email' => (!empty($email) ? $email : '')
                                                //'gender' => (!empty($gender) ? $gender : ''),
                                                ));
            }
        }
        else
        {
            /* check if email already exists in users table */
            if (!empty($email_val)) {
                $email = strtolower($email_val);
                $user_exist_data = $this->common_model->findWhere($table = 'ws_users', array('email' => $email, 'activated' => 1), $multi_record = false, $order = '');
                if (isset($user_exist_data) && (!empty($user_exist_data))) {
                    if ($user_exist_data['email'] != '') {
                        /* user already exists */
                        $data = array(
                            'status' => 0,
                            'message' => 'Email id already exists'
                        );
                        $this->response($data, 200);
                    }
                }
            } else {
                $email = '';
            }

            $post_data = array(
            'fullname' => urldecode($fullname),
            'social_id' => $social_id,
            'social_type' => $social_type,
            'unique_name' => '',
            'created' => date('Y-m-d h:i'),
            'profile_pic' => (!empty($profile_path) ? $profile_path : ''),
            'email' => (!empty($email) ? $email : ''),
            'gender' => (!empty($gender) ? $gender : ''),
            );
            $this->db->insert('ws_users', $post_data);
            $last_id = $this->db->insert_id();

            //sound notification set
            $sound_post_data = array('user_id' => $last_id);
            $sound_id = $this->common_model->add('ws_sound_notification', $sound_post_data);

            //notification set
            $this->db->insert('ws_notification_set', $post_data = array('user_id' => $last_id));
        }    
        /* if the image file exists as input parameter */

        if ($last_id) {

            //set devicetoken and devicetype
            $this->db->where('id',$last_id);
            $this->db->update('ws_users', array(
                                            'deviceType' =>$deviceType ,
                                            'deviceToken' => (!empty($deviceToken) ? $deviceToken : '')
                                            ));


            //default follow start
            $this->follow_friend_common($last_id);
            //default follow end

            /* check if the credentials exist */
            $user_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $last_id), $multi_record = false, $order = '');
            //chk notification set
            $user_notification_data = $this->common_model->findWhere($table = 'ws_notification_set', $where_data = array('user_id' => $last_id), $multi_record = false, $order = '');

            $data = array(
                'status' => 1,
                'user_id' => $last_id,
                'email_address' => (!empty($user_data['email']) ? $user_data['email'] : ''),
                'unique_name' => (!empty($user_data['unique_name']) ? $user_data['unique_name'] : ''),
                'fullname' => (!empty($user_data['fullname']) ? $user_data['fullname'] : ''),
                'profile_type' => (!empty($user_data['profile_type']) ? $user_data['profile_type'] : ''),
                'profile_pic' => (!empty($user_data['profile_pic']) ? $base_url.$user_data['profile_pic'] : ''),
                'post_notification' => (isset($user_notification_data['post']) ? $user_notification_data['post'] : ''),
                'vote_notification' => (isset($user_notification_data['vote']) ? $user_notification_data['vote'] : ''),
                'comment_notification' => (isset($user_notification_data['comment']) ? $user_notification_data['comment'] : ''),
                'tag_notification' => (isset($user_notification_data['tag']) ? $user_notification_data['tag'] : ''),
                'group_notification' => (isset($user_notification_data['group']) ? $user_notification_data['group'] : ''),
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Please enter the valid details'
            );
        }
        $this->response($data, 200);
    }

    public function updateEmail_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $email_val = $this->input->post('email');
        $this->check_empty($email_val, 'Please enter email');
        //echo $email_val;
        $user_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $user_id), $multi_record = false, $order = '');
        if($user_data)
        {
            /* check if email already exists in users table */
            if (!empty($email_val)) {
                $email = strtolower($email_val);
                $user_exist_data = $this->common_model->findWhere($table = 'ws_users', array('email' => $email, 'activated' => 1), $multi_record = false, $order = '');
                if (isset($user_exist_data) && (!empty($user_exist_data))) {
                    if ($user_exist_data['email'] != '') {
                        /* user already exists */
                        $data = array(
                            'status' => 0,
                            'message' => 'Email id already exists'
                        );
                        $this->response($data, 200);
                    }
                }
            } else {
                $email = '';
            }

            $this->db->where('id',$user_id);
            $this->db->update('ws_users', array(
                                            'email' => $email
                                            ));

            $data = array(
                'status' => 1,
                'message' => 'success'
            );

        }else {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exists'
            );
        }
        $this->response($data, 200);
    }

    function imagecreatefromfile( $filename )
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('File "'.$filename.'" not found.');
        }
        switch ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ))) {
            case 'jpeg':
            case 'jpg':
                return imagecreatefromjpeg($filename);
            break;

            case 'png':
                return imagecreatefrompng($filename);
            break;

            case 'gif':
                return imagecreatefromgif($filename);
            break;

            default:
                throw new InvalidArgumentException('File "'.$filename.'" is not valid jpg, png or gif image.');
            break;
        }
    }

    /**
     * *******************************************************************************
     * Function Name :    update_profile                                            *
     * Functionality :    Updates the user profile                                  *
     * @author       :    pratibha sinha                                            *
     * @param        :    int      id           user id                             *
     * @param        :    string   fullname     user's fullname                     *
     * @param        :    file     profile_pic  user's phone                        *
     * revision 0    :    author changes_made                                       *
     * *******************************************************************************
     * */
    public function update_profile_post() {
        $base_url = $this->baseurl;
        $this->load->helper(array('file'));
        $this->load->library('upload');

        $id = $this->input->post('id');
        $this->check_empty($id, 'Please enter id');

        $fullname = $this->input->post('fullname');

        $unique_name = $this->input->post('unique_name');

        @$file_name = $_FILES['profile_pic'];

        $where_data = array('id' => $id, 'activated' => 1);
        /* get the user data */
        $exist_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');

        /* if the user exists */
        if ($exist_user_data) {

            /*check unique_name alraedy exist*/

            /*if($exist_user_data['unique_name'] == '')
            {
                $alreadyexist_unique_name = $this->common_model->findWhere($table = 'ws_users', $where_data = array('unique_name' => $unique_name), $multi_record = false, $order = '');
                if (isset($alreadyexist_unique_name) && (!empty($alreadyexist_unique_name)))
                {
                        $data = array(
                            'status' => 0,
                            'message' => 'unique name already exists'
                        );
                        $this->response($data, 200);
                }
            }*/

            /* check if email already exists in users table */
            $unique_name = strtolower($unique_name);
            $unique_name_data = $this->common_model->findWhere($table = 'ws_users', array('unique_name' => $unique_name, 'activated' => 1), $multi_record = false, $order = '');

            /* if the user exists */
            if (isset($unique_name_data) && (!empty($unique_name_data))) {
                if ($unique_name_data['unique_name'] != '') {
                    /* user already exists */
                    $data = array(
                        'status' => 402,
                        'message' => 'unique_name already exists'
                    );
                    $this->response($data, 200);
                }
            }
            

            /* if the profile pic exists as input parameter */
            if (isset($_FILES['profile_pic']['name'])) {
                $file_name = $_FILES['profile_pic']['name'];
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);

                $config['upload_path'] = './uploads/profile';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '500000';
                $config['max_width'] = '52400';
                $config['max_height'] = '57680';
                $config['file_name'] = 'profile' . rand() . '.' . $ext;

                $this->upload->initialize($config);
                /* if profile pic uploading fails */
                if (!$this->upload->do_upload('profile_pic')) {
                    //echo 'jkk';print_r($this->upload->display_errors());die;
                    $data = array(
                        'status' => 0,
                        'message' => 'Profile pic could not be updated'
                    );
                    $this->response($data, 200);
                    /* if the profile pic is uploaded */
                } else {
                    $profile_path = "uploads/profile/" . $config['file_name'];
                }
            }

            $post_data = Array(
                'fullname' => (!empty($fullname) ? $fullname : $exist_user_data['fullname']),
                'unique_name' => (empty($exist_user_data['unique_name']) ? $unique_name : $exist_user_data['unique_name']),
                'profile_pic' => ((isset($profile_path)) && (!empty($profile_path)) ? $profile_path : $exist_user_data['profile_pic'])
            );

            /* update profile */
            $where_data = array('id' => $id, 'activated' => 1);
            if ($this->common_model->updateWhere($table = 'ws_users', $where_data, $post_data)) {
                $updated_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');

                $data = array(
                    'status' => 1,
                    'message' => 'Profile updated successfully',
                    'profile_pic' => (!empty($updated_data['profile_pic']) ? $base_url . $updated_data['profile_pic'] : ''),
                    'email_address' => (!empty($updated_data['email']) ? $updated_data['email'] : '')
                );
                /* if update profile fails */
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Unable to update profile'
                );
            }
            /* if the user does not exist */
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exist'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name :    generateRandomString                                      *
     * Functionality :    generates a random string according to length parameters  *
     * @param        :    int  length  length of the generated random string        *
     * revision 0    :    author changes_made                                       *
     * *******************************************************************************
     * */
    function generateRandomString($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * *******************************************************************************
     * Function Name :    forgot_password                                           *
     * Functionality :    resets and sends the new password to the user by email    *
     * @author       :    pratibha sinha                                            *
     * @param        :    string  email  email id of user                           *
     * revision 0    :    author changes_made                                       *
     * *******************************************************************************
     * */
    public function forgot_password_post() {
        $email = $this->input->post('email');
        $this->check_empty($email, 'Please enter email');

        /* generate random token */
        $token = $this->generateRandomString();

        /* load email library */
        $email_config = $this->config->item('smtp');
        $this->load->library('email', $email_config);

        $where_data = array('email' => $email, 'activated' => 1);
        $user_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = FALSE, $select = array());
        /* if the user exist */
        if ($user_data) {

            $data['fullname'] = $user_data['fullname'];
            $data['email'] = $user_data['email'];

            $this->email->from('Admin@mybestestapp.com', 'Bestest');
            $this->email->to($email);
            $this->email->subject('Requested Password');

            $message = 'Dear ' . $data['fullname'] . ',<br><br>
                        Below is the token.<br><br>
                        Verification token is ' . $token;

            $this->email->message($message);

            /* if the mail is sent */
            if ($this->email->send()) {

                $post_data = Array(
                    'verify_token' => $token
                );

                $where_data = array('email' => $email, 'activated' => 1);
                $this->common_model->updateWhere($table = 'ws_users', $where_data, $post_data);

                $data = array(
                    'status' => 1,
                    'message' => 'if the given email id is registered you will receive the new password otherwise create an account'
                );
                /* if the email is not sent */
            } else {
                //echo $this->email->print_debugger();die;
                $data = array(
                    'status' => 0,
                    'message' => 'if the given email id is registered you will receive the new password otherwise create an account'
                );
            }
            /* if the email does not exist */
        } else {
            $data = array(
                'status' => 0,
                'message' => 'if the given email id is registered you will receive the new password otherwise create an account'
            );
        }
        $this->response($data, 200); // 200 being the HTTP response code
    }

    /**
     * *******************************************************************************
     * Function Name : get_profile                                                  *
     * Functionality : view profile                                                 *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function get_profile_post() {
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $where_data = array('id' => $user_id, 'activated' => 1);
        $exist_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');


        if (!empty($exist_user_data['id'])) {

            $following = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('user_id' => $user_id))->result_array();
            $following_count = count($following);
            $follower = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
            $follower_count = count($follower);

            //celebrity following and followers count

            if ($exist_user_data['celebrity'] == 0) {
                $follower = 0;
                $following_query = "select count(*) as count from ws_followers_celebrity where user_id = '$user_id'";
                $following_result = $this->common_model->getQuery($following_query);
                $following = $following_result[0]['count'];
            } else {
                $following = 0;
                $follower_query = "select count(*)  as count from ws_followers_celebrity where celebrity_id = '$user_id'";
                $follower_result = $this->common_model->getQuery($follower_query);
                $follower = $follower_result[0]['count'];
            }

            //friends detail

            $friend_query = "SELECT count(*) as count from ws_friend_list WHERE ( user_id = '$user_id' OR friend_id = '$user_id' ) AND status = 1 ORDER BY list_id DESC";
            $friend_data = $this->common_model->getQuery($friend_query);
            $friends = $friend_data[0]['count'];

            //posts detail

            $post_query = "SELECT count(*) as count from ws_posts WHERE user_id = '$user_id' AND status = 1 ORDER BY post_id DESC";
            $post_data = $this->common_model->getQuery($post_query);
            $posts = $post_data[0]['count'];

            //likes
            $this->db->select('p.post_id , i.image_id,i.likes,i.unlikes,sum(i.likes) as sum_likes');
            $this->db->from('ws_posts p', 'p.status = 1');
            $this->db->join('ws_images i', 'p.post_id = i.post_id');
            $this->db->where('p.user_id', $user_id);

            $likes = $this->db->get();
            $ret = $likes->row();
            $total_likes = $ret->sum_likes;
            
            $Data = array(
                'name' => $exist_user_data['fullname'],
                'email' => $exist_user_data['email'],
                'phone' => $exist_user_data['phone'],
                'profile_pic' => (!empty($exist_user_data['profile_pic']) ? $base_url . $exist_user_data['profile_pic'] : ''),
                'follower' => $follower,
                'following' => $following,
                'following_count' => (!empty($following_count) ? $following_count : 0),
                'follower_count' => (!empty($follower_count) ? $follower_count : 0),
                'friends' => $friends,
                'posts' => $posts,
                'likes' => (!empty($total_likes) ? $total_likes : 0)
            );

            $data = array(
                'status' => 1,
                'data' => $Data
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exists'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : get_profile_new                                              *
     * Functionality : view profile by other user for unblocked user                *
     * @author       : pratibha sinha                                               *
     * @param        : int    viewer_id                                             *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    /* new changes provide the following output followers(int) , following(int), user posts(int) */
    public function get_profile_new_post() {
        $limit = 10;

        $offset = $this->input->post('offset');
        $this->check_integer_empty($offset, 'Please add offset');

        $datetime1 = date('Y-m-d H:i:s', time());

        $base_url = $this->baseurl;
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';

        $viewer_id = $this->input->post('viewer_id');
        $this->check_empty($viewer_id, 'Please enter viewer_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $exist_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');

        if (!empty($exist_user_data['id'])) {
            $blockfriend_query = $this->common_model->findWhere($table = 'ws_block', array('user_id' => $viewer_id, 'friend_id' => $user_id), $multi_record = false, $order = '');
            if (empty($blockfriend_query)) {

                $following = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('user_id' => $user_id))->result_array();
                $following_count = count($following);
                $follower = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
                $follower_count = count($follower);

                //celebrity following and follower count

                if ($exist_user_data['celebrity'] == 0) {
                    $follower = 0;
                    $following_query = "select count(*) as count from ws_followers_celebrity where user_id = '$user_id'";
                    $following_result = $this->common_model->getQuery($following_query);
                    $following = $following_result[0]['count'];
                } else {
                    $following = 0;
                    $follower_query = "select count(*) as count from ws_followers_celebrity where celebrity_id = '$user_id'";
                    $follower_result = $this->common_model->getQuery($follower_query);
                    $follower = $follower_result[0]['count'];
                }

                //friends detail

                $friend_query = "SELECT count(*) as count from ws_friend_list WHERE ( user_id = '$user_id' OR friend_id = '$user_id' ) AND status = 1 ORDER BY list_id DESC";
                $friend_data = $this->common_model->getQuery($friend_query);
                $friends = $friend_data[0]['count'];
                //die;
                //posts detail

                $post_query = "SELECT count(*) as count from ws_posts WHERE user_id = '$user_id' AND status = 1 ORDER BY post_id DESC";
                $post_data = $this->common_model->getQuery($post_query);
                $posts = $post_data[0]['count'];

                //likes
                $this->db->select('p.post_id , i.image_id,i.likes,i.unlikes,sum(i.likes) as sum_likes');
                $this->db->from('ws_posts p', 'p.status = 1');
                $this->db->join('ws_images i', 'p.post_id = i.post_id');
                $this->db->where('p.user_id', $user_id);

                $likes = $this->db->get();
                $ret = $likes->row();
                $total_likes = $ret->sum_likes;

                //friend request
                //check already send friend request
                $send_request = 0;
                $friendrequest1 = $this->db->get_where('ws_friend_request', array('sender_id' => $viewer_id, 'receiver_id' => $user_id, 'status' => 0))->row();
                $friendrequest2 = $this->db->get_where('ws_friend_request', array('sender_id' => $user_id, 'receiver_id' => $viewer_id, 'status' => 0))->row();
                if ($friendrequest1 OR $friendrequest2) {
                    $send_request = 1;
                }

                //chk viewer follow user or not
                $follow_chk = $this->common_model->findWhere($table = 'ws_follow', array('user_id' => $viewer_id, 'friend_id' => $user_id), $multi_record = false, $order = '');

                //user posts

                if($viewer_id == $user_id)
                {
                    $result = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $user_id, 'status' => '1') , $limit , $offset)->result_array();
                }
                else
                {
                    $result = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $user_id, 'share_with' => 'public','status' => '1') , $limit , $offset)->result_array();
                }    
                
                if (empty($result)) {
                    $result = array();
                } else {
                    foreach ($result as &$post) {

                        //comment count
                        $postID = $post['post_id'];
                        $poll_link = $base_url.'web/?id='.$postID;
                        $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
                        $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
                        $commentCount = $this->common_model->getQuery($comment_post);

                        $post['comment_count'] = $commentCount[0]['count'];
                        //post creator
                        $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                        $post['post_creator'] = (!empty($post_sender['fullname']) ) ? $post_sender['fullname'] : '';
                        $post['creator_pic'] = (!empty($post_sender['profile_pic']) ) ? $base_url . $post_sender['profile_pic'] : '';
                        $post['creator_unique_name'] = (!empty($post_sender['unique_name']) ) ? $post_sender['unique_name'] : '';
                        $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);
                        
                        //group_name 
                        $sharegroup_name = array();
                        if ($post['group_id'] != 0) {
                            foreach (explode(',', $post['group_id']) as $key => $value) {
                                $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                                $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                            }
                        } else {
                            $sharegroup_name = '';
                        }
                        $post['group_name'] = $sharegroup_name;
                        //frnd name
                        $sharefriend_name = array();
                        if ($post['friend_id'] != 0) {
                            foreach (explode(',', $post['friend_id']) as $key => $value) {
                                $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value, 'activated' => 1), $multi_record = false, $order = '');
                                $sharefriend_name[] = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                            }
                        } else {
                            $sharefriend_name = '';
                        }
                        $post['friend_name'] = $sharefriend_name;

                        //text data
                        $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                        $totalPostTextLikes =0;
                        foreach($text as $tx)
                        {
                            $totalPostTextLikes +=  $tx['likes'];
                        }
                        $total_textprop = 0;
                        $loop_textindex = 0;
                        if (count($text) > 0) {
                            $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();

                            //check like of user id
                            foreach ($post['text'] as &$text_likes) {
                                $loop_textindex++;
                                $likes_count = (int) $text_likes['likes'];

                                $unlikes_count = (int) $text_likes['unlikes'];
                                $text_id = (int) $text_likes['id'];

                                
                                //likes status
                                if ($likes_count > 0) {

                                    $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                                    if(count($text) == $loop_textindex && $loop_textindex > 1){
                                    $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                                    }else{
                                    $prop = round ( $likesproportion);
                                    $total_textprop += $prop;
                                    $text_likes['likes_proportion'] = $prop.'%';
                                    }
                                    //echo '1';die;
                                    //echo 'yguhu'.$img_id;die;
                                    $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $viewer_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $text_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_text_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.text_id', $text_id);

                                    $text_likes['likes_detail'] = $this->db->get()->result_array();
                                    if ($text_likes['likes_detail']) {
                                        foreach ($text_likes['likes_detail'] as &$detail_friend) {
                                            $fr_id = $detail_friend['user_id'];
                                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                            if ($like_frnd) {
                                                $detail_friend['is_friend'] = 1;
                                            } else {
                                                $detail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $text_likes['likes_detail'] = array();
                                    $text_likes['like_status'] = 'not liked';
                                    $text_likes['likes_proportion'] = '0%';
                                }

                                //unlikes array
                                if ($unlikes_count > 0) {

                                    $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $viewer_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $text_likes['unlike_status'] = $unlike_status;
                                    //unlikes array

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_text_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.text_id', $text_id);

                                    $text_likes['unlikes_detail'] = $this->db->get()->result_array();
                                    if ($text_likes['unlikes_detail']) {
                                        foreach ($text_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                            $fr_id = $unlikedetail_friend['user_id'];
                                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                            if ($unlike_frnd) {
                                                $unlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $unlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $text_likes['unlikes_detail'] = array();
                                    $text_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['text'] = array();
                        }

                        //images data

                        $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                        $totalPostImageLikes =0;
                        $totalPostImageUnlikes =0;
                        foreach($img as $im)
                        {
                            $totalPostImageLikes +=  $im['likes'];
                            $totalPostImageUnlikes +=  $im['unlikes'];
                        }
                        $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
                        $total_imgprop = 0;
                        $loop_imgindex = 0;
                        if (count($img) > 0) {
                            $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();

                            //check like of user id
                            //echo '<pre>';print_r($post['images']);die;
                            foreach ($post['images'] as &$images_likes) {
                                $loop_imgindex++;
                                $likes_count = (int) $images_likes['likes'];

                                $unlikes_count = (int) $images_likes['unlikes'];
                                $img_id = (int) $images_likes['image_id'];

                                
                                //likes status
                                if ($likes_count > 0) {
                                    $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                                    if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                                    $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                                    }else{
                                    $prop = round ( $likesproportion);
                                    $total_imgprop += $prop;
                                    $images_likes['likes_proportion'] = $prop.'%';
                                    }

                                    $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $viewer_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $images_likes['like_status'] = $like_status;
                                    

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.image_id', $img_id);

                                    $images_likes['likes_detail'] = $this->db->get()->result_array();
                                    if ($images_likes['likes_detail']) {
                                        foreach ($images_likes['likes_detail'] as &$detail_friend) {
                                            $fr_id = $detail_friend['user_id'];
                                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                            if ($like_frnd) {
                                                $detail_friend['is_friend'] = 1;
                                            } else {
                                                $detail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $images_likes['likes_detail'] = array();
                                    $images_likes['like_status'] = 'not liked';
                                    $images_likes['likes_proportion'] = '0%';
                                }

                                //unlikes array
                                $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                                if(count($img) == 1){
                                    $prop = round ( $unlikesproportion);
                                    $total_imgprop += $prop;
                                    $images_likes['unlikes_proportion'] = $prop.'%';
                                }
                                if ($unlikes_count > 0) {

                                    $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $viewer_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $images_likes['unlike_status'] = $unlike_status;
                                    //unlikes array

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.image_id', $img_id);

                                    $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                                    if ($images_likes['unlikes_detail']) {
                                        foreach ($images_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                            $fr_id = $unlikedetail_friend['user_id'];
                                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                            if ($unlike_frnd) {
                                                $unlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $unlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $images_likes['unlikes_detail'] = array();
                                    $images_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['images'] = array();
                        }
                        //videos data
                        $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

                        if (count($vid) > 0) {
                            $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                            foreach ($post['video'] as &$video_likes) {
                               
                               $likes_count = (int) $video_likes['likes'];

                                $unlikes_count = (int) $video_likes['unlikes'];
                                $vid_id = (int) $video_likes['video_id'];
                                
                                //likes status
                                if ($likes_count > 0) {
                                    $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $viewer_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $video_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_video_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.video_id', $vid_id);

                                    $video_likes['likes_detail'] = $this->db->get()->result_array();

                                    if ($video_likes['likes_detail']) {
                                        foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                            $fr_id = $vidlikedetail_friend['user_id'];
                                            $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                            if ($vidlike_frnd) {
                                                $vidlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $vidlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $video_likes['likes_detail'] = array();
                                    $video_likes['like_status'] = 'not liked';
                                }

                                //unlikes array
                               if ($unlikes_count > 0) {
                                    //unlikes array
                                    $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $viewer_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $video_likes['unlike_status'] = $unlike_status;

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_video_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.video_id', $vid_id);

                                    $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                                } else {
                                    $video_likes['unlikes_detail'] = array();
                                    $video_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['video'] = array();
                        }

                        //last comment data
                        $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();


                        if (count($last_comment) > 0) {
                            $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();
                            sort($post['last_comment']);

                            foreach ($post['last_comment'] as &$commentDetail) {
                                $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                                $commentDetail['sender_name'] = $comment_sender['fullname'];
                                $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                                $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);

                                $commentDetail['commentuser_name'] = array();
                                if (!empty($commentDetail['mention_users'])) {
                                    foreach (explode(',', $commentDetail['mention_users']) as $key => $value) {
                                        $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                                        $commentDetail['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                                    }
                                }
                            }
                        } else {
                            //$post['last_comment'] = json_decode('{}');
                            $post['last_comment'] = array();
                        }

                        //tag detail start
                        $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
                        if (count($tag) > 0) {
                            $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                            //check like of user id
                            foreach ($post['tagged_data'] as &$tags) {
                                $user_id_val = (int) $tags['user_id'];
                                if ($user_id_val > 0) {
                                    $tag_frnd_id = $user_id_val;
                                    //echo 'yguhu'.$img_id;die;
                                    $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                                    $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                                    $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                                    $tags['tag_frnd'] = $tag_frnd_name;
                                } else {
                                    $tags['tag_frnd'] = '';
                                }
                            }
                        } else {
                            $post['tagged_data'] = array();
                        }

                        $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                        if (count($tagwrd) > 0) {
                            $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                        } else {
                            $post['taggedword_data'] = array();
                        }
                    }
                }

                //user posts

                $Data = array(
                    'base_url' => $base_url,
                    'post_images_url' => $post_base_url,
                    'name' => $exist_user_data['fullname'],
                    'unique_name' => (!empty($exist_user_data['unique_name']) ? $exist_user_data['unique_name'] : ''),
                    'email' => $exist_user_data['email'],
                    //'phone' => $exist_user_data['phone'],
                    'profile_pic' => (!empty($exist_user_data['profile_pic']) ? $base_url . $exist_user_data['profile_pic'] : ''),
                    'profile_type' => (!empty($exist_user_data['profile_type']) ? $exist_user_data['profile_type'] : ''),
                    'follower' => (!empty($follower) ? $follower : 0),
                    'following' => (!empty($following) ? $following : 0),
                    'following_count' => (!empty($following_count) ? $following_count : 0),
                    'follower_count' => (!empty($follower_count) ? $follower_count : 0),
                    'friends' => (!empty($friends) ? $friends : 0),
                    'posts' => (!empty($posts) ? $posts : 0),
                    'likes' => (!empty($total_likes) ? $total_likes : 0),
                    'send_friend_request' => $send_request,
                    'follow' => (!empty($follow_chk) ? 'yes' : 'no'),
                    'data' => $result
                );

                $data = array(
                    'status' => 1,
                    'data' => $Data
                );
            } else {
                $data = array(
                    'status' => 1,
                    'message' => 'Blocked User'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User Id does not exists'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : add_friend                                                   *
     * Functionality : List of all users to add as a friend                         *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function add_new_friend_post() {
        error_reporting(0);
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $base_url = $this->baseurl;

        //user check
        $chk_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');
        if ($chk_user_data) {
            //contact check

            $frienddetailData = array();
            
            $user_query = "SELECT id FROM ws_users where (id != '$user_id' AND activated = 1)";
            $query = $this->db->query($user_query);
            $user_data = $query->result_array();

            //chk all users
            if ($user_data) {

                //get all friends of user_id
                $friend_id1 = $this->db->query("SELECT friend_id as id FROM ws_friend_list WHERE user_id = $user_id AND status = '1' ")->result_array();
                $friend_id2 = $this->db->query("SELECT user_id  as id FROM ws_friend_list WHERE friend_id = $user_id AND status = '1' ")->result_array();
                $friend_id = array_merge($friend_id1, $friend_id2);

                $tmp_all_friend_val = array();

                foreach ($friend_id as $fr) {
                    $tmp_all_friend_val[] = $fr['id'];
                }

                $tmp_arr1 = array();
                foreach ($user_data as $user) {

                    if (!in_array($user['id'], $tmp_all_friend_val)) {
                        $tmp_arr1[] = $user['id'];
                    }
                }
                
                foreach ($tmp_arr1 as $user) {
                    //echo $user;
                    $where_data = array('id' => $user, 'activated' => 1);
                    //$this->db->where("user_id='".$user_id."'", NULL, FALSE);
                    $detailuser_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');


                    //friend request
                    //check already send friend request
                    $send_request = 0;
                    $friendrequest1 = $this->db->get_where('ws_friend_request', array('sender_id' => $detailuser_data['id'], 'receiver_id' => $user_id, 'status' => 0))->row();
                    $friendrequest2 = $this->db->get_where('ws_friend_request', array('sender_id' => $user_id, 'receiver_id' => $detailuser_data['id'], 'status' => 0))->row();
                    if ($friendrequest1 OR $friendrequest2) {
                        $send_request = 1;
                    }

                    $user_detail[] = array(
                        'user_id' => $detailuser_data['id'],
                        'fullname' => $detailuser_data['fullname'],
                        'profile_pic' => (!empty($detailuser_data['profile_pic']) ? $base_url . $detailuser_data['profile_pic'] : ''),
                        'send_friend_request' => $send_request
                    );
                }

                $data = array(
                    'status' => '1',
                    'data' => $user_detail,
                );
            } else {
                $user_detail = array();
                $data = array(
                    'status' => '0',
                    'data' => $user_detail,
                );
            }
        } else {
            $data = array(
                'status' => '0',
                'message' => 'user id does not exist'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : add_friend                                                   *
     * Functionality : List of all users to add as a friend                         *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function unique_multidim_array($array, $key) {
        $temp_array = array();
        $i = 0;
        $key_array = array();

        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }

    public function add_friend_post() {
        error_reporting(0);
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $name_phone = json_decode($this->input->post('name_phone'));
        $this->check_empty($name_phone, 'Please enter name_phone');
        
        $base_url = $this->baseurl;

        //user check
        $chk_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');
        if ($chk_user_data) {
            
            //contact check

            $frienddetailData = array();
            if (!empty($name_phone)) {
                foreach ($name_phone as $contactArray) {
                    $nameVal = $contactArray->name;
                    $numberVal = $contactArray->number;
                    $chk_phone_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('phone' => $numberVal, 'activated' => 1), $multi_record = false, $order = '');
                    if ($chk_phone_data) {
                        $app_exist = 1;
                        $phone_user_id = $chk_phone_data['id'];
                    } else {
                        $app_exist = 0;
                        $phone_user_id = 0;
                    }
                    //friend request
                    //check already send friend request
                    $send_request = 0;
                    $friendrequest1 = $this->db->get_where('ws_friend_request', array('sender_id' => $phone_user_id, 'receiver_id' => $user_id, 'status' => 0))->row();
                    $friendrequest2 = $this->db->get_where('ws_friend_request', array('sender_id' => $user_id, 'receiver_id' => $phone_user_id, 'status' => 0))->row();
                    if ($friendrequest1 OR $friendrequest2) {
                        $send_request = 1;
                    }
                    $frienddetailData[] = array(
                        'user_id' => $phone_user_id,
                        'fullname' => (!empty($chk_phone_data['fullname']) ? $chk_phone_data['fullname'] : $nameVal),
                        'number' => $numberVal,
                        'has_app' => $app_exist,
                        'profile_pic' => (!empty($chk_phone_data['profile_pic']) ? $base_url . $chk_phone_data['profile_pic'] : ''),
                        'send_friend_request' => $send_request,
                        'email' => (!empty($chk_phone_data['email']) ? $chk_phone_data['email'] : ''),
                    );
                }
            }
            
            $user_query = "SELECT id FROM ws_users where (id != '$user_id' AND activated = 1)";
            $query = $this->db->query($user_query);
            $user_data = $query->result_array();

            //chk all users
            if ($user_data) {

                //get all friends of user_id
                $friend_id1 = $this->db->query("SELECT friend_id as id FROM ws_friend_list WHERE user_id = $user_id AND status = '1' ")->result_array();
                $friend_id2 = $this->db->query("SELECT user_id  as id FROM ws_friend_list WHERE friend_id = $user_id AND status = '1' ")->result_array();
                $friend_id = array_merge($friend_id1, $friend_id2);

                $tmp_all_friend_val = array();

                foreach ($friend_id as $fr) {
                    $tmp_all_friend_val[] = $fr['id'];
                }
                //check whether users are friends of user_id
                $tmp_arr1 = array();
                foreach ($user_data as $user) {

                    if (!in_array($user['id'], $tmp_all_friend_val)) {
                        //array_push($tmp_arr1 , $user['id']);
                        $tmp_arr1[] = $user['id'];
                    }
                }
                
                foreach ($tmp_arr1 as $user) {
                    $where_data = array('id' => $user, 'activated' => 1);
                    $detailuser_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');

                    //friend request
                    //check already send friend request
                    $send_request = 0;
                    $friendrequest1 = $this->db->get_where('ws_friend_request', array('sender_id' => $detailuser_data['id'], 'receiver_id' => $user_id))->row();
                    $friendrequest2 = $this->db->get_where('ws_friend_request', array('sender_id' => $user_id, 'receiver_id' => $detailuser_data['id']))->row();
                    if ($friendrequest1 OR $friendrequest2) {
                        $send_request = 1;
                    }

                    $user_detail[] = array(
                        'user_id' => $detailuser_data['id'],
                        'fullname' => $detailuser_data['fullname'],
                        'number' => $detailuser_data['phone'],
                        'has_app' => 1,
                        'profile_pic' => (!empty($detailuser_data['profile_pic']) ? $base_url . $detailuser_data['profile_pic'] : ''),
                        'send_friend_request' => $send_request,
                        'email' => (!empty($detailuser_data['email']) ? $detailuser_data['email'] : '')
                    );
                }
                $combine_result = array_values(array_merge($frienddetailData, $user_detail));
                $details = array_values($this->unique_multidim_array($combine_result, 'number'));
                $data = array(
                    'status' => '1',
                    'data' => $details,
                );
            } else {
                $user_detail = array();
                $combine_result = array_values(array_merge($frienddetailData, $user_detail));
                $details = array_values($this->unique_multidim_array($combine_result, 'number'));
                $data = array(
                    'status' => '0',
                    'data' => $details,
                );
            }
        } else {
            $data = array(
                'status' => '0',
                'message' => 'user id does not exist'
            );
        }

        $this->response($data, 200);
    }

    public function feedback_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $feedback = $this->input->post('feedback');
        $this->check_empty($feedback, 'Please enter feedback');

        $where_data = array('id' => $user_id);
        $exist_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');

        $name = $exist_user_data['fullname'];

        $email = $exist_user_data['email'];
        $admin = 'contact@mybestestapp.com';
        $config = array();
        $config['useragent'] = "CodeIgniter";
        $config['mailpath'] = "/usr/bin/sendmail"; // or "/usr/sbin/sendmail"
        $config['protocol'] = "smtp";
        $config['smtp_host'] = "localhost";
        $config['smtp_port'] = "25";
        $config['mailtype'] = 'html';
        $config['charset'] = 'utf-8';
        $config['newline'] = "\r\n";
        $config['wordwrap'] = TRUE;

        $this->load->library('email');

        $this->email->initialize($config);

        $this->email->from($email, $name);
        $this->email->to($admin);
        $this->email->subject('Bestest:Feedback');
        $msg = 'Dear Admin' . ',<br><br>
                        Below is the new feedback' . '<br><br>' .
                'Name: ' . $name . '<br><br>' .
                'Feedback: ' . $feedback . '<br><br>' .
                'Email: ' . $email;

        $this->email->message($msg);
        /* if the mail is sent */
        if ($this->email->send()) {
            $data = array(
                'status' => 1,
                'message' => 'Email sent'
            );
            /* if the email is not sent */
        } else {
            //echo $this->email->print_debugger();die;
            $data = array(
                'status' => 0,
                'message' => 'Email not sent'
            );
        }
        $this->response($data, 200);
    }

    public function invite_user_email_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $email_id = $this->input->post('email_id');
        $this->check_empty($email_id, 'Please enter email_id');

        $where_data = array('id' => $user_id);
        $exist_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');

        $from = $exist_user_data['email'];
        $name = $exist_user_data['fullname'];

        $base_url = $this->baseurl;
        $message = 'Dear User,<br><br>
                    Below is the invite link.<br><br>
                    Click <a href=' . $base_url . 'invite/>here</a>';


        $subject = 'BESTEST:Invite User';
        $email_config = $this->config->item('smtp');
        $this->load->library('email', $email_config);

        $this->email->from($from, $name);
        $this->email->to($email_id);

        $this->email->subject($subject);
        $this->email->message($message);

        if ($this->email->send()) {
            $data = array(
                'status' => 1,
                'message' => 'Invitation sent successfully to ' . $email_id
            );
        } else {
            //echo $this->email->print_debugger();
            $data = array(
                'status' => 0,
                'message' => 'Unable to send the invitation.Please try after sometime.'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : send_friend_request                                          *
     * Functionality : user is sending request to another user                      *
     * @author       : pratibha sinha                                               *
     * @param        : int    sender_id                                             *
     * @param        : int    receiver_id                                           *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function send_friend_request_post() {
        $sender_id = $this->input->post('sender_id');
        $this->check_empty($sender_id, 'Please enter sender_id');

        $receiver_id = $this->input->post('receiver_id');
        $this->check_empty($receiver_id, 'Please enter receiver_id');

        $friend1 = $this->db->get_where('ws_friend_list', array('user_id' => $sender_id, 'friend_id' => $receiver_id, 'status' => '1'))->row();
        $friend2 = $this->db->get_where('ws_friend_list', array('user_id' => $receiver_id, 'friend_id' => $sender_id, 'status' => '1'))->row();

        //check already friends
        if ($friend1 OR $friend2) {
            $data = array(
                'status' => 0,
                'message' => 'already friends'
            );
            $this->response($data, 200);
        } else {
            //check already send friend request
            $friendrequest1 = $this->db->get_where('ws_friend_request', array('sender_id' => $sender_id, 'receiver_id' => $receiver_id))->row();
            $friendrequest2 = $this->db->get_where('ws_friend_request', array('sender_id' => $receiver_id, 'receiver_id' => $sender_id))->row();
            if ($friendrequest1 OR $friendrequest2) {
                $data = array(
                    'status' => 0,
                    'message' => 'already send friend request'
                );
                $this->response($data, 200);
            } else {
                $post_data = array(
                    'sender_id' => $sender_id,
                    'receiver_id' => $receiver_id,
                    'added_at' => date('Y-m-d h:i')
                );

                $last_id = $this->common_model->add('ws_friend_request', $post_data);
                if ($last_id) {
                    $data = array(
                        'status' => 1,
                        'message' => 'Friend request sent',
                        'request_id' => $last_id
                    );
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'Unknown Error: Unable to send friend request.'
                    );
                }
                $this->response($data, 200);
            }
        }
    }

    /**
     * *******************************************************************************
     * Function Name : get_friend_request                                           *
     * Functionality : list friend request                                          *
     * @author       : pratibha sinha                                               *
     * @param        : int    receiver_id                                           *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function get_friend_request_post() {
        $base_url = $this->baseurl;

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $friend_list_query = "select * from ws_friend_request where (receiver_id = '$user_id' AND status = 0)";
        $request_data = $this->common_model->getQuery($friend_list_query);
        if ($request_data) {
            foreach ($request_data as $requestArray) {
                $detailuser_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $requestArray['sender_id'], 'activated' => 1), $multi_record = false, $order = '');
                $Data[] = array(
                    'sender_id' => $requestArray['sender_id'],
                    'sender_name' => $detailuser_data['fullname'],
                    'profile_pic' => (!empty($detailuser_data['profile_pic']) ? $base_url . $detailuser_data['profile_pic'] : ''),
                    'created_at' => $requestArray['added_at']
                );
            }
            $data = array(
                'status' => 1,
                'message' => $Data
            );
        } else {
            $Data = array();
            $data = array(
                'status' => 0,
                'message' => $Data
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : accept_reject_request                                        *
     * Functionality : Accepting/rejecting friend request                           *
     * @author       : pratibha sinha                                               *
     * @param        : int    sender_id                                             *
     * @param        : int    receiver_id                                           *
     * @param        : int    status  accept= 1,reject=-1                           *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function accept_reject_request_post() {
        $sender_id = $this->input->post('sender_id');
        $this->check_empty($sender_id, 'Please enter sender_id');

        $receiver_id = $this->input->post('receiver_id');
        $this->check_empty($receiver_id, 'Please enter receiver_id');

        $status = $this->input->post('status');
        $this->check_empty($status, 'Please enter status');

        $where_data = array('sender_id' => $sender_id, 'receiver_id' => $receiver_id);

        //if friend request accepted
        if ($status == 1) {
            $message = 'Friend request accepted';
            $friend_data = array(
                'user_id' => $sender_id,
                'friend_id' => $receiver_id
            );
            $last_id = $this->common_model->add('ws_friend_list', $friend_data);
            $update_data = array(
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id
            );
            if ($this->common_model->updateWhere('ws_friend_request', $update_data, $post_data = array('status' => 1))) {
                //echo $this->db->last_query();die;
                $data = array(
                    'status' => 1,
                    'message' => $message
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Error'
                );
            }
        } else {
            //if friend request rejected
            $message = 'Friend request rejected';
            //delete friend request entry from table
            $delete_data = array('sender_id' => $sender_id, 'receiver_id' => $receiver_id);
            if ($this->common_model->delete($table = 'ws_friend_request', $delete_data)) {
                $data = array(
                    'status' => 1,
                    'message' => $message
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Error'
                );
            }
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : unfriend                                                     *
     * Functionality : unfriend                                                     *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * @param        : int    friend_id                                             *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function unfriend_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please enter friend_id');
        $query = "Delete from ws_friend_list where (user_id = '$user_id' AND friend_id = '$friend_id') OR (user_id = '$friend_id' AND friend_id = '$user_id')";
        if ($this->db->query($query)) {

            $delete_request = "Delete from ws_friend_request where (sender_id = '$user_id' AND receiver_id = '$friend_id') OR (sender_id = '$friend_id' AND receiver_id = '$user_id')";
            $this->db->query($delete_request);
            $data = array(
                'status' => 1,
                'message' => 'Success'
            );
        } else {
            $data = array(
                'status' => 1,
                'message' => 'Error'
            );
        }
        $this->response($data, 200);
    }

    public function view_newsend_friendrequest_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $friend_list_query = "select * from ws_friend_request where (receiver_id = '$user_id')";
        $request_data = $this->common_model->getQuery($friend_list_query);
        if ($request_data) {
            foreach ($request_data as $requestArray) {
                $detailgetuser_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $requestArray['sender_id'], 'activated' => 1), $multi_record = false, $order = '');

                if ($requestArray['status'] == 0) {
                    $receive_status = 'receive_pending';
                } else {
                    $receive_status = 'receive_accepted';
                }
                $getData[] = array(
                    'sender_id' => $requestArray['sender_id'],
                    'sender_name' => $detailgetuser_data['fullname'],
                    'profile_pic' => (!empty($detailgetuser_data['profile_pic']) ? $base_url . $detailgetuser_data['profile_pic'] : ''),
                    'created_at' => $requestArray['added_at'],
                    'send' => 0,
                    'friend_request_status' => $receive_status
                );
            }
        } else {
            $getData = array();
        }
        $query = "select * from ws_friend_request where sender_id = '$user_id'";
        $result = $this->common_model->getQuery($query);

        if ($result) {
            foreach ($result as $result_val) {
                if ($result_val['status'] == 0) {
                    $send_status = 'send_pending';
                } else {
                    $send_status = 'send_accepted';
                }

                $detailsenduser_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $result_val['receiver_id'], 'activated' => 1), $multi_record = false, $order = '');
                $sendDATA[] = array(
                    'sender_id' => $result_val['receiver_id'],
                    'sender_name' => $detailsenduser_data['fullname'],
                    'profile_pic' => (!empty($detailsenduser_data['profile_pic']) ? $base_url . $detailsenduser_data['profile_pic'] : ''),
                    'created_at' => $result_val['added_at'],
                    'send' => 1,
                    'friend_request_status' => $send_status
                );
            }
        } else {
            $sendDATA = array();
        }

        $combine_result = array_merge((array) $getData, (array) $sendDATA);
        $data = array(
            'status' => 1,
            'data' => $combine_result
        );

        $this->response($data, 200);
    }

    public function view_send_friendrequest_post() {
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $query = "select * from ws_friend_request where sender_id = '$user_id'";
        $result = $this->common_model->getQuery($query);

        $friend_list_query = "select * from ws_friend_request where (receiver_id = '$user_id' AND status = 0)";
        $request_data = $this->common_model->getQuery($friend_list_query);
        if ($request_data) {
            foreach ($request_data as $requestArray) {
                $detailgetuser_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $requestArray['sender_id'], 'activated' => 1), $multi_record = false, $order = '');
                $getData[] = array(
                    'sender_id' => $requestArray['sender_id'],
                    'sender_name' => $detailgetuser_data['fullname'],
                    'profile_pic' => (!empty($detailgetuser_data['profile_pic']) ? $base_url . $detailgetuser_data['profile_pic'] : ''),
                    'created_at' => $requestArray['added_at'],
                    'send' => 0
                );
            }
        } else {
            $getData = array();
        }
        if ($result) {
            foreach ($result as $result_val) {
                $detailsenduser_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $result_val['receiver_id'], 'activated' => 1), $multi_record = false, $order = '');
                $sendDATA[] = array(
                    'sender_id' => $result_val['receiver_id'],
                    'sender_name' => $detailsenduser_data['fullname'],
                    'profile_pic' => (!empty($detailsenduser_data['profile_pic']) ? $base_url . $detailsenduser_data['profile_pic'] : ''),
                    'created_at' => $result_val['added_at'],
                    'send' => 1
                );
            }
        } else {
            $sendDATA = array();
        }
        $combine_result = array_merge((array) $getData, (array) $sendDATA);
        $data = array(
            'status' => 1,
            'data' => $combine_result
        );
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : manage_friend                                                *
     * Functionality : list of all friends                                          *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function manage_friend_post() {
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $friend_query = "SELECT * from ws_friend_list WHERE ( user_id = '$user_id' OR friend_id = '$user_id' ) AND status = 1 ORDER BY list_id DESC";
        $friend_data = $this->common_model->getQuery($friend_query);

        if (!empty($friend_data)) {
            foreach ($friend_data as $friendVal) {
                //echo '<pre>';print_r($friendVal);
                if ($friendVal['user_id'] == $user_id) {
                    $friend_id = $friendVal['friend_id'];
                }
                if ($friendVal['friend_id'] == $user_id) {
                    $friend_id = $friendVal['user_id'];
                }
                $detailuser_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $friend_id, 'activated' => 1), $multi_record = false, $order = '');
                if ($detailuser_data) {
                    $return_data[] = array(
                        'id' => $friend_id,
                        'fullname' => $detailuser_data['fullname'],
                        'profile_pic' => (!empty($detailuser_data['profile_pic']) ? $base_url . $detailuser_data['profile_pic'] : '')
                    );
                    $data = array(
                        'status' => 1,
                        'data' => $return_data
                    );
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => "Friend's list empty"
                    );
                }
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Error'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : create_group                                                 *
     * Functionality : create group                                                 *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * @param        : string    group_name                                         *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function create_group_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $group_name = $this->input->post('group_name');
        $this->check_empty($group_name, 'Please enter group_name');

        $post_data = array(
            'group_name' => $group_name,
            'group_owner' => $user_id,
            'status' => 1,
            'added_at' => date('Y-m-d h:i')
        );

        $last_id = $this->common_model->add('ws_groups', $post_data);
        if ($last_id) {

            //add group_owner as a member into the group
            $group_member_post_data = array(
                'group_id' => $last_id,
                'member_id' => $user_id,
                'status' => 1,
                'added_at' => date('Y-m-d h:i')
            );
            $group_member = $this->common_model->add('ws_group_members', $group_member_post_data);
            $group_member_post_data['group_name'] = $group_name;
            $data = array(
                'status' => 1,
                'message' => 'Created',
                'group_data' => $group_member_post_data
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Unable to add group'
            );
        }
        $this->response($data, 200);
    }

    public function edit_group_post() {
        $base_url = $this->baseurl;
        $this->load->helper(array('file'));
        $this->load->library('upload');

        $group_owner_id = $this->input->post('group_owner_id');
        $this->check_empty($group_owner_id, 'Please enter group_owner_id');

        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please enter group_id');

        $group_name = $this->input->post('group_name');
        
        $group_detail = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_id), $multi_record = false, $order = '');

        if ($group_detail) {
            if ($group_detail['group_owner'] == $group_owner_id) {

                if (isset($_FILES['profile_pic']['name'])) {
                    //provide config values
                    $file_name = $_FILES['profile_pic']['name'];
                    $ext = pathinfo($file_name, PATHINFO_EXTENSION);

                    $config['upload_path'] = './uploads/profile';
                    $config['allowed_types'] = 'gif|jpg|png';
                    $config['max_size'] = '500000';
                    $config['max_width'] = '52400';
                    $config['max_height'] = '57680';
                    $config['file_name'] = 'profile' . rand() . '.' . $ext;

                    $this->upload->initialize($config);

                    //if the profile pic could not be uploaded
                    if (!$this->upload->do_upload('profile_pic')) {
                        print_r($this->upload->display_errors());
                        $this->session->set_flashdata('errors', $this->upload->display_errors());
                    } else {
                        $profile_path = "uploads/profile/" . $config['file_name'];
                    }
                }

                $post_data = Array(
                    'group_name' => (!empty($group_name) ? $group_name : $group_detail['group_name']),
                    'profile_pic' => ((isset($profile_path)) && (!empty($profile_path)) ? $profile_path : $group_detail['profile_pic'])
                );

                /* update profile */
                $where_data = array('id' => $group_id);
                if ($this->common_model->updateWhere($table = 'ws_groups', $where_data, $post_data)) {
                    $updated_data = $this->common_model->findWhere($table = 'ws_groups', $where_data, $multi_record = false, $order = '');

                    $data = array(
                        'status' => 1,
                        'message' => 'Group image updated successfully',
                        'profile_pic' => (!empty($updated_data['profile_pic']) ? $base_url . $updated_data['profile_pic'] : '')
                    );
                    /* if update profile fails */
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'Unable to update profile'
                    );
                }
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Only admin can edit groups'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'group does not exist'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : add_group_members                                            *
     * Functionality : add members into the group                                   *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * @param        : int    member_id                                             *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function add_group_members_post() {
        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please enter group_id');

        $member_id = $this->input->post('member_id');
        $this->check_empty($member_id, 'Please enter member_id');

        $member_array = explode(",", $member_id);
        //check group member
        if ($member_array) {
            foreach ($member_array as $id) {
                $id_val = (int) $id;
                $member_check = "select * from ws_group_members where (group_id = '$group_id' AND member_id = '$id_val') ";
                $group_data = $this->common_model->getQuery($member_check);
                //check already member of a group
                if (empty($group_data)) {
                    $post_data = array(
                        'group_id' => $group_id,
                        'member_id' => $id_val,
                        'status' => 1,
                        'added_at' => date('Y-m-d h:i')
                    );
                    $group_member = $this->common_model->add('ws_group_members', $post_data);
                    if ($group_member) {
                        $data = array(
                            'status' => 1,
                            'message' => 'Success'
                        );
                    } else {
                        $data = array(
                            'status' => 0,
                            'message' => 'Unable to add group member'
                        );
                    }
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'Already added as a member'
                    );
                }
            }
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : get_combine_friend_celebrity_group                           *
     * Functionality : get details of friend,celebrity and group                    *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function get_combine_friend_celebrity_group_post() {
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $notification = $this->db->order_by('added_at', 'desc')->get_where('ws_notifications', array('receiver_id' => $user_id, 'status' => 0))->result_array();
        $notification_count = count($notification);

        //fetch friends
        $frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' or friend_id = '$user_id') AND status = 1";
        $frnd_data = $this->common_model->getQuery($frnd_query);

        //check friends
        if ($frnd_data) {
            foreach ($frnd_data as $frnd) {
                if ($frnd['user_id'] == $user_id) {
                    $id = $frnd['friend_id'];
                } else {
                    $id = $frnd['user_id'];
                }
                $frndexist_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $id, 'activated' => 1), $multi_record = false, $order = '');

                //check block status
                $blockfriend_query = $this->common_model->findWhere($table = 'ws_block', array('user_id' => $user_id, 'friend_id' => $id), $multi_record = false, $order = '');

                if ($blockfriend_query) {
                    $block_status = 'block';
                } else {
                    $block_status = 'unblock';
                }
                $FRIEND_DATA[] = array(
                    'friend_id' => $id,
                    'friend_name' => $frndexist_data['fullname'],
                    'friend_img_url' => (!empty($frndexist_data['profile_pic']) ? $base_url . $frndexist_data['profile_pic'] : ''),
                    'friend_block_status' => $block_status
                );
            }
        } else {
            $FRIEND_DATA = array();
        }

        //celebrity list
        $celebrity_query = "select * from ws_followers_celebrity where user_id = '$user_id'";
        $celebrity_data = $this->common_model->getQuery($celebrity_query);

        if ($celebrity_data) {
            foreach ($celebrity_data as $celebrity) {

                $celebrityData = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $celebrity['celebrity_id'], 'activated' => 1), $multi_record = false, $order = '');
                $CELEBRITY_DATA[] = array(
                    'celebrity_name' => $celebrityData['fullname'],
                    'celebrity_img_url' => (!empty($celebrityData['profile_pic']) ? $base_url . $celebrityData['profile_pic'] : ''),
                    'celebrity_id' => $celebrityData['id'],
                );
            }
        } else {
            $CELEBRITY_DATA = array();
        }

        //group list

        $group_query = "select wg.id,wg.group_id,wg.member_id from ws_group_members wg left join ws_group_post_latest wl on wg.group_id = wl.group_id join ws_groups wgp on wgp.id = wg.group_id where wg.member_id = '$user_id' ORDER BY wl.id desc";
        $group_data = $this->common_model->getQuery($group_query);
        //check whether user_id is following group
        if ($group_data) {
            foreach ($group_data as $group_data_val) {

                $gpData = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_data_val['group_id']), $multi_record = false, $order = '');
                $gpownerData = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $gpData['group_owner'], 'activated' => 1), $multi_record = false, $order = '');

                //group read start
                $gpread = $this->common_model->findWhere($table = 'ws_group_read_status', $where_data = array('group_id' => $gpData['id'], 'user_id' => $user_id), $multi_record = false, $order = '');
                //group read end

                 $readmsg = $this->db->get_where('ws_group_read_status', array('group_id' => $gpData['id'] ,'user_id' => $user_id))->result_array();
                 $readmsg_count = count($readmsg);

                $GROUP_DATA[] = array(
                    'group_id' => (!empty($gpData['id']) ? $gpData['id'] : ''),
                    'group_name' => (!empty($gpData['group_name']) ? $gpData['group_name'] : ''),
                    'group_image' => (!empty($gpData['profile_pic']) ? $base_url . $gpData['profile_pic'] : ''),
                    'group_owner_name' => (!empty($gpownerData['fullname']) ? $gpownerData['fullname'] : ''),
                    'group_read_status' => (isset($gpread['status']) ? $gpread['status'] : ''),
                    'group_unread_msg_count' => (isset($readmsg_count) ? $readmsg_count : '')
                );
            }
        } else {
            $GROUP_DATA = array();
        }
        $data = array(
            'status' => 1,
            'notification_count' => $notification_count,
            'friends' => $FRIEND_DATA,
            'celebrity' => $CELEBRITY_DATA,
            'groups' => $GROUP_DATA
        );
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : get_all_groups                                               *
     * Functionality : get all groups followed by user_id                           *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function get_all_groups_post() {
        $base_url = $this->baseurl;

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $group_query = "select wg.id,wg.group_id,wg.member_id,wl.id from ws_group_members wg left join ws_group_post_latest wl on wg.group_id = wl.group_id where wg.member_id = '$user_id' ORDER BY wl.id desc";
        $group_data = $this->common_model->getQuery($group_query);

        //check whether user_id is following group
        if ($group_data) {
            foreach ($group_data as $group_data_val) {

                $gpData = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_data_val['group_id']), $multi_record = false, $order = '');
                $gpownerData = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $gpData['group_owner'], 'activated' => 1), $multi_record = false, $order = '');

                //group read start
                $gpread = $this->common_model->findWhere($table = 'ws_group_read_status', $where_data = array('group_id' => $gpData['id'], 'user_id' => $user_id), $multi_record = false, $order = '');
                //group read end

                $readmsg = $this->db->get_where('ws_group_read_status', array('group_id' => $gpData['id'] ,'user_id' => $user_id))->result_array();
                $readmsg_count = count($readmsg);

                $GROUP_DATA[] = array(
                    'group_id' => (!empty($gpData['id']) ? $gpData['id'] : ''),
                    'group_name' => (!empty($gpData['group_name']) ? $gpData['group_name'] : ''),
                    'group_image' => (!empty($gpData['profile_pic']) ? $base_url . $gpData['profile_pic'] : ''),
                    'group_owner_id' => (!empty($gpownerData['fullname']) ? $gpownerData['fullname'] : ''),
                    'group_read_status' => (isset($gpread['status']) ? $gpread['status'] : ''),
                    'group_unread_msg_count' => (isset($readmsg_count) ? $readmsg_count : '')
                );
            }
        } else {
            $GROUP_DATA = array();
        }
        $data = array(
            'status' => 1,
            'groups' => $GROUP_DATA
        );
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : get_group                                                    *
     * Functionality : get group details created by group owner                     *
     * @author       : pratibha sinha                                               *
     * @param        : int    group_owner_id                                        *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function get_group_post() {
        $base_url = $this->baseurl;

        $group_owner_id = $this->input->post('group_owner_id');
        $this->check_empty($group_owner_id, 'Please enter group_owner_id');

        $group_query = "SELECT * from ws_groups WHERE group_owner = '$group_owner_id' AND status = 1 ORDER BY id DESC";
        $group_data = $this->common_model->getQuery($group_query);

        if (!empty($group_data)) {
            foreach ($group_data as $group_val) {
                $return_data[] = array(
                    'id' => $group_val['id'],
                    'group_name' => $group_val['group_name'],
                    'group_image' => (!empty($group_val['profile_pic']) ? $base_url . $group_val['profile_pic'] : ''),
                );
                $data = array(
                    'status' => 1,
                    'data' => $return_data
                );
            }
        } else {
            $return_data = array();
            $data = array(
                'status' => 1,
                'data' => $return_data
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : get_group_members                                                           *
     * Functionality : View group members                                                          *    
     * @author       : pratibha sinha                                                              *
     * @param        : int group_id                                                                *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function get_group_members_post() {
        $base_url = $this->baseurl;
        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please enter group_id');

        $groupData = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_id), $multi_record = false, $order = '');

        $group_member_query = "select * from ws_group_members where (group_id = '$group_id' AND status = 1)";
        $group_member_data = $this->common_model->getQuery($group_member_query);
        //check group members data
        if ($group_member_data) {
            foreach ($group_member_data as $members) {
                $memmberData = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $members['member_id'], 'activated' => 1), $multi_record = false, $order = '');

                $return_data[] = array(
                    'member_id' => $members['member_id'],
                    'member_name' => (!empty($memmberData['fullname']) ? $memmberData['fullname'] : ''),
                    'member_profile_pic' => (!empty($memmberData['profile_pic']) ? $base_url . $memmberData['profile_pic'] : '')
                );
            }
            $data = array(
                'status' => 1,
                'group_owner_id' => $groupData['group_owner'],
                'group_name' => $groupData['group_name'],
                'group_image' => (!empty($groupData['profile_pic']) ? $base_url . $groupData['profile_pic'] : ''),
                'data' => $return_data
            );
        } else {
            $return_data = array();
            $data = array(
                'status' => 1,
                'data' => $return_data
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : update_groups                                                *
     * Functionality : update group members by group owner                          *
     * @author       : pratibha sinha                                               *
     * @param        : int    group_owner_id                                        *
     * @param        : int    group_id                                              *
     * @param        : int    added_friend_id                                       *
     * @param        : int    deleted_friend_id                                     *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function update_groups_post() {
        $group_owner_id = $this->input->post('group_owner_id');
        $this->check_empty($group_owner_id, 'Please enter group_owner_id');

        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please enter group_id');

        $added_friend_id = $this->input->post('added_friend_id');
        
        $deleted_friend_id = $this->input->post('deleted_friend_id');
        $this->check_empty($deleted_friend_id, 'Please enter deleted_friend_id');

        //if group exist
        $group_owner_detail = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_id), $multi_record = false, $order = '');
        if ($group_owner_detail) {
            if ($group_owner_detail['group_owner'] == $group_owner_id) {
                //add member into the group
                if ($added_friend_id != '') {
                    $post_data = array(
                        'group_id' => $group_id,
                        'member_id' => $added_friend_id,
                        'status' => 1,
                        'added_at' => date('Y-m-d h:i')
                    );

                    $member_check = "select * from ws_group_members where (group_id = '$group_id' AND member_id = '$added_friend_id') ";
                    $group_data = $this->common_model->getQuery($member_check);
                    if (empty($group_data)) {
                        $group_member = $this->common_model->add('ws_group_members', $post_data);
                    }
                }
                //delete member from the group
                if ($deleted_friend_id != '') {
                    $member_check = "select * from ws_group_members where (group_id = '$group_id' AND member_id = '$deleted_friend_id') ";
                    $group_data = $this->common_model->getQuery($member_check);
                    if (!empty($group_data)) {
                        $post_data = array(
                            'group_id' => $group_id,
                            'member_id' => $deleted_friend_id
                        );
                        $this->common_model->delete($table = 'ws_group_members', $post_data);
                    }
                }
                $data = array(
                    'status' => 1,
                    'message' => 'Updated successfully'
                );
            } else {
                $data = array(
                    'status' => 1,
                    'message' => 'Only admin can update groups'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Either group id or group owner id is wrong'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : leave_group                                                  *
     * Functionality : leave group by member                                        *
     * @author       : pratibha sinha                                               *
     * @param        : int    group_id                                              *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function leave_group_post() {
        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please enter group_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $member_check = "select * from ws_group_members where (group_id = '$group_id' AND member_id = '$user_id') ";
        $group_data = $this->common_model->getQuery($member_check);
        //if user_id belongs to group_id
        if (!empty($group_data)) {
            $post_data = array(
                'group_id' => $group_id,
                'member_id' => $user_id
            );
            $this->common_model->delete($table = 'ws_group_members', $post_data);
            $data = array(
                'status' => 1,
                'message' => 'Success'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Member does not belong to group'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : group_chat                                                   *
     * Functionality : start group chat by member                                   *
     * @author       : pratibha sinha                                               *
     * @param        : int    group_id                                              *
     * @message      : string    message                                            *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function group_chat_post() {
        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please enter group_id');

        $message = $this->input->post('message');
        $this->check_empty($message, 'Please enter message');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $group_check = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_id), $multi_record = false, $order = '');

        //if group exist
        if (!empty($group_check)) {
            $member_check = "select * from ws_group_members where (group_id = '$group_id' AND member_id = '$user_id') ";
            $group_data = $this->common_model->getQuery($member_check);
            //if user_id belongs to group_id
            if (!empty($group_data)) {
                $post_data = array(
                    'group_id' => $group_id,
                    'message' => $message,
                    'user_id' => $user_id,
                    'added_at' => date('Y-m-d h:i')
                );

                $group_msg = $this->common_model->add('ws_group_messages', $post_data);
                if ($group_msg) {

                    //notification start
                    $notify_members_check = "select * from ws_group_members where (group_id = '$group_id' AND member_id != '$user_id') ";
                    $notify_members_data = $this->common_model->getQuery($notify_members_check);

                    foreach ($notify_members_data as $notify_member) {
                        $receiver = $notify_member['member_id'];
                        //check block status
                        $block_chk = $this->check_block($receiver, $user_id);
                        if ($block_chk == false) {
                            $this->send_notification($receiver, $user_id, 'group_chat', $message, $group_id, '', $group_msg);
                        } else {
                            // echo 'hkjhjk';die;
                        }
                    }
                    //notification end
                    $data = array(
                        'status' => 1,
                        'message' => 'Success',
                        'data' => $post_data
                    );
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'Error'
                    );
                }
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Member does not belong to group'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Group does not exist'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : get_group_chat                                               *
     * Functionality : view group chat by member                                    *
     * @author       : pratibha sinha                                               *
     * @param        : int    group_id                                              *
     * @param        : int    user_id                                               *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function get_group_chat_post() {
        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please enter group_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $group_check = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_id), $multi_record = false, $order = '');

        //if group exist
        if (!empty($group_check)) {
            $member_check = $this->common_model->findWhere($table = 'ws_group_members', $where_data = array('group_id' => $group_id, 'member_id' => $user_id), $multi_record = false, $order = '');

            // if user_id belongs to group_id
            if (!empty($member_check)) {
                $group_member_time = $member_check['added_at'];
                $query = "select * from ws_group_messages where group_id = '$group_id' AND added_at > '$group_member_time'";
                $group_data = $this->common_model->getQuery($query);
                
                if (!empty($group_data)) {

                    foreach ($group_data as $group_val) {
                        
                        $this->db->select('profile_pic');
                        $this->db->from('ws_users');
                        $this->db->where('id', $group_val['user_id']);
                        $img = $this->db->get()->row();
                        $Data[] = array(
                            'message_creator' => $group_val['user_id'],
                            'message' => $group_val['message'],
                            'added_at' => $group_val['added_at'],
                            'message_id' => $group_val['id'],
                            'pic' => $img->profile_pic,
                        );
                    }
                } else {
                    $Data = array();
                }

                $data = array(
                    'status' => 1,
                    'base_url' => $this->baseurl,
                    'data' => $Data
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'userid does not belong to group'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Group does not exist'
            );
        }
        $this->response($data, 200);
    }

    /**
     * *******************************************************************************
     * Function Name : delete_group                                                 *
     * Functionality : delete group by owner                                        *
     * @author       : pratibha sinha                                               *
     * @param        : int    group_id                                              *
     * @param        : int    owner_id                                              *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function delete_group_post() {
        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please enter group_id');

        $owner_id = $this->input->post('owner_id');
        $this->check_empty($owner_id, 'Please enter owner_id');
        //if group exist
        $group_owner_detail = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_id), $multi_record = false, $order = '');
        if ($group_owner_detail) {
            if ($group_owner_detail['group_owner'] == $owner_id) {

                //delete group
                $this->common_model->delete($table = 'ws_groups', $post_data = array('id' => $group_id));

                //delete group members
                $this->common_model->delete($table = 'ws_group_members', $post_data = array('group_id' => $group_id));

                //delete group messages
                $this->common_model->delete($table = 'ws_group_messages', $post_data = array('group_id' => $group_id));

                //delete group id from posts
                $this->db->query("UPDATE ws_posts
                    SET
                      group_id =
                        TRIM(BOTH ',' FROM REPLACE(CONCAT(',', group_id, ','), ',$group_id,', ','))
                    WHERE
                      FIND_IN_SET('$group_id', group_id)");
                
                $data = array(
                    'status' => 1,
                    'message' => 'Success'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Only admin can delete the group'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'group id does not exist'
            );
        }
        $this->response($data, 200);
    }

    //start common chat functions

    /**
     * *******************************************************************************
     * Function Name : chat_start                                                   *
     * Functionality : To start chat.                                               *
     * @author       : pratibha sinha                                               *
     * @param        : $sender_id,$receiver_id                                      *
     * *******************************************************************************
     * */
    public function chat_start($sender_id, $receiver_id) {
        $created = date('Y-m-d h:i');
        $post_data = array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'created' => $created,
            'sender_status' => $sender_id,
            'receiver_status' => $receiver_id,
        );
        if ($last_id = $this->common_model->add('ws_conversations', $post_data)) {
            return $last_id;
        } else {
            return 0;
        }
    }

    /**
     * *******************************************************************************
     * Function Name : check_conversation                                           *
     * Functionality : To check status of conversation w.r.t user .                 *
     * @author       : pratibha sinha                                               *
     * @param        : $user_id,$conversation_id                                    *
     * *******************************************************************************
     * */
    public function check_conversation($user_id, $receiver_id) {
        $wdata = $this->db->where("(sender_id='" . $user_id . "' AND receiver_id='" . $receiver_id . "') OR (sender_id='" . $receiver_id . "' AND receiver_id='" . $user_id . "')", NULL, FALSE);
        $post_exists = $this->common_model->findWhere($table = 'ws_conversations', $wdata, $multi_record = False, $order = '');

        if ($user_id == $post_exists['sender_id'] && ($post_exists['sender_status'] == $user_id || $post_exists['sender_status'] == 0)) {
            //echo 'm';die;
            return true;
        } elseif ($user_id == $post_exists['receiver_id'] && ($post_exists['receiver_status'] == $user_id || $post_exists['receiver_status'] == 0)) {
            //echo 'n';die;
            return true;
        } else {
            //echo 'p';die;
            return false;
        }
    }

    /**
     * *******************************************************************************
     * Function Name : check_exist_conversation                                     *
     * Functionality : To check whether conversation exist or not .                 *
     * @author       : pratibha sinha                                               *
     * @param        : $sender_id,$receiver_id                                      *
     * *******************************************************************************
     * */
    public function check_exist_conversation($sender_id, $receiver_id) {
        $conv_query = "SELECT * from ws_conversations Where (sender_id = '$sender_id' OR receiver_id = '$sender_id') AND (sender_id = '$receiver_id' OR receiver_id = '$receiver_id') ";
        $conv_data = $this->common_model->getQuery($conv_query);
        if (!empty($conv_data)) {
            return $conv_data[0]['id'];
        } else {
            return 0;
        }
    }

    //end common chat functions

    /**
     * **********************************************************************************************
     * Function Name : conversation                                                                *
     * Functionality : to start conversation based on existing conversation or new conversation    *
     * @author       : pratibha sinha                                                              *
     * @param        : int    sender_id                                                            *
     * @param        : int    receiver_id                                                          *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function conversation_post() {
        $sender_id = $this->input->post('sender_id');
        $this->check_empty($sender_id, 'Please enter sender_id');

        $receiver_id = $this->input->post('receiver_id');
        $this->check_empty($receiver_id, 'Please enter receiver_id');

        $message = $this->input->post('message');
        $this->check_empty($message, 'Please enter message');


        $conv_id = $this->check_exist_conversation($sender_id, $receiver_id);

        if ($conv_id > 0) {
            $conversation_id = $conv_id;
            $conversation = $this->common_model->findWhere($table = 'ws_conversations', array('id' => $conversation_id), $multi_record = false, $order = '');
            if ($sender_id == $conversation['sender_id']) {
                $this->db->where('id', $conversation_id);
                $this->db->update('ws_conversations', array('sender_status' => $sender_id));
            }

            if ($sender_id == $conversation['receiver_id']) {
                $this->db->where('id', $conversation_id);
                $this->db->update('ws_conversations', array('receiver_status' => $sender_id));
            }
        } else {
            $conversation_id = $this->chat_start($sender_id, $receiver_id);
        }
        $created = date('Y-m-d h:i');
        $post_data = array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'message' => $message,
            'conversation_id' => $conversation_id,
            'created' => $created
        );

        if ($last_id = $this->common_model->add('ws_messages', $post_data)) {
            //$post_data['message_id'] = $last_id;
            $con_where = array(
                'id' => $last_id,
            );
            $post_data = $this->common_model->findWhere($table = 'ws_messages', $con_where, $multi_record = false, $order = '');
            $post_data['message_id'] = $post_data['id'];

            //send notification start for post

            $block_chk = $this->check_block($receiver_id, $sender_id);
            if ($block_chk == false) {
                $this->send_notification($receiver_id, $sender_id, 'simple_chat', $message, '', '', $last_id);
            }
            //send notification end for post

            $data = array(
                'status' => '1',
                'data' => $post_data
            );
        } else {

            $data = array(
                'status' => '0',
                'message' => 'error'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : get_conversation                                                            *
     * Functionality : view all conversation between sender and receiver                           *
     * @author       : pratibha sinha                                                              *
     * @param        : int    sender_id                                                            *
     * @param        : int    conversation_id                                                      *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function get_conversation_post() {
        $sender_id = $this->input->post('sender_id');
        $receiver_id = $this->input->post('receiver_id');
        $conversation_id = $this->input->post('conversation_id');
        $this->check_empty($sender_id, 'Please enter sender id');
        $this->check_empty($receiver_id, 'Please enter receiver id');
        
        $where_data = array('sender_id' => $sender_id);
        $conversation_data = array();

        $wdata = $this->db->where("(sender_id='" . $sender_id . "' AND receiver_id='" . $receiver_id . "') OR (sender_id='" . $receiver_id . "' AND receiver_id='" . $sender_id . "')", NULL, FALSE);
        //check conversation exist
        $conv_chk_data = $this->common_model->findWhere($table = 'ws_conversations', $wdata, $multi_record = false, $order = '');
        if ($conv_chk_data) {
            if ($conv_chk_data['sender_id'] == $sender_id) {
                $conversation_query = "SELECT * from ws_messages WHERE (( sender_id = '$sender_id' AND receiver_id = '$receiver_id' ) OR ( sender_id = '$receiver_id' AND receiver_id = '$sender_id' )) AND sender_deleted = 1 ORDER BY id ASC";
                $conversation_data = $this->common_model->getQuery($conversation_query);
            } else {
                $conversation_query = "SELECT * from ws_messages WHERE (( sender_id = '$sender_id' AND receiver_id = '$receiver_id' ) OR ( sender_id = '$receiver_id' AND receiver_id = '$sender_id' )) AND receiver_deleted = 1 ORDER BY id ASC";
                $conversation_data = $this->common_model->getQuery($conversation_query);
            }
            $post_data = array(
                'is_read' => 1
            );

            $where_data = array(
                'conversation_id' => $conversation_id
            );
            $check = $this->check_conversation($sender_id, $receiver_id);
            
            if ($conversation_data && $check == true) {

                foreach ($conversation_data as $messages) {
                    $con_where = array(
                        'id' => $messages['sender_id'],
                        'activated' => 1
                    );
                    $user_data = $this->common_model->findWhere($table = 'ws_users', $con_where, $multi_record = false, $order = '');


                    $final_array['message_id'] = $messages['id'];
                    $final_array['sender_id'] = $messages['sender_id'];
                    $final_array['name'] = $user_data['fullname'];
                    $final_array['profile_pic'] = $user_data['profile_pic'];
                    $final_array['conversation_id'] = $messages['conversation_id'];
                    $final_array['message'] = $messages['message'];
                    $final_array['created'] = $messages['created'];

                    $return_data[] = $final_array;
                }
                $wdata = $this->db->where("(sender_id='".$sender_id."' AND receiver_id='".$receiver_id."') OR (sender_id='".$receiver_id."' AND receiver_id='".$sender_id."')", NULL, FALSE);
                $conv_data = $this->common_model->findWhere($table = 'ws_conversations', $wdata, $multi_record = false, $order = '');

                if ($conv_data['sender_id'] == $sender_id) {
                    $user_id = $conv_data['sender_id'];
                    $receiver_profile_id = $conv_data['receiver_id'];
                    //echo '11';die;
                } else {
                    $user_id = $conv_data['receiver_id'];
                    $receiver_profile_id = $conv_data['sender_id'];
                    //echo '22';die;
                }

                $receiver_profile_where = array(
                    'id' => $receiver_profile_id, 'activated' => 1
                );
                $receiver_profile = $this->common_model->findWhere($table = 'ws_users', $receiver_profile_where, $multi_record = false, $order = '');


                $sender_profile_where = array(
                    'id' => $sender_id, 'activated' => 1
                );
                $sender_profile = $this->common_model->findWhere($table = 'ws_users', $sender_profile_where, $multi_record = false, $order = '');
                
                $this->common_model->updateWhere('ws_messages', $where_data, $post_data);
                $data = array(
                    'status' => 1,
                    'data' => $return_data,
                    'receiver_profile' => $receiver_profile,
                    'sender_profile' => $sender_profile
                );
            } else {
                $conversation_data = array();
                $data = array(
                    'status' => 1,
                    'data' => $conversation_data
                );
            }
        } else {
            $conversation_data = array();
            $data = array(
                'status' => 1,
                'data' => $conversation_data
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : get_all_conversations                                                       *
     * Functionality : view all conversation(last messages of receiver) of user                    *
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function get_all_conversations_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $conversation_query = "SELECT * from ws_conversations where ( sender_id = '$user_id' OR receiver_id = '$user_id' ) AND status = 1
        AND (sender_status = '$user_id' OR receiver_status = '$user_id') ORDER BY id DESC";
        $conversation_data = $this->common_model->getQuery($conversation_query);
        //echo '<pre>';print_r($conversation_data);
        if ($conversation_data) {
            foreach ($conversation_data as $conv_data) {
                $conv_id = $conv_data['id'];
                $sender_id = $conv_data['sender_id'];
                $receiver_id = $conv_data['receiver_id'];

                $check = $this->check_conversation($sender_id, $receiver_id);
                //echo $check;
                if ($check == true) {
                    //echo '2';die;
                    $msg_query = "SELECT * from ws_messages where conversation_id = '$conv_id' AND status = 1 ORDER BY id DESC LIMIT 1";
                    //echo $this->db->last_query();
                    $msg_data = $this->common_model->getQuery($msg_query);

                    if ($user_id == $receiver_id) {
                        $user_query = "SELECT * from ws_users where (id = '$sender_id' AND activated = 1)";
                    } else {
                        $user_query = "SELECT * from ws_users where (id = '$receiver_id' AND activated = 1)";
                    }

                    $user_data = $this->common_model->getQuery($user_query);
                }

                $Data[] = array(
                    'conversation_id' => $conv_data['id'],
                    'sender_id' => $conv_data['sender_id'],
                    'receiver_id' => $conv_data['receiver_id'],
                    'status' => $conv_data['status'],
                    'is_read' => $conv_data['is_read'],
                    'created' => $conv_data['created'],
                    'last_message' => (!empty($msg_data[0]['message']) ? $msg_data[0]['message'] : ''),
                    'user_name' => (!empty($user_data[0]['fullname']) ? $user_data[0]['fullname'] : ''),
                    'profile_pic' => (!empty($user_data[0]['profile_pic']) ? $user_data[0]['profile_pic'] : '')
                );
                $data = array(
                    'status' => 1,
                    'base_url' => $this->baseurl,
                    'data' => $Data
                );
            }
        } else {
            $Data = array();
            $data = array(
                'status' => 1,
                'data' => $Data
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : delete_msgs                                                                 *
     * Functionality : delete msg of simple chat                                                   *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    msg_id                                                               *
     * @param        : int    user_id                                                              *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function delete_msgs_post() {
        $msg_id = $this->input->post('msg_id');
        $this->check_empty($msg_id, 'Please enter msg id');
        
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $where_data = array(
            'id' => $msg_id
        );

        $msg_exists = $this->common_model->findWhere($table = 'ws_messages', $where_data, $multi_record = False, $order = '');

        if ($msg_exists) {
            if ($msg_exists['sender_id'] == $user_id) {
                $msgpost_data = array('sender_deleted' => 0);
            } else {
                $msgpost_data = array('receiver_deleted' => 0);
            }
            $this->common_model->updateWhere('ws_messages', $where_data, $msgpost_data);
        } else {
            $data = array(
                'status' => 0,
                'data' => 'msg not exist'
            );
        }
        $data = array(
            'status' => 1,
            'data' => 'success'
        );
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : delete_conversation                                                         *
     * Functionality : delete conversation                                                         *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    sender_id                                                            *
     * @param        : int    receiver_id                                                          *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function delete_conversation_post() {
        $sender_id = $this->input->post('sender_id');
        $this->check_empty($sender_id, 'Please enter sender_id');

        $receiver_id = $this->input->post('receiver_id');
        $this->check_empty($receiver_id, 'Please enter receiver_id');

        $conversation_id = '';

        $wdata = $this->db->where("(sender_id='" . $sender_id . "' AND receiver_id='" . $receiver_id . "') OR (sender_id='" . $receiver_id . "' AND receiver_id='" . $sender_id . "')", NULL, FALSE);
        
        $post_exists = $this->common_model->findWhere($table = 'ws_conversations', $wdata, $multi_record = False, $order = '');

        if ($post_exists) {
            if (($post_exists['sender_id'] == $sender_id && $post_exists['receiver_id'] == $receiver_id) || ($post_exists['sender_id'] == $receiver_id && $post_exists['receiver_id'] == $sender_id)) {
                
                if ($sender_id == $post_exists['sender_id'] && $post_exists['sender_status'] == $sender_id) {
                    $post_data = array('sender_status' => 0);

                    $msgpost_data = array('sender_deleted' => 0);
                    $this->common_model->updateWhere('ws_messages', $where_data = array('conversation_id' => $conversation_id), $msgpost_data);

                } elseif ($sender_id == $post_exists['receiver_id'] && $post_exists['receiver_status'] == $sender_id) {
                    $post_data = array('receiver_status' => 0);

                    $msgpost_data = array('receiver_deleted' => 0);
                    $this->common_model->updateWhere('ws_messages', $where_data = array('conversation_id' => $conversation_id), $msgpost_data);
                
                } else {
                    $data = array(
                        'status' => 0,
                        'data' => 'failed'
                    );
                }
                $where_data = array('id' => $post_exists['id']);
                $this->common_model->updateWhere('ws_conversations', $where_data, $post_data);

                $data = array(
                    'status' => 1,
                    'message' => 'success'
                );
            } else {
                $data = array(
                    'status' => 1,
                    'message' => 'User id does not belong to conversation'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'No conversation'
            );
        }
        $this->response($data, 200);
    }

    function notification_status_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');
        $notification_status = $this->input->post('notification_status');
        $notification_type = '1';

        $update_data = array(
            'user_id' => $user_id,
            'status' => $notification_status,
            'notification_type' => $notification_type,
            'added_at' => date('Y-m-d H:i')
        );
        if ($this->db->insert('ws_notifications', $update_data)) {
            $data = array(
                'status' => 1,
                'message' => 'Updated Successfully'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Notification could not be added'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : check_block                                                                 *
     * Functionality : check block friend                                                          *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * @param        : int    friend_id                                                            *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    //check block friend
    public function check_block($user_id, $friend_id) {
        $block = $this->common_model->findWhere($table = 'ws_block', array('user_id' => $user_id, 'friend_id' => $friend_id), $multi_record = false, $order = '');
        if (!empty($block)) {
            return true; // blockdie;
            //echo '1';
        } else {
            return false; // not block
            //echo '2';
        }
    }

    public function convert_video_post() {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        echo $orginel_file_name = '/var/www/html/bestest_test/uploads/videos/spacetestSMALL_512kb.mp4';

        //compatibility
        $comp_newfname = 'out12234.mp4';
        echo $comp_file_newname = '/var/www/html/bestest_test/uploads/videos/' . $comp_newfname;

        $val = exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");
        echo $val;
    }

    function test_img_post() {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);
        
        $file1 = '/var/www/html/bestest_test/uploads/post_images/header.png';
        $file2 = '/var/www/html/bestest_test/uploads/post_images/image2.jpg';
        $file3 = '/var/www/html/bestest_test/uploads/post_images/image3.jpg';
        $filefinal = '/var/www/html/bestest_test/uploads/post_images/chk.png';
        $nfilefinal = '/var/www/html/bestest_test/uploads/post_images/ncollage.jpg';
        //$test1 = exec("/usr/bin/ffmpeg -i $file1 -i $file2 -filter_complex scale=120:-1, tile=2x1 -strict experimental $filefinal 2>&1" , $error);
        
        //$chk = exec("/usr/bin/ffmpeg -version");
        //$chk = exec("/usr/bin/ffmpeg montage -mode concatenate -tile 1x header.png iPhone.png footer.png newcollnewtest1.png");
        //$chk = exec("/usr/bin/ffmpeg convert image1.jpg image2.jpg image3.jpg +append colnew.jpg");
        //$chk = exec("/usr/bin/ffmpeg convert image1.jpg image2.jpg image3.jpg -append colnewapp.jpg");
        //$chk = exec("/usr/bin/ffmpeg montage -mode concatenate -tile 1x image*.jpg new.jpg");
        $chk = exec("/usr/bin/ffmpeg -i $file1 -vf "."crop=314:100:0:0"." $filefinal");     //crop image
        echo '<pre>fvgfg';
        var_dump($chk);
    }

    /**
     * **********************************************************************************************
     * Function Name : create_post                                                                 *
     * Functionality : create_post                                                                 *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * @param        : int    question                                                             *
     * @param        : int    image1                                                               *
     * @param        : int    image2                                                               *
     * @param        : int    image3                                                               *
     * @param        : int    video                                                                *
     * @param        : int    taguser_id                                                           *
     * @param        : int    share_with                                                           *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    function create_post_post() {
        error_reporting(0);
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');
        $question = $this->input->post('question');
        $rotation = $this->input->post('rotation');

        $this->check_empty($question, 'Please add the question');
        $image1 = (isset($_FILES['image1'])) ? $_FILES['image1'] : '';
        $image2 = (isset($_FILES['image2'])) ? $_FILES['image2'] : '';
        $image3 = (isset($_FILES['image3'])) ? $_FILES['image3'] : '';
        $video = (isset($_FILES['video'])) ? $_FILES['video'] : '';

        $taguser_id = $this->input->post('taguser_id');
        
        $type = '';
        if (empty($image1) AND empty($image2) AND empty($image3) AND empty($video)) {
            $data = array(
                'status' => 0,
                'message' => 'Please add at least image or video'
            );
            $this->response($data, 200);
        } else if (!empty($video)) {
            $type = 'video';
        } else {
            $type = 'image';
        }
        
        $share_with = $this->input->post('share_with');
        $this->check_empty($share_with, 'Please add the share_with');

        $added_at = $this->input->post('added_at');
        $this->check_empty($added_at, 'Please add the added_at');

        $group_id = 0;
        if ($share_with == 'group') {
            $group_id = $this->input->post('group_id');
            $this->check_empty($group_id, 'Please add the group_id');
        } else {
            $group_id = 0;
        }

        $friend_id = 0;
        if ($share_with == 'friend') {
            $friend_id = $this->input->post('friend_id');
            $this->check_empty($friend_id, 'Please add the friend_id');
        } else {
            $friend_id = 0;
        }

        $this->db->insert('ws_posts', array('user_id' => $user_id, 'question' => $question, 'share_with' => $share_with, 'status' => '1', 'group_id' => $group_id, 'friend_id' => $friend_id, 'added_at' => $added_at));
        $post_insert_id = $this->db->insert_id();

        //send notification start for post
        //selected notification friends
        $notify_frnds_query = "select * from ws_notification_frnds where (type = 'post' AND friend_id = '$user_id')";
        $notify_frnds_list = $this->common_model->getQuery($notify_frnds_query);
        // friends list
        $notify_all_frnds_query = "Select * From ws_friend_list where (user_id = '$user_id' or friend_id = '$user_id') AND status = 1";
        $notify_all_frnds_list = $this->common_model->getQuery($notify_all_frnds_query);

        //if notification friends are selected
        if ($notify_frnds_list) {
            foreach ($notify_frnds_list as $notify_list) {
                $receiver = $notify_list['user_id'];
                //check receiver block status
                $block_chk = $this->check_block($receiver, $user_id);
                if ($block_chk == false) {
                    $this->send_notification($receiver, $user_id, 'post', '', '', $post_insert_id);
                } else {
                    // echo 'hkjhjk';die;
                }
            }
        } else {
            foreach ($notify_all_frnds_list as $notify_all_list) {
                if ($notify_all_list['user_id'] == $user_id) {
                    $receiver = $notify_all_list['friend_id'];
                } else {
                    $receiver = $notify_all_list['user_id'];
                }

                //check receiver block status
                $block_chk = $this->check_block($receiver, $user_id);
                if ($block_chk == false) {
                    $this->send_notification($receiver, $user_id, 'post', '', '', $post_insert_id);
                } else {
                    // echo 'hkjhjk';die;
                }
            }
        }

        //send notification end for post
        //if insert post
        if ($post_insert_id) {
            //if type is image
            if ($type == 'image') {
                $config['upload_path'] = './uploads/post_images/'; //The path where the image will be save
                $config['allowed_types'] = 'gif|jpg|png'; //Images extensions accepted
                $config['max_size'] = '5048'; //The max size of the image in kb's
                //$config['max_height'] = 768;
                $config['file_name'] = 'img_' . $user_id . time();
                $this->load->library('upload', $config); //Load the upload CI library

                if ($image1) {
                    if ($this->upload->do_upload('image1')) {
                        $file_info = $this->upload->data('image1');
                        $file_name = $file_info['file_name'];
                        $this->db->insert('ws_images', array('image_name' => $file_name, 'post_id' => $post_insert_id));
                        $image_insert_id = $this->db->insert_id();
                        
                    } else {
                        //print_r($this->upload->display_errors());die;
                        $data = array('status' => 0, 'message' => 'image could not be saved');
                        $this->response($data, 200);
                    }
                }

                if ($image2) {
                    if ($this->upload->do_upload('image2')) {
                        $file_info = $this->upload->data('image2');
                        $file_name = $file_info['file_name'];
                        $this->db->insert('ws_images', array('image_name' => $file_name, 'post_id' => $post_insert_id));
                        $image_insert_id = $this->db->insert_id();
                        
                    } else {
                        $data = array('status' => 0, 'message' => 'image could not be saved');
                        $this->response($data, 200);
                    }
                }

                if ($image3) {
                    if ($this->upload->do_upload('image3')) {
                        $file_info = $this->upload->data('image3');
                        $file_name = $file_info['file_name'];
                        $this->db->insert('ws_images', array('image_name' => $file_name, 'post_id' => $post_insert_id));
                        $image_insert_id = $this->db->insert_id();
                        
                    } else {
                        $data = array('status' => 0, 'message' => 'image could not be saved');
                        $this->response($data, 200);
                    }
                }
            } else {
                $config['upload_path'] = './uploads/videos/'; //The path where the image will be save
                $config['allowed_types'] = '*'; //Images extensions accepted
                //$config['max_size'] = '100000000'; //The max size of the video in kb's
                $config['overwrite'] = TRUE; //If exists an image with the same name it will overwrite. Set to false if don't want to overwrite
                $config['file_name'] = 'vid_' . $user_id . time();
                $this->load->library('upload', $config); //Load the upload CI library

                if ($video) {
                    if ($this->upload->do_upload('video')) {
                        //original video path
                        $post_video_path = "uploads/videos/" . $config['file_name'];

                        //video compatibility
                        $uploaded_data = $this->upload->data();
                        $file_path = $uploaded_data['file_path'];
                        $time = time();
                        $orginel_file_name = $uploaded_data['full_path'];

                        //compatibility

                        $comp_newfname = 'con_vid_' . $time . '.mp4';
                        $comp_file_newname = $file_path . $comp_newfname;


                        exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                        $post_video_path = "uploads/videos/" . $comp_newfname;

                        //thumbnail
                        $image_fname = 'con_vid_' . $time . '.jpg';

                        $image_file_name = $file_path . $image_fname;


                        if ($rotation == '90') {
                            exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                        } else {
                            exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                        }
                        $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                        
                        //add play button
                        $name_cover = 'cover_' . $time . '.jpg';
                        $upload_path = 'uploads/videos/';
                        $fisrt_img = $image_file_name;
                        $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                        $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                        $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                        $image_post_data = array(
                            'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                            'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                            'post_id' => $post_insert_id
                        );
                        $this->common_model->add('ws_videos', $image_post_data);
                    } else {
                        // print_r( $this->upload->display_errors() );die;
                        $data = array('status' => 0, 'message' => 'video could not be saved');
                        $this->response($data, 200);
                    }
                }
            }

            //tags

            if (!empty($taguser_id)) {
                $tagmember_array = explode(",", $taguser_id);
                
                if ($tagmember_array) {
                    foreach ($tagmember_array as $id) {
                        $id_val = (int) $id;
                        $member_check = "select * from ws_tags where (post_id = '$post_insert_id' AND user_id = '$id_val') ";
                        $tag_data = $this->common_model->getQuery($member_check);
                        if (empty($tag_data)) {
                            $post_data = array(
                                'post_id' => $post_insert_id,
                                'user_id' => $id_val,
                            );
                            $tag_member = $this->common_model->add('ws_tags', $post_data);
                            if ($tag_member) {
                                $data = array(
                                    'status' => 1,
                                    'message' => 'Success'
                                );
                            } else {
                                $data = array(
                                    'status' => 0,
                                    'message' => 'Unable to tag'
                                );
                            }
                        } else {
                            $data = array(
                                'status' => 0,
                                'message' => 'Already tagged'
                            );
                        }
                    }
                    //send notification start for post
                    //selected notification friends
                    $notify_frnds_query = "select * from ws_notification_frnds where (type = 'tag' AND friend_id = '$user_id')";
                    $notify_frnds_list = $this->common_model->getQuery($notify_frnds_query);

                    //all friends 
                    $notify_all_frnds_query = "Select * From ws_friend_list where (user_id = '$user_id' or friend_id = '$user_id') AND status = 1";
                    $notify_all_frnds_list = $this->common_model->getQuery($notify_all_frnds_query);

                    if ($notify_frnds_list) {
                        foreach ($notify_frnds_list as $notify_list) {
                            $receiver = $notify_list['user_id'];
                            //check block status
                            $block_chk = $this->check_block($receiver, $user_id);
                            if ($block_chk == false) {
                                $this->send_notification($receiver, $user_id, 'tag', '', '', $post_insert_id);
                            }
                        }
                    } else {
                        foreach ($notify_all_frnds_list as $notify_all_list) {
                            if ($notify_all_list['user_id'] == $user_id) {
                                $receiver = $notify_all_list['friend_id'];
                            } else {
                                $receiver = $notify_all_list['user_id'];
                            }

                            //check block status
                            $block_chk = $this->check_block($receiver, $user_id);
                            if ($block_chk == false) {
                                $this->send_notification($receiver, $user_id, 'tag', '', '', $post_insert_id);
                            } else {
                                // echo 'hkjhjk';die;
                            }
                        }
                    }
                    //send notification end for post
                }
            }

            $data = array(
                'status' => 1,
                'message' => 'Post has been added'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Could not insert the post'
            );
        }
        $this->response($data, 200);
    }

    public function check_notification_set($user_id, $type) {
        $set_detail = $this->common_model->findWhere($table = 'ws_notification_set', array('user_id' => $user_id), $multi_record = false, $order = '');

        $status = $set_detail[$type];
        if ($status == 1) {
            return true; // enabled
            //echo '1';
        } else {
            return false; // disabled
            //echo '2';
        }
    }

    /**
     * *******************************************************************************
     * Function Name : change_password                                              *
     * Functionality : Change password                                              *
     * @author       : pratibha sinha                                               *
     * @param        : int    user_id                                               *
     * @param        : int    old_password                                          *
     * @param        : int    new_password                                          *
     * revision 0    : author changes_made                                          *
     * *******************************************************************************
     * */
    public function change_password_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $verification_token = $this->input->post('verification_token');
        $this->check_empty($verification_token, 'Please enter verification_token');

        $new_password = $this->input->post('new_password');
        $this->check_empty($new_password, 'Please enter new_password');

        $where_data = array('id' => $user_id,'verify_token'=> $verification_token, 'activated' => 1);
        $exist_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');
        //echo $this->db->last_query();
        if (!empty($exist_user_data['id'])) {
            
            $hashToStoreInDb = password_hash($new_password, PASSWORD_BCRYPT);
            $post_data = Array(
                    'md5_pwd' => 0,
                    'password' => (!empty($new_password) ? $hashToStoreInDb : $exist_user_data['password']),
                );

            $where_data = array('id' => $user_id, 'activated' => 1);
            $this->common_model->updateWhere($table = 'ws_users', $where_data, $post_data);
            //if password changed successfully
            $data = array(
                'status' => 1,
                'message' => 'Changes successfully saved'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Please enter valid token'
            );
        }
        $this->response($data, 200);
    }

    public function update_notification_setting_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $status = $this->input->post('status');
        $this->check_integer_empty($status, 'Please add status');

        $type = $this->input->post('type');
        $this->check_empty($type, 'Please add type');

        $where_data = array('user_id' => $user_id);
        $updated_data = array($type => $status);
        if ($this->common_model->updateWhere('ws_notification_set', $where_data, $updated_data)) {
            $data = array(
                'status' => 1,
                'message' => 'success'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'failed'
            );
        }
        $this->response($data, 200);
    }

    public function notification_password_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $vote_status = $this->input->post('vote_status');
        $this->check_integer_empty($vote_status, 'Please add vote_status');

        $comment_status = $this->input->post('comment_status');
        $this->check_integer_empty($comment_status, 'Please add comment_status');

        $tag_status = $this->input->post('tag_status');
        $this->check_integer_empty($tag_status, 'Please add tag_status');

        $group_status = $this->input->post('group_status');
        $this->check_integer_empty($group_status, 'Please add group_status');

        $old_password = $this->input->post('old_password');
        //$this->check_empty($old_password, 'Please enter old_password');

        $profile_type = $this->input->post('profile_type');
        $this->check_empty($profile_type, 'Please add profile_type');

        if (!empty($old_password)) {
            $new_password = $this->input->post('new_password');
            $this->check_empty($new_password, 'Please enter new_password');
        }

        $where_data = array('id' => $user_id, 'activated' => 1);
        $exist_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data, $multi_record = false, $order = '');

        if (!empty($exist_user_data['id'])) {
            //notification start
            $where_data = array('user_id' => $user_id);
            $updated_data = array('vote' => $vote_status, 'comment' => $comment_status, 'tag' => $tag_status , 'group' => $group_status);
            if ($this->common_model->updateWhere('ws_notification_set', $where_data, $updated_data)) {
                if (($old_password != '' && $new_password != '') || $profile_type != '') {
                    $pwd_data = array('id' => $user_id, 'password' => md5($old_password), 'activated' => 1);
                    //$exist_user_pwd = $this->common_model->findWhere($table = 'ws_users', $pwd_data, $multi_record = false, $order = '');

                    $passwordIsOldFlag = $exist_user_data['md5_pwd'];
                    $existingHashFromDb = $exist_user_data['password'];

                    $passwordCompare = ($passwordIsOldFlag == 1)? md5($old_password): $old_password;
                    
                    $isPasswordCorrect = password_verify($passwordCompare, $existingHashFromDb);
                    if($isPasswordCorrect == false)
                    {
                        $data = array(
                            'status' => 1,
                            'message' => 'Wrong password'
                        );
                        //$this->response($data, 200); // 200 being the HTTP response code
                    }
                    $hashToStoreInDb = password_hash($new_password, PASSWORD_BCRYPT);
                    $post_data = Array(
                            'md5_pwd' => 0,
                            'password' => (!empty($new_password) ? $hashToStoreInDb : $exist_user_data['password']),
                            'profile_type' => $profile_type
                        );

                        $where_data = array('id' => $user_id, 'activated' => 1);
                        $this->common_model->updateWhere($table = 'ws_users', $where_data, $post_data);
                        //if password changed successfully
                        $data = array(
                            'status' => 1,
                            'message' => 'Changes successfully saved'
                        );
                }
                 else {
                    $data = array(
                        'status' => 1,
                        'message' => 'Changes successfully saved'
                    );
                }
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'failed'
                );
            }
            //notification end
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exists'
            );
        }
        $this->response($data, 200);
    }

    public function aws_upload($file_name , $full_path)
    {
        //$data = array('upload_data' => $this->upload->data());
        //echo $file_name;
        //echo 'hhjh';
        //echo $full_path;die;
        $this->load->library('s3');
        // CONSTRUCT URI
        $uri = "developmentcdn/images/post_images/".$file_name;
        $bucketName = "developmentcdn";

        // DISPLAY DATA
        //echo "<pre>";
       // print_r($data);

        // PUT with custom headers:
        $put = S3::putObject(
            S3::inputFile($full_path),
            $bucketName,
            $uri,
            S3::ACL_PUBLIC_READ,
            array(),
            array( // Custom $requestHeaders
                "Cache-Control" => "max-age=315360000",
                "Expires" => gmdate("D, d M Y H:i:s T", strtotime("+5 years"))
            )
        );
        //var_dump($put);die;
        //$img_baseurl = 'http://d1lvl2bc2ytvwe.cloudfront.net/';
        //echo $img_baseurl.$uri; 
        //return $uri;
    }

    function create_new_post_post() {
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');

        $rotation = $this->input->post('rotation');
        
        $type = $this->input->post('type');
        $this->check_empty($type, 'Please add type');

        $poll_type = $this->input->post('poll_type');
        
        $titleval = $this->input->post('title');
        $title = str_replace('\\"', '"', $titleval);
        
        $latitude = $this->input->post('latitude');

        $longitude = $this->input->post('longitude');

        $location = $this->input->post('location');

        $questionval = $this->input->post('question');
        $question = str_replace('\\"', '"', $questionval);

        $image1 = (isset($_FILES['image1'])) ? $_FILES['image1'] : '';
        $image2 = (isset($_FILES['image2'])) ? $_FILES['image2'] : '';
        $image3 = (isset($_FILES['image3'])) ? $_FILES['image3'] : '';
        $image4 = (isset($_FILES['image4'])) ? $_FILES['image4'] : '';

        $video1 = (isset($_FILES['video1'])) ? $_FILES['video1'] : '';
        $video2 = (isset($_FILES['video2'])) ? $_FILES['video2'] : '';
        $video3 = (isset($_FILES['video3'])) ? $_FILES['video3'] : '';
        $video4 = (isset($_FILES['video4'])) ? $_FILES['video4'] : '';

        $text1val = $this->input->post('text1');
        $text1 = str_replace('\\"', '"', $text1val);

        $text2val = $this->input->post('text2');
        $text2 = str_replace('\\"', '"', $text2val);

        $text3val = $this->input->post('text3');
        $text3 = str_replace('\\"', '"', $text3val);

        $text4val = $this->input->post('text4');
        $text4 = str_replace('\\"', '"', $text4val);

        $text5val = $this->input->post('text5');
        $text5 = str_replace('\\"', '"', $text5val);

        $text6val = $this->input->post('text6');
        $text6 = str_replace('\\"', '"', $text6val);

        $text7val = $this->input->post('text7');
        $text7 = str_replace('\\"', '"', $text7val);

        $text8val = $this->input->post('text8');
        $text8 = str_replace('\\"', '"', $text8val);

        $text9val = $this->input->post('text9');
        $text9 = str_replace('\\"', '"', $text9val);

        $text10val = $this->input->post('text10');
        $text10 = str_replace('\\"', '"', $text10val);

        $text11val = $this->input->post('text11');
        $text11 = str_replace('\\"', '"', $text11val);

        $text12val = $this->input->post('text12');
        $text12 = str_replace('\\"', '"', $text12val);

        $text13val = $this->input->post('text13');
        $text13 = str_replace('\\"', '"', $text13val);

        $text14val = $this->input->post('text14');
        $text14 = str_replace('\\"', '"', $text14val);

        $text15val = $this->input->post('text15');
        $text15 = str_replace('\\"', '"', $text15val);

        $text16val = $this->input->post('text16');
        $text16 = str_replace('\\"', '"', $text16val);

        $text17val = $this->input->post('text17');
        $text17 = str_replace('\\"', '"', $text17val);

        $text18val = $this->input->post('text18');
        $text18 = str_replace('\\"', '"', $text18val);

        $text19val = $this->input->post('text19');
        $text19 = str_replace('\\"', '"', $text19val);

        $text20val = $this->input->post('text20');
        $text20 = str_replace('\\"', '"', $text20val);
        
        $taguser_id = $this->input->post('taguser_id');
        
        //chk img text count
        if ($type == 'image' || $type == 'video') {
            $this->check_empty($question, 'Please add the question');
        }

        if ($type == 'video') {
            
            $count_vids = 0;
            for ($i = 0; $i < 5; $i++) {
                if (${'video' . $i} != "") {
                    $count_vids++;
                }
            }

            if ($count_vids > 4) {
                $data = array(
                    'status' => 0,
                    'message' => 'video cannot be greater than 4'
                );
                $this->response($data, 200);
            }
        }

        if ($type == 'image') {
            $count_imgs = 0;
            for ($i = 0; $i < 5; $i++) {
                if (${'image' . $i} != "") {
                    $count_imgs++;
                }
            }

            if ($count_imgs > 4) {
                $data = array(
                    'status' => 0,
                    'message' => 'image cannot be greater than 4'
                );
                $this->response($data, 200);
            }
        }

        if ($type == 'text') {
            $this->check_empty($title, 'Please add title');

            //chk text 
            $count_txts = 0;
            for ($i = 0; $i < 21; $i++) {
                if (${'text' . $i} != "") {
                    $count_txts++;
                }
            }

            if ($count_txts < 2 || $count_txts > 20) {
                $data = array(
                    'status' => 0,
                    'message' => 'text cannot be greater than 20'
                );
                $this->response($data, 200);
            }
        }

        $share_with = $this->input->post('share_with');
        $this->check_empty($share_with, 'Please add the share_with');

        $delayed_post = $this->input->post('delayed_post');
        //$this->check_integer_empty($delayed_post, 'Please add the delayed_post');

        $delayed_text = $this->input->post('delayed_text');
        //$this->check_empty($delayed_text, 'Please add the delayed_text');

        $added_at = date('Y-m-d H:i:s', time());

        $group_id = 0;
        if ($share_with == 'group') {
            $group_id = $this->input->post('group_id');
            $this->check_empty($group_id, 'Please add the group_id');
        } else {
            $group_id = 0;
        }

        $friend_id = 0;
        if ($share_with == 'friend') {
            $friend_id = $this->input->post('friend_id');
            $this->check_empty($friend_id, 'Please add the friend_id');
        } else {
            $friend_id = 0;
        }

        

        //send notification start for post

        if ($share_with == 'group') {
            //fetch group members
            $members = array();
            $groupsArray = explode(',', $group_id);
            foreach ($groupsArray as $gpID) {

                    $this->db->insert('ws_posts', array(
                    'user_id' => $user_id,
                    'title' => (!empty($title) ? $title : ''),
                    'question' => (!empty($question) ? $question : ''),
                    'share_with' => $share_with,
                    'status' => '1',
                    'group_id' => $gpID,
                    'friend_id' => $friend_id,
                    'added_at' => $added_at,
                    'type' => $type,
                    'poll_type' => (!empty($poll_type) ? $poll_type : 'public'),
                    'latitude' => (!empty($latitude) ? $latitude : ''),
                    'longitude' => (!empty($longitude) ? $longitude : ''),
                    'location' => (!empty($location) ? $location : ''),
                    'delayed_post' => (!empty($delayed_post) ? $delayed_post : ''),
                    'delayed_text' => (!empty($delayed_text) ? $delayed_text : ''),
                ));
                $post_insert_id = $this->db->insert_id();
                //latest group post record
                $gp_latest = $this->common_model->findWhere($table = 'ws_group_post_latest', array('group_id' => $gpID), $multi_record = false, $order = '');
                if (!empty($gp_latest)) {
                    $this->common_model->delete($table = 'ws_group_post_latest', $delgp_data = array('group_id' => $gpID));
                }
                $this->db->insert('ws_group_post_latest', array('group_id' => $gpID, 'post_id' => $post_insert_id));
                //latest group post record

                $gp_members = $this->db->select('member_id')->get_where('ws_group_members', array('group_id' => $gpID))->result_array();
                foreach ($gp_members as $member_key => $member_val) {
                    $members[] = $member_val['member_id'];
                    //read status start
                    $this->db->insert('ws_group_read_status', array('group_id' => $gpID, 'user_id' => $member_val['member_id']));
                    //read status end
                }
                $members_chk_notification = array_values(array_unique($members));
                if ($members_chk_notification) {
                    foreach ($members_chk_notification as $mem_val) {
                        $receiver = $mem_val;
                        //check notification status
                        $notify_chk = $this->check_notification_set($receiver, 'group');
                        
                        if ($notify_chk == true && $mem_val != $user_id) {
                            $this->save_notification($receiver , 'group' , $user_id , $post_insert_id);
                            $this->send_notification($receiver , $user_id , 'group' , '' , '' , $post_insert_id , '' , '' , $gpID);
                            //save notification
                            
                        }
                    }
                }
                if ($post_insert_id) {

                        //if type is image
                        if ($type == 'image') {
                            $config['upload_path'] = './uploads/post_images/'; //The path where the image will be save
                            $config['allowed_types'] = 'gif|jpg|png|jpeg'; //Images extensions accepted
                            $config['max_size'] = '100000'; //The max size of the image in kb's
                            //$config['max_height'] = 768;
                            $config['file_name'] = 'img_' . $user_id . time();
                            $this->load->library('upload', $config); //Load the upload CI library

                            if ($image1) {
                                if ($this->upload->do_upload('image1')) {
                                    $file_info = $this->upload->data('image1');
                                    $file_name1 = $file_info['file_name'];
                                    $full_path = $file_info['full_path'];
                                    $this->aws_upload($file_name1 , $full_path);
                                } else {
                                    //echo '1';print_r($this->upload->display_errors());
                                    $data = array('status' => 0, 'message' => 'image could not be saved');
                                    $this->response($data, 200);
                                }
                            }

                            if ($image2) {
                                if ($this->upload->do_upload('image2')) {
                                    $file_info = $this->upload->data('image2');
                                    $file_name2 = $file_info['file_name'];
                                    $full_path = $file_info['full_path'];
                                    $this->aws_upload($file_name2 , $full_path);
                                } else {
                                    //echo '2';print_r($this->upload->display_errors());
                                    $data = array('status' => 0, 'message' => 'image could not be saved');
                                    $this->response($data, 200);
                                }
                            }

                            if ($image3) {
                                if ($this->upload->do_upload('image3')) {
                                    $file_info = $this->upload->data('image3');
                                    $file_name3 = $file_info['file_name'];
                                    $full_path = $file_info['full_path'];
                                    $this->aws_upload($file_name3 , $full_path);
                                    } else {
                                    //echo '3';print_r($this->upload->display_errors());
                                    $data = array('status' => 0, 'message' => 'image could not be saved');
                                    $this->response($data, 200);
                                }
                            }

                            if ($image4) {
                                if ($this->upload->do_upload('image4')) {
                                    $file_info = $this->upload->data('image4');
                                    $file_name4 = $file_info['file_name'];
                                    $full_path = $file_info['full_path'];
                                    $this->aws_upload($file_name4 , $full_path);
                                } else {
                                    //echo '4';print_r($this->upload->display_errors());die;
                                    $data = array('status' => 0, 'message' => 'image could not be saved');
                                    $this->response($data, 200);
                                }
                            }

                            //image cropping
                            if ($count_imgs == 1) {
                                $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                                } elseif ($count_imgs == 2) {
                                $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                               
                            } elseif ($count_imgs == 3) {
                                $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);

                            } elseif ($count_imgs == 4) {
                                $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                            }
                        } elseif ($type == 'text') {
                            if ($text1) {
                                $this->db->insert('ws_text', array('text' => $text1, 'post_id' => $post_insert_id));
                            }
                            if ($text2) {
                                $this->db->insert('ws_text', array('text' => $text2, 'post_id' => $post_insert_id));
                            }
                            if ($text3) {
                                $this->db->insert('ws_text', array('text' => $text3, 'post_id' => $post_insert_id));
                            }
                            if ($text4) {
                                $this->db->insert('ws_text', array('text' => $text4, 'post_id' => $post_insert_id));
                            }
                            if ($text5) {
                                $this->db->insert('ws_text', array('text' => $text5, 'post_id' => $post_insert_id));
                            }
                            if ($text6) {
                                $this->db->insert('ws_text', array('text' => $text6, 'post_id' => $post_insert_id));
                            }
                            if ($text7) {
                                $this->db->insert('ws_text', array('text' => $text7, 'post_id' => $post_insert_id));
                            }
                            if ($text8) {
                                $this->db->insert('ws_text', array('text' => $text8, 'post_id' => $post_insert_id));
                            }
                            if ($text9) {
                                $this->db->insert('ws_text', array('text' => $text9, 'post_id' => $post_insert_id));
                            }
                            if ($text10) {
                                $this->db->insert('ws_text', array('text' => $text10, 'post_id' => $post_insert_id));
                            }
                            if ($text11) {
                                $this->db->insert('ws_text', array('text' => $text11, 'post_id' => $post_insert_id));
                            }
                            if ($text12) {
                                $this->db->insert('ws_text', array('text' => $text12, 'post_id' => $post_insert_id));
                            }
                            if ($text13) {
                                $this->db->insert('ws_text', array('text' => $text13, 'post_id' => $post_insert_id));
                            }
                            if ($text14) {
                                $this->db->insert('ws_text', array('text' => $text14, 'post_id' => $post_insert_id));
                            }
                            if ($text15) {
                                $this->db->insert('ws_text', array('text' => $text15, 'post_id' => $post_insert_id));
                            }
                            if ($text16) {
                                $this->db->insert('ws_text', array('text' => $text16, 'post_id' => $post_insert_id));
                            }
                            if ($text17) {
                                $this->db->insert('ws_text', array('text' => $text17, 'post_id' => $post_insert_id));
                            }
                            if ($text18) {
                                $this->db->insert('ws_text', array('text' => $text18, 'post_id' => $post_insert_id));
                            }
                            if ($text19) {
                                $this->db->insert('ws_text', array('text' => $text19, 'post_id' => $post_insert_id));
                            }
                            if ($text20) {
                                $this->db->insert('ws_text', array('text' => $text20, 'post_id' => $post_insert_id));
                            }
                        }elseif($type == 'video')
                        {
                            $config['upload_path'] = './uploads/videos/'; //The path where the image will be save
                            $config['allowed_types'] = '*'; //Images extensions accepted
                            //$config['max_size'] = '100000000'; //The max size of the video in kb's
                            $config['overwrite'] = TRUE; //If exists an image with the same name it will overwrite. Set to false if don't want to overwrite
                            $config['file_name'] = 'vid_' . $user_id . time();
                            $this->load->library('upload', $config); //Load the upload CI library

                            if ($video1) {
                                if ($this->upload->do_upload('video1')) {

                                    //original video path
                                    $post_video_path = "uploads/videos/" . $config['file_name'];

                                    //video compatibility
                                    $uploaded_data = $this->upload->data();
                                    $file_path = $uploaded_data['file_path'];
                                    $time = time();
                                    $orginel_file_name = $uploaded_data['full_path'];

                                    //compatibility

                                    $comp_newfname = 'con_vid_' . $time . '.mp4';
                                    $comp_file_newname = $file_path . $comp_newfname;

                                    exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                                    $post_video_path = "uploads/videos/" . $comp_newfname;

                                    //thumbnail
                                    $image_fname = 'con_vid_' . $time . '.jpg';

                                    $image_file_name = $file_path . $image_fname;

                                    /*if ($rotation == '90') {
                                        exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                                    } else {*/
                                        exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                        //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                                    //}
                                    $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                                    
                                    //add play button
                                    $name_cover = 'cover_' . $time . '.jpg';
                                    $upload_path = 'uploads/videos/';
                                    $fisrt_img = $image_file_name;
                                    $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                                    $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                                    $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                                    $image_post_data = array(
                                        'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                        'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                        'post_id' => $post_insert_id
                                    );
                                    $this->common_model->add('ws_videos', $image_post_data);
                                } else {
                                    // print_r( $this->upload->display_errors() );die;
                                    $data = array('status' => 0, 'message' => 'video could not be saved');
                                    $this->response($data, 200);
                                }
                            }

                            if ($video2) {
                                if ($this->upload->do_upload('video2')) {

                                    //original video path
                                    $post_video_path = "uploads/videos/" . $config['file_name'];
                                    
                                    //video compatibility
                                    $uploaded_data = $this->upload->data();
                                    $file_path = $uploaded_data['file_path'];
                                    $time = time();
                                    $orginel_file_name = $uploaded_data['full_path'];

                                    //compatibility

                                    $comp_newfname = 'con_vid_' . $time . '.mp4';
                                    $comp_file_newname = $file_path . $comp_newfname;


                                    exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                                    $post_video_path = "uploads/videos/" . $comp_newfname;

                                    //thumbnail
                                    $image_fname = 'con_vid_' . $time . '.jpg';

                                    $image_file_name = $file_path . $image_fname;

                                    /*if ($rotation == '90') {
                                        exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                                    } else {*/
                                        exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                        //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                                   // }
                                    $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                                    
                                    //add play button
                                    $name_cover = 'cover_' . $time . '.jpg';
                                    $upload_path = 'uploads/videos/';
                                    $fisrt_img = $image_file_name;
                                    $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                                    $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                                    $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                                    $image_post_data = array(
                                        'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                        'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                        'post_id' => $post_insert_id
                                    );
                                    $this->common_model->add('ws_videos', $image_post_data);
                                } else {
                                    // print_r( $this->upload->display_errors() );die;
                                    $data = array('status' => 0, 'message' => 'video could not be saved');
                                    $this->response($data, 200);
                                }
                            }

                            if ($video3) {
                                if ($this->upload->do_upload('video3')) {

                                    //original video path
                                    $post_video_path = "uploads/videos/" . $config['file_name'];
                                    
                                    //video compatibility
                                    $uploaded_data = $this->upload->data();
                                    $file_path = $uploaded_data['file_path'];
                                    $time = time();
                                    $orginel_file_name = $uploaded_data['full_path'];

                                    //compatibility

                                    $comp_newfname = 'con_vid_' . $time . '.mp4';
                                    $comp_file_newname = $file_path . $comp_newfname;


                                    exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                                    $post_video_path = "uploads/videos/" . $comp_newfname;

                                    //thumbnail
                                    $image_fname = 'con_vid_' . $time . '.jpg';

                                    $image_file_name = $file_path . $image_fname;

                                    /*if ($rotation == '90') {
                                        exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                                    } else {*/
                                        exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                        //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                                   // }
                                    $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                                    
                                    //add play button
                                    $name_cover = 'cover_' . $time . '.jpg';
                                    $upload_path = 'uploads/videos/';
                                    $fisrt_img = $image_file_name;
                                    $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                                    $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                                    $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                                    $image_post_data = array(
                                        'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                        'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                        'post_id' => $post_insert_id
                                    );
                                    $this->common_model->add('ws_videos', $image_post_data);
                                } else {
                                    // print_r( $this->upload->display_errors() );die;
                                    $data = array('status' => 0, 'message' => 'video could not be saved');
                                    $this->response($data, 200);
                                }
                            }

                            if ($video4) {
                                if ($this->upload->do_upload('video4')) {

                                    //original video path
                                    $post_video_path = "uploads/videos/" . $config['file_name'];

                                    //video compatibility
                                    $uploaded_data = $this->upload->data();
                                    $file_path = $uploaded_data['file_path'];
                                    $time = time();
                                    $orginel_file_name = $uploaded_data['full_path'];

                                    //compatibility

                                    $comp_newfname = 'con_vid_' . $time . '.mp4';
                                    $comp_file_newname = $file_path . $comp_newfname;

                                    exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                                    $post_video_path = "uploads/videos/" . $comp_newfname;

                                    //thumbnail
                                    $image_fname = 'con_vid_' . $time . '.jpg';

                                    $image_file_name = $file_path . $image_fname;

                                   /* if ($rotation == '90') {
                                        exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                                    } else {*/
                                        exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                       // exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                                    //}
                                    $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                                    
                                    //add play button
                                    $name_cover = 'cover_' . $time . '.jpg';
                                    $upload_path = 'uploads/videos/';
                                    $fisrt_img = $image_file_name;
                                    $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                                    $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                                    $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                                    $image_post_data = array(
                                        'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                        'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                        'post_id' => $post_insert_id
                                    );
                                    $this->common_model->add('ws_videos', $image_post_data);
                                } else {
                                    // print_r( $this->upload->display_errors() );die;
                                    $data = array('status' => 0, 'message' => 'video could not be saved');
                                    $this->response($data, 200);
                                }
                            }
                        }

                        //tags

                        if (!empty($taguser_id)) {
                            $tagmember_array = explode(",", $taguser_id);
                            //echo '<pre>';var_dump($member_array);

                            if ($tagmember_array) {
                                foreach ($tagmember_array as $id) {
                                    $id_val = (int) $id;
                                    //var_dump($id_val);die;
                                    $member_check = "select * from ws_tags where (post_id = '$post_insert_id' AND user_id = '$id_val') ";
                                    $tag_data = $this->common_model->getQuery($member_check);
                                    if (empty($tag_data)) {
                                        $post_data = array(
                                            'post_id' => $post_insert_id,
                                            'user_id' => $id_val,
                                        );
                                        $tag_member = $this->common_model->add('ws_tags', $post_data);
                                        if ($tag_member) {

                                            //tag notification
                                                $receiver = $id_val;
                                                //check notification status
                                                $notify_chk = $this->check_notification_set($receiver, 'tag');
                                                //save notification
                                                $this->save_notification($receiver , 'tag' , $user_id , $post_insert_id);
                                                if ($notify_chk == true && $id_val != $user_id) {
                                                    $this->send_notification($receiver , $user_id , 'tag' , '' , '' , $post_insert_id);
                                                }
                                            //tag notification

                                            $data = array(
                                                'status' => 1,
                                                'message' => 'Success'
                                            );
                                        } else {
                                            $data = array(
                                                'status' => 0,
                                                'message' => 'Unable to tag'
                                            );
                                        }
                                    } else {
                                        $data = array(
                                            'status' => 0,
                                            'message' => 'Already tagged'
                                        );
                                    }
                                }
                                //send notification start for post
                                //selected notification friends
                                $notify_frnds_query = "select * from ws_notification_frnds where (type = 'tag' AND friend_id = '$user_id')";
                                $notify_frnds_list = $this->common_model->getQuery($notify_frnds_query);

                                //all friends 
                                $notify_all_frnds_query = "Select * From ws_friend_list where (user_id = '$user_id' or friend_id = '$user_id') AND status = 1";
                                $notify_all_frnds_list = $this->common_model->getQuery($notify_all_frnds_query);

                                if ($notify_frnds_list) {
                                    foreach ($notify_frnds_list as $notify_list) {
                                        $receiver = $notify_list['user_id'];
                                        //check block status
                                        $block_chk = $this->check_block($receiver, $user_id);
                                        if ($block_chk == false) {
                                            $this->send_notification($receiver, $user_id, 'tag', '', '', $post_insert_id);
                                        }
                                    }
                                } else {
                                    foreach ($notify_all_frnds_list as $notify_all_list) {
                                        if ($notify_all_list['user_id'] == $user_id) {
                                            $receiver = $notify_all_list['friend_id'];
                                        } else {
                                            $receiver = $notify_all_list['user_id'];
                                        }

                                        //check block status
                                        $block_chk = $this->check_block($receiver, $user_id);
                                        if ($block_chk == false) {
                                            $this->send_notification($receiver, $user_id, 'tag', '', '', $post_insert_id);
                                        } else {
                                            // echo 'hkjhjk';die;
                                        }
                                    }
                                }
                                //send notification end for post
                            }
                        }
                        $pollLink = $base_url.'web/?id='.$post_insert_id;
                        $poll_link = '<iframe src="'.$pollLink.'" height="200" width="300"></iframe>';
                        $data = array(
                            'status' => 1,
                            'message' => 'Post has been added',
                            'poll_link' => $poll_link
                        );

                        if($user_id == 360)
                        {
                            $following_members = array();
                            $follower_members = array();
                            $followers_following = array();
                            //fetch followers and followings
                            $following = $this->db->order_by('created', 'desc')->select('friend_id')->get_where('ws_follow', array('user_id' => $user_id))->result_array();
                            foreach ($following as $following_key => $following_val) {
                                $following_members[] = $following_val['friend_id'];
                            }

                            $follower = $this->db->order_by('created', 'desc')->select('user_id')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
                            foreach ($follower as $follower_key => $follower_val) {
                                $follower_members[] = $follower_val['user_id'];
                            }
                            $followers_following = array_values(array_unique(array_merge($following_members, $follower_members)));
                            
                            if ($followers_following) {
                                foreach ($followers_following as $mem_follow_val) {
                                    $receiver = $mem_follow_val;
                                    //check notification status
                                    $notify_chk = $this->check_notification_set($receiver, 'post');
                                    //save notification
                                    
                                    if ($notify_chk == true && $mem_follow_val != $user_id) {
                                        $this->save_notification($receiver , 'post' , $user_id , $post_insert_id);
                                        $this->send_notification($receiver , $user_id , 'post' , '' , '' , $post_insert_id);
                                        
                                    }
                                }
                            }
                        }
                        //$this->response($data, 200);

                    } else {
                        $data = array(
                            'status' => 0,
                            'message' => 'Could not insert the post'
                        );
                        $this->response($data, 200);
                    }
            }
            
            //echo '<pre>';print_r($members);
            //print response

            

        } elseif ($share_with == 'friend') {
                $this->db->insert('ws_posts', array(
                'user_id' => $user_id,
                'title' => (!empty($title) ? $title : ''),
                'question' => (!empty($question) ? $question : ''),
                'share_with' => $share_with,
                'status' => '1',
                'group_id' => $group_id,
                'friend_id' => $friend_id,
                'added_at' => $added_at,
                'type' => $type,
                'poll_type' => (!empty($poll_type) ? $poll_type : 'public'),
                'latitude' => (!empty($latitude) ? $latitude : ''),
                'longitude' => (!empty($longitude) ? $longitude : ''),
                'location' => (!empty($location) ? $location : ''),
                'delayed_post' => (!empty($delayed_post) ? $delayed_post : ''),
                'delayed_text' => (!empty($delayed_text) ? $delayed_text : ''),
            ));
            $post_insert_id = $this->db->insert_id();
            $friendmembers = array();
            $friendsArray = explode(',', $friend_id);
            foreach ($friendsArray as $frID) {
                $receiver = $frID;
                //check notification status
                $notify_chk = $this->check_notification_set($receiver, 'post');
                //save notification
                $this->save_notification($receiver , 'post' , $user_id , $post_insert_id);
                if ($notify_chk == true && $friend_id != $user_id) {
                    $this->send_notification($receiver , $user_id , 'post' , '' , '' , $post_insert_id);
                }
            }
            if ($post_insert_id) {

                //if type is image
                if ($type == 'image') {
                    $config['upload_path'] = './uploads/post_images/'; //The path where the image will be save
                    $config['allowed_types'] = 'gif|jpg|png|jpeg'; //Images extensions accepted
                    $config['max_size'] = '100000'; //The max size of the image in kb's
                    //$config['max_height'] = 768;
                    $config['file_name'] = 'img_' . $user_id . time();
                    $this->load->library('upload', $config); //Load the upload CI library

                    if ($image1) {
                        if ($this->upload->do_upload('image1')) {
                            $file_info = $this->upload->data('image1');
                            $file_name1 = $file_info['file_name'];
                            $full_path = $file_info['full_path'];
                            $this->aws_upload($file_name1 , $full_path);
                        } else {
                            //echo '1';print_r($this->upload->display_errors());
                            $data = array('status' => 0, 'message' => 'image could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($image2) {
                        if ($this->upload->do_upload('image2')) {
                            $file_info = $this->upload->data('image2');
                            $file_name2 = $file_info['file_name'];
                            $full_path = $file_info['full_path'];
                            $this->aws_upload($file_name2 , $full_path);
                        } else {
                            //echo '2';print_r($this->upload->display_errors());
                            $data = array('status' => 0, 'message' => 'image could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($image3) {
                        if ($this->upload->do_upload('image3')) {
                            $file_info = $this->upload->data('image3');
                            $file_name3 = $file_info['file_name'];
                            $full_path = $file_info['full_path'];
                            $this->aws_upload($file_name3 , $full_path);
                            } else {
                            //echo '3';print_r($this->upload->display_errors());
                            $data = array('status' => 0, 'message' => 'image could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($image4) {
                        if ($this->upload->do_upload('image4')) {
                            $file_info = $this->upload->data('image4');
                            $file_name4 = $file_info['file_name'];
                            $full_path = $file_info['full_path'];
                            $this->aws_upload($file_name4 , $full_path);
                        } else {
                            //echo '4';print_r($this->upload->display_errors());die;
                            $data = array('status' => 0, 'message' => 'image could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    //image cropping
                    if ($count_imgs == 1) {
                        $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                        } elseif ($count_imgs == 2) {
                        $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                       
                    } elseif ($count_imgs == 3) {
                        $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);

                    } elseif ($count_imgs == 4) {
                        $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                    }
                } elseif ($type == 'text') {
                    if ($text1) {
                        $this->db->insert('ws_text', array('text' => $text1, 'post_id' => $post_insert_id));
                    }
                    if ($text2) {
                        $this->db->insert('ws_text', array('text' => $text2, 'post_id' => $post_insert_id));
                    }
                    if ($text3) {
                        $this->db->insert('ws_text', array('text' => $text3, 'post_id' => $post_insert_id));
                    }
                    if ($text4) {
                        $this->db->insert('ws_text', array('text' => $text4, 'post_id' => $post_insert_id));
                    }
                    if ($text5) {
                        $this->db->insert('ws_text', array('text' => $text5, 'post_id' => $post_insert_id));
                    }
                    if ($text6) {
                        $this->db->insert('ws_text', array('text' => $text6, 'post_id' => $post_insert_id));
                    }
                    if ($text7) {
                        $this->db->insert('ws_text', array('text' => $text7, 'post_id' => $post_insert_id));
                    }
                    if ($text8) {
                        $this->db->insert('ws_text', array('text' => $text8, 'post_id' => $post_insert_id));
                    }
                    if ($text9) {
                        $this->db->insert('ws_text', array('text' => $text9, 'post_id' => $post_insert_id));
                    }
                    if ($text10) {
                        $this->db->insert('ws_text', array('text' => $text10, 'post_id' => $post_insert_id));
                    }
                    if ($text11) {
                        $this->db->insert('ws_text', array('text' => $text11, 'post_id' => $post_insert_id));
                    }
                    if ($text12) {
                        $this->db->insert('ws_text', array('text' => $text12, 'post_id' => $post_insert_id));
                    }
                    if ($text13) {
                        $this->db->insert('ws_text', array('text' => $text13, 'post_id' => $post_insert_id));
                    }
                    if ($text14) {
                        $this->db->insert('ws_text', array('text' => $text14, 'post_id' => $post_insert_id));
                    }
                    if ($text15) {
                        $this->db->insert('ws_text', array('text' => $text15, 'post_id' => $post_insert_id));
                    }
                    if ($text16) {
                        $this->db->insert('ws_text', array('text' => $text16, 'post_id' => $post_insert_id));
                    }
                    if ($text17) {
                        $this->db->insert('ws_text', array('text' => $text17, 'post_id' => $post_insert_id));
                    }
                    if ($text18) {
                        $this->db->insert('ws_text', array('text' => $text18, 'post_id' => $post_insert_id));
                    }
                    if ($text19) {
                        $this->db->insert('ws_text', array('text' => $text19, 'post_id' => $post_insert_id));
                    }
                    if ($text20) {
                        $this->db->insert('ws_text', array('text' => $text20, 'post_id' => $post_insert_id));
                    }
                }elseif($type == 'video')
                {
                    $config['upload_path'] = './uploads/videos/'; //The path where the image will be save
                    $config['allowed_types'] = '*'; //Images extensions accepted
                    //$config['max_size'] = '100000000'; //The max size of the video in kb's
                    $config['overwrite'] = TRUE; //If exists an image with the same name it will overwrite. Set to false if don't want to overwrite
                    $config['file_name'] = 'vid_' . $user_id . time();
                    $this->load->library('upload', $config); //Load the upload CI library

                    if ($video1) {
                        if ($this->upload->do_upload('video1')) {

                            //original video path
                            $post_video_path = "uploads/videos/" . $config['file_name'];

                            //video compatibility
                            $uploaded_data = $this->upload->data();
                            $file_path = $uploaded_data['file_path'];
                            $time = time();
                            $orginel_file_name = $uploaded_data['full_path'];

                            //compatibility

                            $comp_newfname = 'con_vid_' . $time . '.mp4';
                            $comp_file_newname = $file_path . $comp_newfname;

                            exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                            $post_video_path = "uploads/videos/" . $comp_newfname;

                            //thumbnail
                            $image_fname = 'con_vid_' . $time . '.jpg';

                            $image_file_name = $file_path . $image_fname;

                            /*if ($rotation == '90') {
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                            } else {*/
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                            //}
                            $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                            
                            //add play button
                            $name_cover = 'cover_' . $time . '.jpg';
                            $upload_path = 'uploads/videos/';
                            $fisrt_img = $image_file_name;
                            $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                            $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                            $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                            $image_post_data = array(
                                'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                'post_id' => $post_insert_id
                            );
                            $this->common_model->add('ws_videos', $image_post_data);
                        } else {
                            // print_r( $this->upload->display_errors() );die;
                            $data = array('status' => 0, 'message' => 'video could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($video2) {
                        if ($this->upload->do_upload('video2')) {

                            //original video path
                            $post_video_path = "uploads/videos/" . $config['file_name'];
                            
                            //video compatibility
                            $uploaded_data = $this->upload->data();
                            $file_path = $uploaded_data['file_path'];
                            $time = time();
                            $orginel_file_name = $uploaded_data['full_path'];

                            //compatibility

                            $comp_newfname = 'con_vid_' . $time . '.mp4';
                            $comp_file_newname = $file_path . $comp_newfname;


                            exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                            $post_video_path = "uploads/videos/" . $comp_newfname;

                            //thumbnail
                            $image_fname = 'con_vid_' . $time . '.jpg';

                            $image_file_name = $file_path . $image_fname;

                            /*if ($rotation == '90') {
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                            } else {*/
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                           // }
                            $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                            
                            //add play button
                            $name_cover = 'cover_' . $time . '.jpg';
                            $upload_path = 'uploads/videos/';
                            $fisrt_img = $image_file_name;
                            $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                            $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                            $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                            $image_post_data = array(
                                'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                'post_id' => $post_insert_id
                            );
                            $this->common_model->add('ws_videos', $image_post_data);
                        } else {
                            // print_r( $this->upload->display_errors() );die;
                            $data = array('status' => 0, 'message' => 'video could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($video3) {
                        if ($this->upload->do_upload('video3')) {

                            //original video path
                            $post_video_path = "uploads/videos/" . $config['file_name'];
                            
                            //video compatibility
                            $uploaded_data = $this->upload->data();
                            $file_path = $uploaded_data['file_path'];
                            $time = time();
                            $orginel_file_name = $uploaded_data['full_path'];

                            //compatibility

                            $comp_newfname = 'con_vid_' . $time . '.mp4';
                            $comp_file_newname = $file_path . $comp_newfname;


                            exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                            $post_video_path = "uploads/videos/" . $comp_newfname;

                            //thumbnail
                            $image_fname = 'con_vid_' . $time . '.jpg';

                            $image_file_name = $file_path . $image_fname;

                            /*if ($rotation == '90') {
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                            } else {*/
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                           // }
                            $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                            
                            //add play button
                            $name_cover = 'cover_' . $time . '.jpg';
                            $upload_path = 'uploads/videos/';
                            $fisrt_img = $image_file_name;
                            $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                            $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                            $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                            $image_post_data = array(
                                'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                'post_id' => $post_insert_id
                            );
                            $this->common_model->add('ws_videos', $image_post_data);
                        } else {
                            // print_r( $this->upload->display_errors() );die;
                            $data = array('status' => 0, 'message' => 'video could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($video4) {
                        if ($this->upload->do_upload('video4')) {

                            //original video path
                            $post_video_path = "uploads/videos/" . $config['file_name'];

                            //video compatibility
                            $uploaded_data = $this->upload->data();
                            $file_path = $uploaded_data['file_path'];
                            $time = time();
                            $orginel_file_name = $uploaded_data['full_path'];

                            //compatibility

                            $comp_newfname = 'con_vid_' . $time . '.mp4';
                            $comp_file_newname = $file_path . $comp_newfname;

                            exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                            $post_video_path = "uploads/videos/" . $comp_newfname;

                            //thumbnail
                            $image_fname = 'con_vid_' . $time . '.jpg';

                            $image_file_name = $file_path . $image_fname;

                           /* if ($rotation == '90') {
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                            } else {*/
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                               // exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                            //}
                            $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                            
                            //add play button
                            $name_cover = 'cover_' . $time . '.jpg';
                            $upload_path = 'uploads/videos/';
                            $fisrt_img = $image_file_name;
                            $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                            $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                            $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                            $image_post_data = array(
                                'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                'post_id' => $post_insert_id
                            );
                            $this->common_model->add('ws_videos', $image_post_data);
                        } else {
                            // print_r( $this->upload->display_errors() );die;
                            $data = array('status' => 0, 'message' => 'video could not be saved');
                            $this->response($data, 200);
                        }
                    }
                }

                //tags

                if (!empty($taguser_id)) {
                    $tagmember_array = explode(",", $taguser_id);
                    //echo '<pre>';var_dump($member_array);

                    if ($tagmember_array) {
                        foreach ($tagmember_array as $id) {
                            $id_val = (int) $id;
                            //var_dump($id_val);die;
                            $member_check = "select * from ws_tags where (post_id = '$post_insert_id' AND user_id = '$id_val') ";
                            $tag_data = $this->common_model->getQuery($member_check);
                            if (empty($tag_data)) {
                                $post_data = array(
                                    'post_id' => $post_insert_id,
                                    'user_id' => $id_val,
                                );
                                $tag_member = $this->common_model->add('ws_tags', $post_data);
                                if ($tag_member) {

                                    //tag notification
                                        $receiver = $id_val;
                                        //check notification status
                                        $notify_chk = $this->check_notification_set($receiver, 'tag');
                                        //save notification
                                        $this->save_notification($receiver , 'tag' , $user_id , $post_insert_id);
                                        if ($notify_chk == true && $id_val != $user_id) {
                                            $this->send_notification($receiver , $user_id , 'tag' , '' , '' , $post_insert_id);
                                        }
                                    //tag notification

                                    $data = array(
                                        'status' => 1,
                                        'message' => 'Success'
                                    );
                                } else {
                                    $data = array(
                                        'status' => 0,
                                        'message' => 'Unable to tag'
                                    );
                                }
                            } else {
                                $data = array(
                                    'status' => 0,
                                    'message' => 'Already tagged'
                                );
                            }
                        }
                        //send notification start for post
                        //selected notification friends
                        $notify_frnds_query = "select * from ws_notification_frnds where (type = 'tag' AND friend_id = '$user_id')";
                        $notify_frnds_list = $this->common_model->getQuery($notify_frnds_query);

                        //all friends 
                        $notify_all_frnds_query = "Select * From ws_friend_list where (user_id = '$user_id' or friend_id = '$user_id') AND status = 1";
                        $notify_all_frnds_list = $this->common_model->getQuery($notify_all_frnds_query);

                        if ($notify_frnds_list) {
                            foreach ($notify_frnds_list as $notify_list) {
                                $receiver = $notify_list['user_id'];
                                //check block status
                                $block_chk = $this->check_block($receiver, $user_id);
                                if ($block_chk == false) {
                                    $this->send_notification($receiver, $user_id, 'tag', '', '', $post_insert_id);
                                }
                            }
                        } else {
                            foreach ($notify_all_frnds_list as $notify_all_list) {
                                if ($notify_all_list['user_id'] == $user_id) {
                                    $receiver = $notify_all_list['friend_id'];
                                } else {
                                    $receiver = $notify_all_list['user_id'];
                                }

                                //check block status
                                $block_chk = $this->check_block($receiver, $user_id);
                                if ($block_chk == false) {
                                    $this->send_notification($receiver, $user_id, 'tag', '', '', $post_insert_id);
                                } else {
                                    // echo 'hkjhjk';die;
                                }
                            }
                        }
                        //send notification end for post
                    }
                }
                $pollLink = $base_url.'web/?id='.$post_insert_id;
                $poll_link = '<iframe src="'.$pollLink.'" height="200" width="300"></iframe>';
                $data = array(
                    'status' => 1,
                    'message' => 'Post has been added',
                    'poll_link' => $poll_link
                );

                if($user_id == 360)
                {
                    $following_members = array();
                    $follower_members = array();
                    $followers_following = array();
                    //fetch followers and followings
                    $following = $this->db->order_by('created', 'desc')->select('friend_id')->get_where('ws_follow', array('user_id' => $user_id))->result_array();
                    foreach ($following as $following_key => $following_val) {
                        $following_members[] = $following_val['friend_id'];
                    }

                    $follower = $this->db->order_by('created', 'desc')->select('user_id')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
                    foreach ($follower as $follower_key => $follower_val) {
                        $follower_members[] = $follower_val['user_id'];
                    }
                    $followers_following = array_values(array_unique(array_merge($following_members, $follower_members)));
                    
                    if ($followers_following) {
                        foreach ($followers_following as $mem_follow_val) {
                            $receiver = $mem_follow_val;
                            //check notification status
                            $notify_chk = $this->check_notification_set($receiver, 'post');
                            //save notification
                            
                            if ($notify_chk == true && $mem_follow_val != $user_id) {
                                $this->save_notification($receiver , 'post' , $user_id , $post_insert_id);
                                $this->send_notification($receiver , $user_id , 'post' , '' , '' , $post_insert_id);
                            }
                        }
                    }
                }
                //$this->response($data, 200);

            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Could not insert the post'
                );
                $this->response($data, 200);
            }
        } else {
            $this->db->insert('ws_posts', array(
                'user_id' => $user_id,
                'title' => (!empty($title) ? $title : ''),
                'question' => (!empty($question) ? $question : ''),
                'share_with' => $share_with,
                'status' => '1',
                'group_id' => $group_id,
                'friend_id' => $friend_id,
                'added_at' => $added_at,
                'type' => $type,
                'poll_type' => (!empty($poll_type) ? $poll_type : 'public'),
                'latitude' => (!empty($latitude) ? $latitude : ''),
                'longitude' => (!empty($longitude) ? $longitude : ''),
                'location' => (!empty($location) ? $location : ''),
                'delayed_post' => (!empty($delayed_post) ? $delayed_post : ''),
                'delayed_text' => (!empty($delayed_text) ? $delayed_text : ''),
            ));
            $post_insert_id = $this->db->insert_id();
            $following_members = array();
            $follower_members = array();
            $followers_following = array();
            //fetch followers and followings
            $following = $this->db->order_by('created', 'desc')->select('friend_id')->get_where('ws_follow', array('user_id' => $user_id))->result_array();
            foreach ($following as $following_key => $following_val) {
                $following_members[] = $following_val['friend_id'];
            }

            $follower = $this->db->order_by('created', 'desc')->select('user_id')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
            foreach ($follower as $follower_key => $follower_val) {
                $follower_members[] = $follower_val['user_id'];
            }
            $followers_following = array_values(array_unique(array_merge($following_members, $follower_members)));
            
            if ($followers_following) {
                foreach ($followers_following as $mem_follow_val) {
                    $receiver = $mem_follow_val;
                    //check notification status
                    $notify_chk = $this->check_notification_set($receiver, 'post');
                    //save notification
                    
                    if ($notify_chk == true && $mem_follow_val != $user_id) {
                        //$this->send_notification($receiver , $user_id , 'post' , '' , '' , $post_insert_id);
                        //$this->save_notification($receiver , 'post' , $user_id , $post_insert_id);
                    }
                }
            }
            if ($post_insert_id) {

                //if type is image
                if ($type == 'image') {
                    $config['upload_path'] = './uploads/post_images/'; //The path where the image will be save
                    $config['allowed_types'] = 'gif|jpg|png|jpeg'; //Images extensions accepted
                    $config['max_size'] = '100000'; //The max size of the image in kb's
                    //$config['max_height'] = 768;
                    $config['file_name'] = 'img_' . $user_id . time();
                    $this->load->library('upload', $config); //Load the upload CI library

                    if ($image1) {
                        if ($this->upload->do_upload('image1')) {
                            $file_info = $this->upload->data('image1');
                            $file_name1 = $file_info['file_name'];
                            $full_path = $file_info['full_path'];
                            $this->aws_upload($file_name1 , $full_path);
                        } else {
                            //echo '1';print_r($this->upload->display_errors());
                            $data = array('status' => 0, 'message' => 'image could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($image2) {
                        if ($this->upload->do_upload('image2')) {
                            $file_info = $this->upload->data('image2');
                            $file_name2 = $file_info['file_name'];
                            $full_path = $file_info['full_path'];
                            $this->aws_upload($file_name2 , $full_path);
                        } else {
                            //echo '2';print_r($this->upload->display_errors());
                            $data = array('status' => 0, 'message' => 'image could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($image3) {
                        if ($this->upload->do_upload('image3')) {
                            $file_info = $this->upload->data('image3');
                            $file_name3 = $file_info['file_name'];
                            $full_path = $file_info['full_path'];
                            $this->aws_upload($file_name3 , $full_path);
                            } else {
                            //echo '3';print_r($this->upload->display_errors());
                            $data = array('status' => 0, 'message' => 'image could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($image4) {
                        if ($this->upload->do_upload('image4')) {
                            $file_info = $this->upload->data('image4');
                            $file_name4 = $file_info['file_name'];
                            $full_path = $file_info['full_path'];
                            $this->aws_upload($file_name4 , $full_path);
                        } else {
                            //echo '4';print_r($this->upload->display_errors());die;
                            $data = array('status' => 0, 'message' => 'image could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    //image cropping
                    if ($count_imgs == 1) {
                        $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                        } elseif ($count_imgs == 2) {
                        $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                       
                    } elseif ($count_imgs == 3) {
                        $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);

                    } elseif ($count_imgs == 4) {
                        $return_val = $this->image_cropping($count_imgs, $post_insert_id, $file_name1, $file_name2, $file_name3, $file_name4);
                    }
                } elseif ($type == 'text') {
                    if ($text1) {
                        $this->db->insert('ws_text', array('text' => $text1, 'post_id' => $post_insert_id));
                    }
                    if ($text2) {
                        $this->db->insert('ws_text', array('text' => $text2, 'post_id' => $post_insert_id));
                    }
                    if ($text3) {
                        $this->db->insert('ws_text', array('text' => $text3, 'post_id' => $post_insert_id));
                    }
                    if ($text4) {
                        $this->db->insert('ws_text', array('text' => $text4, 'post_id' => $post_insert_id));
                    }
                    if ($text5) {
                        $this->db->insert('ws_text', array('text' => $text5, 'post_id' => $post_insert_id));
                    }
                    if ($text6) {
                        $this->db->insert('ws_text', array('text' => $text6, 'post_id' => $post_insert_id));
                    }
                    if ($text7) {
                        $this->db->insert('ws_text', array('text' => $text7, 'post_id' => $post_insert_id));
                    }
                    if ($text8) {
                        $this->db->insert('ws_text', array('text' => $text8, 'post_id' => $post_insert_id));
                    }
                    if ($text9) {
                        $this->db->insert('ws_text', array('text' => $text9, 'post_id' => $post_insert_id));
                    }
                    if ($text10) {
                        $this->db->insert('ws_text', array('text' => $text10, 'post_id' => $post_insert_id));
                    }
                    if ($text11) {
                        $this->db->insert('ws_text', array('text' => $text11, 'post_id' => $post_insert_id));
                    }
                    if ($text12) {
                        $this->db->insert('ws_text', array('text' => $text12, 'post_id' => $post_insert_id));
                    }
                    if ($text13) {
                        $this->db->insert('ws_text', array('text' => $text13, 'post_id' => $post_insert_id));
                    }
                    if ($text14) {
                        $this->db->insert('ws_text', array('text' => $text14, 'post_id' => $post_insert_id));
                    }
                    if ($text15) {
                        $this->db->insert('ws_text', array('text' => $text15, 'post_id' => $post_insert_id));
                    }
                    if ($text16) {
                        $this->db->insert('ws_text', array('text' => $text16, 'post_id' => $post_insert_id));
                    }
                    if ($text17) {
                        $this->db->insert('ws_text', array('text' => $text17, 'post_id' => $post_insert_id));
                    }
                    if ($text18) {
                        $this->db->insert('ws_text', array('text' => $text18, 'post_id' => $post_insert_id));
                    }
                    if ($text19) {
                        $this->db->insert('ws_text', array('text' => $text19, 'post_id' => $post_insert_id));
                    }
                    if ($text20) {
                        $this->db->insert('ws_text', array('text' => $text20, 'post_id' => $post_insert_id));
                    }
                }elseif($type == 'video')
                {
                    $config['upload_path'] = './uploads/videos/'; //The path where the image will be save
                    $config['allowed_types'] = '*'; //Images extensions accepted
                    //$config['max_size'] = '100000000'; //The max size of the video in kb's
                    $config['overwrite'] = TRUE; //If exists an image with the same name it will overwrite. Set to false if don't want to overwrite
                    $config['file_name'] = 'vid_' . $user_id . time();
                    $this->load->library('upload', $config); //Load the upload CI library

                    if ($video1) {
                        if ($this->upload->do_upload('video1')) {

                            //original video path
                            $post_video_path = "uploads/videos/" . $config['file_name'];

                            //video compatibility
                            $uploaded_data = $this->upload->data();
                            $file_path = $uploaded_data['file_path'];
                            $time = time();
                            $orginel_file_name = $uploaded_data['full_path'];

                            //compatibility

                            $comp_newfname = 'con_vid_' . $time . '.mp4';
                            $comp_file_newname = $file_path . $comp_newfname;

                            exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                            $post_video_path = "uploads/videos/" . $comp_newfname;

                            //thumbnail
                            $image_fname = 'con_vid_' . $time . '.jpg';

                            $image_file_name = $file_path . $image_fname;

                            /*if ($rotation == '90') {
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                            } else {*/
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                            //}
                            $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                            
                            //add play button
                            $name_cover = 'cover_' . $time . '.jpg';
                            $upload_path = 'uploads/videos/';
                            $fisrt_img = $image_file_name;
                            $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                            $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                            $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                            $image_post_data = array(
                                'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                'post_id' => $post_insert_id
                            );
                            $this->common_model->add('ws_videos', $image_post_data);
                        } else {
                            // print_r( $this->upload->display_errors() );die;
                            $data = array('status' => 0, 'message' => 'video could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($video2) {
                        if ($this->upload->do_upload('video2')) {

                            //original video path
                            $post_video_path = "uploads/videos/" . $config['file_name'];
                            
                            //video compatibility
                            $uploaded_data = $this->upload->data();
                            $file_path = $uploaded_data['file_path'];
                            $time = time();
                            $orginel_file_name = $uploaded_data['full_path'];

                            //compatibility

                            $comp_newfname = 'con_vid_' . $time . '.mp4';
                            $comp_file_newname = $file_path . $comp_newfname;


                            exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                            $post_video_path = "uploads/videos/" . $comp_newfname;

                            //thumbnail
                            $image_fname = 'con_vid_' . $time . '.jpg';

                            $image_file_name = $file_path . $image_fname;

                            /*if ($rotation == '90') {
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                            } else {*/
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                           // }
                            $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                            
                            //add play button
                            $name_cover = 'cover_' . $time . '.jpg';
                            $upload_path = 'uploads/videos/';
                            $fisrt_img = $image_file_name;
                            $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                            $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                            $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                            $image_post_data = array(
                                'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                'post_id' => $post_insert_id
                            );
                            $this->common_model->add('ws_videos', $image_post_data);
                        } else {
                            // print_r( $this->upload->display_errors() );die;
                            $data = array('status' => 0, 'message' => 'video could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($video3) {
                        if ($this->upload->do_upload('video3')) {

                            //original video path
                            $post_video_path = "uploads/videos/" . $config['file_name'];
                            
                            //video compatibility
                            $uploaded_data = $this->upload->data();
                            $file_path = $uploaded_data['file_path'];
                            $time = time();
                            $orginel_file_name = $uploaded_data['full_path'];

                            //compatibility

                            $comp_newfname = 'con_vid_' . $time . '.mp4';
                            $comp_file_newname = $file_path . $comp_newfname;


                            exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                            $post_video_path = "uploads/videos/" . $comp_newfname;

                            //thumbnail
                            $image_fname = 'con_vid_' . $time . '.jpg';

                            $image_file_name = $file_path . $image_fname;

                            /*if ($rotation == '90') {
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                            } else {*/
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                                //exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                           // }
                            $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                            
                            //add play button
                            $name_cover = 'cover_' . $time . '.jpg';
                            $upload_path = 'uploads/videos/';
                            $fisrt_img = $image_file_name;
                            $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                            $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                            $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                            $image_post_data = array(
                                'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                'post_id' => $post_insert_id
                            );
                            $this->common_model->add('ws_videos', $image_post_data);
                        } else {
                            // print_r( $this->upload->display_errors() );die;
                            $data = array('status' => 0, 'message' => 'video could not be saved');
                            $this->response($data, 200);
                        }
                    }

                    if ($video4) {
                        if ($this->upload->do_upload('video4')) {

                            //original video path
                            $post_video_path = "uploads/videos/" . $config['file_name'];

                            //video compatibility
                            $uploaded_data = $this->upload->data();
                            $file_path = $uploaded_data['file_path'];
                            $time = time();
                            $orginel_file_name = $uploaded_data['full_path'];

                            //compatibility

                            $comp_newfname = 'con_vid_' . $time . '.mp4';
                            $comp_file_newname = $file_path . $comp_newfname;

                            exec("/usr/bin/ffmpeg -i $orginel_file_name -vcodec h264 -acodec aac -strict -2 $comp_file_newname");

                            $post_video_path = "uploads/videos/" . $comp_newfname;

                            //thumbnail
                            $image_fname = 'con_vid_' . $time . '.jpg';

                            $image_file_name = $file_path . $image_fname;

                           /* if ($rotation == '90') {
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -vf transpose=1 -f image2 $image_file_name");
                            } else {*/
                                exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 720x1024 -vf transpose=0 -f image2 $image_file_name");
                               // exec("/usr/bin/ffmpeg -i $orginel_file_name -ss 0.5 -t 1 -s 640x360 -f image2 $image_file_name");
                            //}
                            $thumbnail_img_path = 'uploads/videos/' . $image_fname;
                            
                            //add play button
                            $name_cover = 'cover_' . $time . '.jpg';
                            $upload_path = 'uploads/videos/';
                            $fisrt_img = $image_file_name;
                            $secound_img = '/var/www/html/bestest_test/uploads/videos/play.png';
                            $saving_path = '/var/www/html/bestest_test/uploads/videos/';
                            $this->create_image($fisrt_img, $secound_img, $saving_path, $name_cover, $position = 'center', $padding = '0');

                            $image_post_data = array(
                                'video_name' => (!empty($post_video_path) ? $post_video_path : ''),
                                'video_thumbnail' => (!empty($name_cover) ? 'uploads/videos/' . $name_cover : ''),
                                'post_id' => $post_insert_id
                            );
                            $this->common_model->add('ws_videos', $image_post_data);
                        } else {
                            // print_r( $this->upload->display_errors() );die;
                            $data = array('status' => 0, 'message' => 'video could not be saved');
                            $this->response($data, 200);
                        }
                    }
                }

                //tags

                if (!empty($taguser_id)) {
                    $tagmember_array = explode(",", $taguser_id);
                    //echo '<pre>';var_dump($member_array);

                    if ($tagmember_array) {
                        foreach ($tagmember_array as $id) {
                            $id_val = (int) $id;
                            //var_dump($id_val);die;
                            $member_check = "select * from ws_tags where (post_id = '$post_insert_id' AND user_id = '$id_val') ";
                            $tag_data = $this->common_model->getQuery($member_check);
                            if (empty($tag_data)) {
                                $post_data = array(
                                    'post_id' => $post_insert_id,
                                    'user_id' => $id_val,
                                );
                                $tag_member = $this->common_model->add('ws_tags', $post_data);
                                if ($tag_member) {

                                    //tag notification
                                        $receiver = $id_val;
                                        //check notification status
                                        $notify_chk = $this->check_notification_set($receiver, 'tag');
                                        //save notification
                                        $this->save_notification($receiver , 'tag' , $user_id , $post_insert_id);
                                        if ($notify_chk == true && $id_val != $user_id) {
                                            $this->send_notification($receiver , $user_id , 'tag' , '' , '' , $post_insert_id);
                                        }
                                    //tag notification

                                    $data = array(
                                        'status' => 1,
                                        'message' => 'Success'
                                    );
                                } else {
                                    $data = array(
                                        'status' => 0,
                                        'message' => 'Unable to tag'
                                    );
                                }
                            } else {
                                $data = array(
                                    'status' => 0,
                                    'message' => 'Already tagged'
                                );
                            }
                        }
                        //send notification start for post
                        //selected notification friends
                        $notify_frnds_query = "select * from ws_notification_frnds where (type = 'tag' AND friend_id = '$user_id')";
                        $notify_frnds_list = $this->common_model->getQuery($notify_frnds_query);

                        //all friends 
                        $notify_all_frnds_query = "Select * From ws_friend_list where (user_id = '$user_id' or friend_id = '$user_id') AND status = 1";
                        $notify_all_frnds_list = $this->common_model->getQuery($notify_all_frnds_query);

                        if ($notify_frnds_list) {
                            foreach ($notify_frnds_list as $notify_list) {
                                $receiver = $notify_list['user_id'];
                                //check block status
                                $block_chk = $this->check_block($receiver, $user_id);
                                if ($block_chk == false) {
                                    $this->send_notification($receiver, $user_id, 'tag', '', '', $post_insert_id);
                                }
                            }
                        } else {
                            foreach ($notify_all_frnds_list as $notify_all_list) {
                                if ($notify_all_list['user_id'] == $user_id) {
                                    $receiver = $notify_all_list['friend_id'];
                                } else {
                                    $receiver = $notify_all_list['user_id'];
                                }

                                //check block status
                                $block_chk = $this->check_block($receiver, $user_id);
                                if ($block_chk == false) {
                                    $this->send_notification($receiver, $user_id, 'tag', '', '', $post_insert_id);
                                } else {
                                    // echo 'hkjhjk';die;
                                }
                            }
                        }
                        //send notification end for post
                    }
                }
                $pollLink = $base_url.'web/?id='.$post_insert_id;
                $poll_link = '<iframe src="'.$pollLink.'" height="200" width="300"></iframe>';
                $data = array(
                    'status' => 1,
                    'message' => 'Post has been added',
                    'poll_link' => $poll_link
                );

                if($user_id == 360)
                {
                    $following_members = array();
                    $follower_members = array();
                    $followers_following = array();
                    //fetch followers and followings
                    $following = $this->db->order_by('created', 'desc')->select('friend_id')->get_where('ws_follow', array('user_id' => $user_id))->result_array();
                    foreach ($following as $following_key => $following_val) {
                        $following_members[] = $following_val['friend_id'];
                    }

                    $follower = $this->db->order_by('created', 'desc')->select('user_id')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
                    foreach ($follower as $follower_key => $follower_val) {
                        $follower_members[] = $follower_val['user_id'];
                    }
                    $followers_following = array_values(array_unique(array_merge($following_members, $follower_members)));
                    
                    if ($followers_following) {
                        foreach ($followers_following as $mem_follow_val) {
                            $receiver = $mem_follow_val;
                            //check notification status
                            $notify_chk = $this->check_notification_set($receiver, 'post');
                            //save notification
                            
                            if ($notify_chk == true && $mem_follow_val != $user_id) {
                                $this->save_notification($receiver , 'post' , $user_id , $post_insert_id);
                                $this->send_notification($receiver , $user_id , 'post' , '' , '' , $post_insert_id);
                                
                            }
                        }
                    }
                }
                //$this->response($data, 200);

            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Could not insert the post'
                );
                $this->response($data, 200);
            }
        }

        //selected notification friends
        $notify_frnds_query = "select * from ws_notification_frnds where (type = 'post' AND friend_id = '$user_id')";
        $notify_frnds_list = $this->common_model->getQuery($notify_frnds_query);
        // friends list
        $notify_all_frnds_query = "Select * From ws_friend_list where (user_id = '$user_id' or friend_id = '$user_id') AND status = 1";
        $notify_all_frnds_list = $this->common_model->getQuery($notify_all_frnds_query);

        //if notification friends are selected
        if ($notify_frnds_list) {
            foreach ($notify_frnds_list as $notify_list) {
                $receiver = $notify_list['user_id'];
                //check receiver block status
                $block_chk = $this->check_block($receiver, $user_id);
                if ($block_chk == false) {
                    //$this->send_notification($receiver , $user_id , 'post' , '' , '', $post_insert_id);
                } else {
                    // echo 'hkjhjk';die;
                }
            }
        } else {
            foreach ($notify_all_frnds_list as $notify_all_list) {
                if ($notify_all_list['user_id'] == $user_id) {
                    $receiver = $notify_all_list['friend_id'];
                } else {
                    $receiver = $notify_all_list['user_id'];
                }

                //check receiver block status
                $block_chk = $this->check_block($receiver, $user_id);
                if ($block_chk == false) {
                    //$this->send_notification($receiver , $user_id , 'post' , '' , '', $post_insert_id);
                } else {
                    // echo 'hkjhjk';die;
                }
            }
        }

        //send notification end for post
        $this->response($data, 200);
        
    }

    public function post_reveal_post()
    {
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $delayed_reveal_type = $this->input->post('delayed_reveal_type');
        $this->check_empty($delayed_reveal_type, 'Please add delayed_reveal_type');   //1=reveal,2=close

        $postChk = $this->db->get_where('ws_posts' , array('post_id' => $post_id , 'delayed_post' => 1))->row_array();
        if($postChk){

            if($delayed_reveal_type == 1){
                //poll reveal
                $this->db->where('post_id', $post_id)->update('ws_posts' ,array(
                                                                    'delayed_reveal' => 1,
                                                                ));

                if($postChk['type'] == 'text'){
                    $likeDetails = $this->db->get_where('ws_text_likes' , array('post_id' => $post_id , 'user_id !=' => $postChk['user_id']))->result_array();
                }else{
                    $likeDetails = $this->db->get_where('ws_likes' , array('post_id' => $post_id , 'user_id !=' => $postChk['user_id']))->result_array();
                }
                if($likeDetails){
                    foreach($likeDetails as $detail){
                        $receiver = $detail['user_id'];
                        //$notify_chk = $this->check_notification_set($receiver, 'vote');
                        //save notification
                        
                        //if ($notify_chk == true) {
                            $this->save_notification($receiver, 'reveal', $postChk['user_id'], $post_id);
                            $this->send_notification($receiver, $postChk['user_id'], 'reveal', '', '', $post_id, '', '');
                            
                        //}
                    }
                }
            }else{
                //poll close
                $this->db->where('post_id', $post_id)->update('ws_posts' ,array(
                                                                    'delayed_reveal' => 2,
                                                                ));
            }
            
            $data =array(
                'status' => 1,
                'message' => 'success'
             );  
        }else{
            $data =array(
                'status' => 0,
                'message' => 'delayed post does not exist'
             );   
        }
        $this->response($data, 200);
    }

    public function img_tags_user($post_insert_id, $image_id, $tags_array = array(), $type_tag, $user_id) {

        if ($type_tag == 'word') {
            foreach ($tags_array as $img_word) {

                $tag_imgword_check = "select * from ws_img_tags_word where (word = '$img_word' AND image_id = '$image_id') ";
                $tagimgword_data = $this->common_model->getQuery($tag_imgword_check);
                if (empty($tagimgword_data)) {
                    $post_data = array(
                        'post_id' => $post_insert_id,
                        'word' => $img_word,
                        'image_id' => $image_id,
                    );
                    $tag_word = $this->common_model->add('ws_img_tags_word', $post_data);
                } else {
                    return 2;
                }
            }
        } else {
            foreach ($tags_array as $img_user) {

                $tag_imguser_check = "select * from ws_img_tags_user where (user = '$img_user' AND image_id = '$image_id') ";
                $tagimguser_data = $this->common_model->getQuery($tag_imguser_check);
                if (empty($tagimguser_data)) {
                    $post_data = array(
                        'post_id' => $post_insert_id,
                        'user' => $img_user,
                        'image_id' => $image_id,
                    );
                    $tag_user = $this->common_model->add('ws_img_tags_user', $post_data);

                    //notification
                    $this->save_notification($img_user, 'imgtaguser', $user_id, '');
                    $this->send_notification($img_user, $user_id, 'imgtaguser', '', '', $post_insert_id, '', $tag_user);
                    
                } else {
                    return 2;
                }
            }
        }
    }

    public function image_cropping1($count, $post_insert_id, $file_name1 = '', $file_name2 = '', $file_name3 = '', $file_name4 = '') {

        $img_insert_array = array();
        if ($count == 1) {
            //1st image
            $image_new_name1 = 'new_' . $file_name1;
            $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
            $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

            exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=1024:1024 $image_file_name1");

            $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
            $image_insert_id1 = $this->db->insert_id();
            if ($image_insert_id1 != '') {
                $img_insert_array['id_img1'] = $image_insert_id1;
            }
        } elseif ($count == 2) {
            //1st image
            $image_new_name1 = 'new_' . $file_name1;
            $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
            $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

            exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=640:960 $image_file_name1");

            $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
            $image_insert_id1 = $this->db->insert_id();
            if ($image_insert_id1) {
                $img_insert_array['id_img1'] = $image_insert_id1;
            }
            //2nd image
            $image_new_name2 = 'new_' . $file_name2;
            $orginel_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
            $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

            exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=640:960 $image_file_name2");

            $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
            $image_insert_id2 = $this->db->insert_id();
            if ($image_insert_id2) {
                $img_insert_array['id_img2'] = $image_insert_id2;
            }
        } elseif ($count == 3) {
            //1st image
            $image_new_name1 = 'new_' . $file_name1;
            $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
            $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

            exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=1024:1024 $image_file_name1");

            $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
            $image_insert_id1 = $this->db->insert_id();
            if ($image_insert_id1) {
                $img_insert_array['id_img1'] = $image_insert_id1;
            }

            //2nd image
            $image_new_name2 = 'new_' . $file_name2;
            $orginel_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
            $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

            exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=1024:1024 $image_file_name2");

            $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
            $image_insert_id2 = $this->db->insert_id();
            if ($image_insert_id2) {
                $img_insert_array['id_img2'] = $image_insert_id2;
            }

            //3rd image
            $image_new_name3 = 'new_' . $file_name3;
            $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
            $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

            exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=960:640 $image_file_name3");

            $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
            $image_insert_id3 = $this->db->insert_id();
            if ($image_insert_id3) {
                $img_insert_array['id_img3'] = $image_insert_id3;
            }
        } elseif ($count == 4) {
            //1st image
            $image_new_name1 = 'new_' . $file_name1;
            $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
            $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

            exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=1024:1024 $image_file_name1");

            $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
            $image_insert_id1 = $this->db->insert_id();
            if ($image_insert_id1) {
                $img_insert_array['id_img1'] = $image_insert_id1;
            }

            //2nd image
            $image_new_name2 = 'new_' . $file_name2;
            $orginel_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
            $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

            exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=1024:1024 $image_file_name2");

            $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
            $image_insert_id2 = $this->db->insert_id();
            if ($image_insert_id2) {
                $img_insert_array['id_img2'] = $image_insert_id2;
            }

            //3rd image
            $image_new_name3 = 'new_' . $file_name3;
            $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
            $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

            exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=1024:1024 $image_file_name3");

            $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
            $image_insert_id3 = $this->db->insert_id();
            if ($image_insert_id3) {
                $img_insert_array['id_img3'] = $image_insert_id3;
            }

            //4th image
            $image_new_name4 = 'new_' . $file_name4;
            $orginel_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name4;
            $image_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name4;

            exec("/usr/bin/ffmpeg -i $orginel_file_name4 -vf scale=1024:1024 $image_file_name4");

            $this->db->insert('ws_images', array('image_name' => $file_name4, 'crop_image' => $image_new_name4, 'post_id' => $post_insert_id));
            $image_insert_id4 = $this->db->insert_id();
            if ($image_insert_id4) {
                $img_insert_array['id_img4'] = $image_insert_id4;
            }
        }
        return serialize($img_insert_array);
    }

    public function image_cropping($count, $post_insert_id, $file_name1 = '', $file_name2 = '', $file_name3 = '', $file_name4 = '') {

        $img_insert_array = array();
        if ($count == 1)
        {
            //1st image
            if($file_name1 != '')
            {
                $image_new_name1 = 'new_' . $file_name1;
                $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
                $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

                exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=1024:1024 $image_file_name1");
                $this->aws_upload($image_new_name1 , $image_file_name1);
                $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
            }
            if($file_name2 != '')
            {
                $image_new_name2 = 'new_' . $file_name2;
                $orginel_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
                $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

                exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=1024:1024 $image_file_name2");
                $this->aws_upload($image_new_name2 , $image_file_name2);
                $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
            }
            if($file_name3 != '')
            {
                $image_new_name3 = 'new_' . $file_name3;
                $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
                $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

                exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=1024:1024 $image_file_name3");
                $this->aws_upload($image_new_name3 , $image_file_name3);
                $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
            }
            if($file_name4 != '')
            {
                $image_new_name4 = 'new_' . $file_name4;
                $orginel_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name4;
                $image_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name4;

                exec("/usr/bin/ffmpeg -i $orginel_file_name4 -vf scale=1024:1024 $image_file_name4");
                $this->aws_upload($image_new_name4 , $image_file_name4);
                $this->db->insert('ws_images', array('image_name' => $file_name4, 'crop_image' => $image_new_name4, 'post_id' => $post_insert_id));
            }
            $image_insert_id1 = $this->db->insert_id();
            if ($image_insert_id1 != '') {
                $img_insert_array['id_img1'] = $image_insert_id1;
            }
        }
        elseif($count == 2)
        {
            //1st image
            if($file_name1 != '')
            {
                $image_new_name1 = 'new_' . $file_name1;
                $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
                $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

                exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=770:820 $image_file_name1");
                $this->aws_upload($image_new_name1 , $image_file_name1);

                $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
                $image_insert_id1 = $this->db->insert_id();
                if ($image_insert_id1) {
                    $img_insert_array['id_img1'] = $image_insert_id1;
                }
                $imgval1 = $file_name1;
            }
            if($file_name2 != '' && $image_insert_id1 == '')
            {
                $image_new_name2 = 'new_' . $file_name2;
                $orginel_file_name2= '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
                $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

                exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=770:820 $image_file_name2");
                $this->aws_upload($image_new_name2 , $image_file_name2);

                $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
                $image_insert_id1 = $this->db->insert_id();
                if ($image_insert_id1) {
                    $img_insert_array['id_img1'] = $image_insert_id1;
                }
                $imgval1 = $file_name2;
            }
            if($file_name3 != '' && $image_insert_id1 == '')
            {
                $image_new_name3 = 'new_' . $file_name3;
                $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
                $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

                exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=770:820 $image_file_name3");
                $this->aws_upload($image_new_name3 , $image_file_name3);

                $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
                $image_insert_id1 = $this->db->insert_id();
                if ($image_insert_id1) {
                    $img_insert_array['id_img1'] = $image_insert_id1;
                }
                $imgval1 = $file_name3;
            }
            if($file_name4 != '' && $image_insert_id1 == '')
            {
                $image_new_name4 = 'new_' . $file_name4;
                $orginel_file_name4= '/var/www/html/bestest_test/uploads/post_images/' . $file_name4;
                $image_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name4;

                exec("/usr/bin/ffmpeg -i $orginel_file_name4 -vf scale=770:820 $image_file_name4");
                $this->aws_upload($image_new_name4 , $image_file_name4);

                $this->db->insert('ws_images', array('image_name' => $file_name4, 'crop_image' => $image_new_name4, 'post_id' => $post_insert_id));
                $image_insert_id1 = $this->db->insert_id();
                if ($image_insert_id1) {
                    $img_insert_array['id_img1'] = $image_insert_id1;
                }
                $imgval1 = $file_name4;
            }

            
            //2nd image
            if($file_name1 != '' && $image_insert_id1 != '' && $imgval1 != $file_name1)
            {
                $image_new_name1 = 'new_' . $file_name1;
                $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
                $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

                exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=770:820 $image_file_name1");
                $this->aws_upload($image_new_name1 , $image_file_name1);

                $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
                $image_insert_id2 = $this->db->insert_id();
                if ($image_insert_id2) {
                    $img_insert_array['id_img2'] = $image_insert_id2;
                }
            }
            if($file_name2 != '' && $image_insert_id1 != '' && $image_insert_id2 == '' && $imgval1 != $file_name2)
            {
                $image_new_name2 = 'new_' . $file_name2;
                $orginel_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
                $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

                exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=770:820 $image_file_name2");
                $this->aws_upload($image_new_name2 , $image_file_name2);

                $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
                $image_insert_id2 = $this->db->insert_id();
                if ($image_insert_id2) {
                    $img_insert_array['id_img2'] = $image_insert_id2;
                }
            }
            if($file_name3 != '' && $image_insert_id1 != '' && $image_insert_id2 == '' && $imgval1 != $file_name3)
            {
                $image_new_name3 = 'new_' . $file_name3;
                $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
                $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

                exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=770:820 $image_file_name3");
                $this->aws_upload($image_new_name3 , $image_file_name3);

                $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
                $image_insert_id2 = $this->db->insert_id();
                if ($image_insert_id2) {
                    $img_insert_array['id_img2'] = $image_insert_id2;
                }
            }
            if($file_name4 != '' && $image_insert_id1 != '' && $image_insert_id2 == '' && $imgval1 != $file_name4)
            {
                $image_new_name4 = 'new_' . $file_name4;
                $orginel_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name4;
                $image_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name4;

                exec("/usr/bin/ffmpeg -i $orginel_file_name4 -vf scale=770:820 $image_file_name4");
                $this->aws_upload($image_new_name4 , $image_file_name4);

                $this->db->insert('ws_images', array('image_name' => $file_name4, 'crop_image' => $image_new_name4, 'post_id' => $post_insert_id));
                $image_insert_id2 = $this->db->insert_id();
                if ($image_insert_id2) {
                    $img_insert_array['id_img2'] = $image_insert_id2;
                }
            }
        }
        elseif ($count == 3)
        {
            //1st image
            if($file_name1 != '')
            {
                $image_new_name1 = 'new_' . $file_name1;
                $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
                $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

                exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=500:400 $image_file_name1");
                $this->aws_upload($image_new_name1 , $image_file_name1);

                $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
                $image_insert_id1 = $this->db->insert_id();
                if ($image_insert_id1) {
                    $img_insert_array['id_img1'] = $image_insert_id1;
                }

                $imgval1 = $file_name1;
            }
            if($file_name2 != '' && $image_insert_id1 == '')
            {
                $image_new_name2 = 'new_' . $file_name2;
                $orginel_file_name2= '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
                $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

                exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=500:400 $image_file_name2");
                $this->aws_upload($image_new_name2 , $image_file_name2);

                $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
                $image_insert_id1 = $this->db->insert_id();
                if ($image_insert_id1) {
                    $img_insert_array['id_img1'] = $image_insert_id1;
                }
                $imgval1 = $file_name2;
            }
            if($file_name3 != '' && $image_insert_id1 == '')
            {
                $image_new_name3 = 'new_' . $file_name3;
                $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
                $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

                exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=500:400 $image_file_name3");
                $this->aws_upload($image_new_name3 , $image_file_name3);

                $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
                $image_insert_id1 = $this->db->insert_id();
                if ($image_insert_id1) {
                    $img_insert_array['id_img1'] = $image_insert_id1;
                }
                $imgval1 = $file_name3;
            }
            if($file_name4 != '' && $image_insert_id1 == '')
            {
                $image_new_name4 = 'new_' . $file_name4;
                $orginel_file_name4= '/var/www/html/bestest_test/uploads/post_images/' . $file_name4;
                $image_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name4;

                exec("/usr/bin/ffmpeg -i $orginel_file_name4 -vf scale=500:400 $image_file_name4");
                $this->aws_upload($image_new_name4 , $image_file_name4);

                $this->db->insert('ws_images', array('image_name' => $file_name4, 'crop_image' => $image_new_name4, 'post_id' => $post_insert_id));
                $image_insert_id1 = $this->db->insert_id();
                if ($image_insert_id1) {
                    $img_insert_array['id_img1'] = $image_insert_id1;
                }
                $imgval1 = $file_name4;
            }

            //2nd image
            if($file_name1 != '' && $image_insert_id1 != '' && $imgval1 != $file_name1)
            {
                $image_new_name1 = 'new_' . $file_name1;
                $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
                $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

                exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=500:400 $image_file_name1");
                $this->aws_upload($image_new_name1 , $image_file_name1);

                $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
                $image_insert_id2 = $this->db->insert_id();
                if ($image_insert_id2) {
                    $img_insert_array['id_img2'] = $image_insert_id2;
                }
                $imgval2 = $file_name1;
            }
            if($file_name2 != '' && $image_insert_id1 != '' && $image_insert_id2 == '' && $imgval1 != $file_name2)
            {
                $image_new_name2 = 'new_' . $file_name2;
                $orginel_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
                $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

                exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=500:400 $image_file_name2");
                $this->aws_upload($image_new_name2 , $image_file_name2);

                $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
                $image_insert_id2 = $this->db->insert_id();
                if ($image_insert_id2) {
                    $img_insert_array['id_img2'] = $image_insert_id2;
                }
                $imgval2 = $file_name2;
            }
            if($file_name3 != '' && $image_insert_id1 != '' && $image_insert_id2 == '' && $imgval1 != $file_name3)
            {
                $image_new_name3 = 'new_' . $file_name3;
                $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
                $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

                exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=500:400 $image_file_name3");
                $this->aws_upload($image_new_name3 , $image_file_name3);

                $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
                $image_insert_id2 = $this->db->insert_id();
                if ($image_insert_id2) {
                    $img_insert_array['id_img2'] = $image_insert_id2;
                }
                $imgval2 = $file_name3;
            }
            if($file_name4 != '' && $image_insert_id1 != '' && $image_insert_id2 == '' && $imgval1 != $file_name4)
            {
                $image_new_name4 = 'new_' . $file_name4;
                $orginel_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name4;
                $image_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name4;

                exec("/usr/bin/ffmpeg -i $orginel_file_name4 -vf scale=500:400 $image_file_name4");
                $this->aws_upload($image_new_name4 , $image_file_name4);

                $this->db->insert('ws_images', array('image_name' => $file_name4, 'crop_image' => $image_new_name4, 'post_id' => $post_insert_id));
                $image_insert_id2 = $this->db->insert_id();
                if ($image_insert_id2) {
                    $img_insert_array['id_img2'] = $image_insert_id2;
                }
                $imgval2 = $file_name4;
            }

            //3rd image
            if($file_name1 != '' && $image_insert_id1 != '' && $image_insert_id2 != '' && $imgval1 != $file_name1  && $imgval2 != $file_name1)
            {
                $image_new_name1 = 'new_' . $file_name1;
                $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
                $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

                exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=1300:500 $image_file_name1");
                $this->aws_upload($image_new_name1 , $image_file_name1);

                $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
                $image_insert_id3 = $this->db->insert_id();
                if ($image_insert_id3) {
                    $img_insert_array['id_img3'] = $image_insert_id3;
                }
                $imgval3 = $file_name1;
            }
            if($file_name2 != '' && $image_insert_id1 != '' && $image_insert_id2 != '' && $image_insert_id3 == '' && $imgval1 != $file_name2 && $imgval2 != $file_name2)
            {
                $image_new_name2 = 'new_' . $file_name2;
                $orginel_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
                $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

                exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=1300:500 $image_file_name2");
                $this->aws_upload($image_new_name2 , $image_file_name2);

                $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
                $image_insert_id3 = $this->db->insert_id();
                if ($image_insert_id3) {
                    $img_insert_array['id_img3'] = $image_insert_id3;
                }
                $imgval3 = $file_name2;
            }
            if($file_name3 != '' && $image_insert_id1 != '' && $image_insert_id2 != '' && $image_insert_id3 == ''  && $imgval1 != $file_name3 && $imgval2 != $file_name3)
            {
                $image_new_name3 = 'new_' . $file_name3;
                $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
                $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

                exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=1300:500 $image_file_name3");
                $this->aws_upload($image_new_name3 , $image_file_name3);

                $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
                $image_insert_id3 = $this->db->insert_id();
                if ($image_insert_id3) {
                    $img_insert_array['id_img3'] = $image_insert_id3;
                }
                $imgval3 = $file_name3;
            }
            if($file_name4 != '' && $image_insert_id1 != '' && $image_insert_id2 != '' && $image_insert_id3 == ''  && $imgval1 != $file_name4 && $imgval2 != $file_name4)
            {
                $image_new_name4 = 'new_' . $file_name4;
                $orginel_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name4;
                $image_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name4;

                exec("/usr/bin/ffmpeg -i $orginel_file_name4 -vf scale=1300:500 $image_file_name4");
                $this->aws_upload($image_new_name4 , $image_file_name4);

                $this->db->insert('ws_images', array('image_name' => $file_name4, 'crop_image' => $image_new_name4, 'post_id' => $post_insert_id));
                $image_insert_id3 = $this->db->insert_id();
                if ($image_insert_id3) {
                    $img_insert_array['id_img3'] = $image_insert_id3;
                }
                $imgval3 = $file_name4;
            }
        }
        elseif ($count == 4)
        {
            //1st image
            $image_new_name1 = 'new_' . $file_name1;
            $orginel_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name1;
            $image_file_name1 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name1;

            exec("/usr/bin/ffmpeg -i $orginel_file_name1 -vf scale=1024:1024 $image_file_name1");
            $this->aws_upload($image_new_name1 , $image_file_name1);

            $this->db->insert('ws_images', array('image_name' => $file_name1, 'crop_image' => $image_new_name1, 'post_id' => $post_insert_id));
            $image_insert_id1 = $this->db->insert_id();
            if ($image_insert_id1) {
                $img_insert_array['id_img1'] = $image_insert_id1;
            }

            //2nd image
            $image_new_name2 = 'new_' . $file_name2;
            $orginel_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name2;
            $image_file_name2 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name2;

            exec("/usr/bin/ffmpeg -i $orginel_file_name2 -vf scale=1024:1024 $image_file_name2");
            $this->aws_upload($image_new_name2 , $image_file_name2);

            $this->db->insert('ws_images', array('image_name' => $file_name2, 'crop_image' => $image_new_name2, 'post_id' => $post_insert_id));
            $image_insert_id2 = $this->db->insert_id();
            if ($image_insert_id2) {
                $img_insert_array['id_img2'] = $image_insert_id2;
            }

            //3rd image
            $image_new_name3 = 'new_' . $file_name3;
            $orginel_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name3;
            $image_file_name3 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name3;

            exec("/usr/bin/ffmpeg -i $orginel_file_name3 -vf scale=1024:1024 $image_file_name3");
            $this->aws_upload($image_new_name3 , $image_file_name3);

            $this->db->insert('ws_images', array('image_name' => $file_name3, 'crop_image' => $image_new_name3, 'post_id' => $post_insert_id));
            $image_insert_id3 = $this->db->insert_id();
            if ($image_insert_id3) {
                $img_insert_array['id_img3'] = $image_insert_id3;
            }

            //4th image
            $image_new_name4 = 'new_' . $file_name4;
            $orginel_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $file_name4;
            $image_file_name4 = '/var/www/html/bestest_test/uploads/post_images/' . $image_new_name4;

            exec("/usr/bin/ffmpeg -i $orginel_file_name4 -vf scale=1024:1024 $image_file_name4");
            $this->aws_upload($image_new_name4 , $image_file_name4);

            $this->db->insert('ws_images', array('image_name' => $file_name4, 'crop_image' => $image_new_name4, 'post_id' => $post_insert_id));
            $image_insert_id4 = $this->db->insert_id();
            if ($image_insert_id4) {
                $img_insert_array['id_img4'] = $image_insert_id4;
            }
        }
        return serialize($img_insert_array);
    }

    public function test_time_post() {
        $time = $this->time_elapsed_string('2015-11-16 15:01:07');
        echo $time;
        echo date('Y-m-d H:i:s');
    }

    function time_elapsed_string($datetime1, $datetime2, $full = false) {
        //$datetime1 = date('Y-m-d H:i:s', time());
        $now = new DateTime($datetime1);
        $ago = new DateTime($datetime2);

        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full)
            $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    /**
     * ************************************************************************************************************
     * Function Name : get_task_post                                                                             *
     * Functionality : get post detail(post detail,group name,friend name,images,videos,last comment,tagged data)*                                                                    *    
     * @author       : pratibha sinha                                                                            *
     * @param        : int    user_id                                                                            *
     * @param        : int    type                                                                               *
     * revision 0    : author changes_made                                                                       *
     * ************************************************************************************************************
     * */
    /* output likes(int), last comment, image */
    /* $type = group */
    /* $type = popular posts */

    function getMyPost_post() {

        $limit = 10;

        $index = $this->input->post('index');
        
        $lasttime = $this->input->post('lasttime');
        $this->check_empty($lasttime, 'Please enter lasttime');

        if (DateTime::createFromFormat('Y-m-d G:i:s', $lasttime) !== FALSE) {
              // it's a date
            }else{
                $data = array('status' => 0 , 'message' => 'invalid date format');
                $this->response($data, 200);
            }
        //echo $lastpoll_id;die;
        $base_url = $this->baseurl;
        //$post_base_url = $this->baseurl . 'uploads/post_images/';
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');

        $datetime1 = date('Y-m-d H:i:s', time());
        
        //check on last updated
        $last_used = array('last_used_app' => date('Y-m-d h:i'));
        $this->common_model->updateWhere('ws_users', $where_data = array('id' => $user_id), $last_used);
        //check on last updated

        /* get all the posts by the user */
        $allMyPosts = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $user_id, 'status' => '1'))->result_array();
        if($lasttime == 0)
        {
            $result_post = $this->db->order_by('added_at', 'desc')->select('post_id,user_id,question,share_with,status,repost_status,added_at,group_id,friend_id,title,type,poll_type')->get_where('ws_posts', array('user_id' => $user_id,'status' => '1') , $limit)->result_array();
        }else{
            $result_post = $this->db->order_by('added_at', 'desc')->select('post_id,user_id,question,share_with,status,repost_status,added_at,group_id,friend_id,title,type,poll_type')->get_where('ws_posts', array('user_id' => $user_id, 'added_at <' => $lasttime,'status' => '1') , $limit)->result_array();
        }
        //echo $this->db->last_query();die;
        //extra detail
        $post_count = count($allMyPosts);
        $userValue = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');
        $following = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('user_id' => $user_id))->result_array();
        $following_count = count($following);
        $follower = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
        $follower_count = count($follower);
        $notification = $this->db->order_by('added_at', 'desc')->get_where('ws_notifications', array('receiver_id' => $user_id, 'status' => 0))->result_array();
        $notification_count = count($notification);
        //extra detail

        if($result_post)
        {
            $lasttime_added = 0;
            foreach($result_post as $detail)
            {
                //group detail
                $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $detail['group_id']), $multi_record = false, $order = '');
                $group_array = array(
                    'group_id' => $detail['group_id'],
                    'group_name' => (!empty($sharegroup_detail['group_name']) ? $sharegroup_detail['group_name'] : ''),
                    );

                //friend detail
                $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $detail['friend_id'], 'activated' => 1), $multi_record = false, $order = '');
                $friend_array = array(
                    'friend_id' => $detail['friend_id'],
                    'friend_name' => (!empty($sharefriend_detail['fullname']) ? $sharefriend_detail['fullname'] : ''),
                    );

                $comment_post = $this->db->get_where('ws_comments' , array('post_id' => $detail['post_id']))->result_array();


                //post creator
                $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $detail['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                $post['poll_creator_name'] = (!empty($post_sender['fullname']) ) ? $post_sender['fullname'] : '';
                $post['poll_creator_pic'] = (!empty($post_sender['profile_pic']) ) ? $base_url . $post_sender['profile_pic'] : '';
                $post['poll_creator_unique_name'] = (!empty($post_sender['unique_name']) ) ? $post_sender['unique_name'] : '';
                $post['poll_created_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);

                $pollType = $detail['type'];

                $textDetails = array();
                $textDetails['post_id'] = $detail['post_id'];

                $imageDetails = array();
                $imageDetails['post_id'] = $detail['post_id'];

                if($pollType == 'text'){ // text type poll
                    //text data
                    $text = $this->db->select('id,text,likes')->get_where('ws_text', array('post_id' => $detail['post_id']))->result_array();
                    $totalPostTextLikes =0;
                    foreach($text as $tx)
                    {
                        $totalPostTextLikes +=  $tx['likes'];
                    }
                    $myTextOpinion = 0; //either user has voted on any option
                    $total_textprop = 0;
                    $loop_textindex = 0;
                    if (count($text) > 0) {
                        foreach ($text as &$text_likes) {
                            $loop_textindex++;
                            $likes_count = (int) $text_likes['likes'];
                            $text_id = (int) $text_likes['id'];
                            //likes status
                            $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $text_likes['like_status'] = (!empty($like) ) ? 'liked' : 'not liked';
                            if($text_likes['like_status'] == 'liked')
                            {
                                $myTextOpinion =  1;
                            }
                            if($likes_count > 0)
                            {
                                $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                                if(count($text) == $loop_textindex && $loop_textindex > 1){
                                $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                                }else{
                                $prop = round ( $likesproportion);
                                $total_textprop += $prop;
                                $text_likes['likes_proportion'] = $prop.'%';
                                }
                            }else
                            {
                                $text_likes['likes_proportion'] = '0%';
                            }
                        }
                    }else{
                        continue;
                        //$text = array();
                    }
                    $textDetails['textOptions'] = $text;
                    $textDetails['myPollReaction'] = $myTextOpinion;
                }else if($pollType == 'image'){ // image type poll
                    //images data
                    $images = $this->db->select('image_id ,image_name,crop_image,likes,unlikes')->get_where('ws_images', array('post_id' => $detail['post_id']))->result_array();
                
                    $totalPostImageLikes =0;
                    $totalPostImageUnlikes =0;
                    foreach($images as $im)
                    {
                        $totalPostImageLikes +=  $im['likes'];
                        $totalPostImageUnlikes +=  $im['unlikes'];
                    }
                    $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
                    $myImageOpinion = 0; //either user has voted on any option. 0= neither liked nor disliked,1=liked,2=disliked
                    $total_imgprop = 0;
                    $loop_imgindex = 0;
                    if (count($images) > 0) {
                        //check like of user id
                        foreach ($images as &$images_likes) {
                            $loop_imgindex++;
                            $images_likes['image_name'] = (!empty($images_likes['image_name']) ) ? $post_base_url.$images_likes['image_name'] : '';
                            $images_likes['crop_image'] = (!empty($images_likes['crop_image']) ) ? $post_base_url.$images_likes['crop_image'] : '';
                            $likes_count = (int) $images_likes['likes'];

                            $unlikes_count = (int) $images_likes['unlikes'];
                            $img_id = (int) $images_likes['image_id'];
                            //likes status
                            $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $images_likes['like_status'] = (!empty($like) ) ? 'liked' : 'not liked';
                            
                            //unlikes status
                            $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $images_likes['unlike_status'] = (!empty($unlike) ) ? 'disliked' : 'not disliked';

                            if($images_likes['like_status'] == 'liked')
                            {
                                $myImageOpinion =  1;
                            }elseif($images_likes['unlike_status'] == 'disliked'){
                                $myImageOpinion =  2;
                            }
                            if($likes_count > 0)
                            {
                                $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                                if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                                $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                                }else{
                                $prop = round ( $likesproportion);
                                $total_imgprop += $prop;
                                $images_likes['likes_proportion'] = $prop.'%';
                                }
                            }else{
                                $images_likes['likes_proportion'] = '0%';
                            }
                            

                            $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                            if(count($img) == 1){
                                $prop = round ( $unlikesproportion);
                                $total_imgprop += $prop;
                                $images_likes['unlikes_proportion'] = $prop.'%';
                            }
                        }
                    }else{
                        continue;
                       // $images = array();
                    }
                    $imageDetails['imageOptions'] = $images;
                    $imageDetails['myPollReaction'] = $myImageOpinion;
                }

                //last comment data
                $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $detail['post_id']), 2)->result_array();
                if (count($last_comment) > 0) {
                    sort($last_comment);
                    foreach ($last_comment as &$commentDetail) {
                        $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                        $commentDetail['sender_name'] = $comment_sender['fullname'];
                        $commentDetail['profile_pic'] = (!empty($comment_sender['profile_pic'])  ? $base_url.$comment_sender['profile_pic']: '');
                        $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);
                        $commentDetail['commentuser_name'] = array();
                        if (!empty($commentDetail['mention_users'])) {
                            foreach (explode(',', $commentDetail['mention_users']) as $key => $value) {
                                $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                                $commentDetail['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                            }
                        }
                    }
                }else{
                    $last_comment = array();
                }
                $lasttime_added = $detail['added_at'];
                
                $pollLink = $base_url.'web/?id='.$detail['post_id'];
                $poll_link = '<iframe src="'.$pollLink.'" height="200" width="300"></iframe>';

                $result[] = array(
                'poll_id' => $detail['post_id'],
                'poll_creator_id' => $detail['user_id'],
                'poll_title' => ($detail['type'] == 'image' ) ? $detail['question']: $detail['title'],
                'share_with' => $detail['share_with'],
                'repost_status' => $detail['repost_status'],
                'poll_type' => $detail['type'],
                'delayed_post' => $detail['delayed_post'],
                'delayed_text' => $detail['delayed_text'],
                'delayed_reveal' => $detail['delayed_reveal'],
                'is_anonymous' => $detail['poll_type'],
                'group_detail' => $group_array,
                'friend_detail' => $friend_array,
                'comment_count' => count($comment_post),
                'poll_link' => $poll_link,
                'poll_creator_name' => (!empty($post_sender['fullname'])  ? $post_sender['fullname'] : ''),
                'poll_creator_pic' => (!empty($post_sender['profile_pic'])  ? $base_url.$post_sender['profile_pic'] : ''),
                'poll_creator_unique_name' => (!empty($post_sender['unique_name'])  ? $post_sender['unique_name'] : ''),
                'poll_created_time' => $this->time_elapsed_string($datetime1, $detail['added_at']),
                'text' => $textDetails,
                'images' => $imageDetails,
                'last_comment' => $last_comment,
                );

                
            }
            
            $data = array(
                    'status' => 1,
                    //'base_url' => $base_url,
                    //'post_images_url' => $post_base_url,
                    'post_count' => $post_count,
                    'following_count' => $following_count,
                    'follower_count' => $follower_count,
                    'notification_count' => $notification_count,
                    'full_name' => (!empty($userValue['fullname']) ? $userValue['fullname'] : ''),
                    'unique_name' => (!empty($userValue['unique_name']) ? $userValue['unique_name'] : ''),
                    'profile_pic' => (!empty($userValue['profile_pic']) ? $userValue['profile_pic'] : ''),
                    'lasttime_added' => $lasttime_added,
                    'index' => $index,
                    'data' => $result
                );
            
        }else{
             $data =array(
                'status' => 1,
                'base_url' => $base_url,
                'post_images_url' => $post_base_url,
                'post_count' => $post_count,
                'following_count' => $following_count,
                'follower_count' => $follower_count,
                'notification_count' => $notification_count,
                'full_name' => (!empty($userValue['fullname']) ? $userValue['fullname'] : ''),
                'unique_name' => (!empty($userValue['unique_name']) ? $userValue['unique_name'] : ''),
                'profile_pic' => (!empty($userValue['profile_pic']) ? $userValue['profile_pic'] : ''),
                'index' => $index,
                'data' => array()
             );   
        }
        $this->response($data, 200);
    }

    public function voteTextPoll_post() {
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $text_id = $this->input->post('text_id');
        $this->check_empty($text_id, 'Please add text_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        //check post w.r.t image_id

        $post_chk = $this->common_model->findWhere($table = 'ws_text', array('id' => $text_id, 'post_id' => $post_id), $multi_record = false, $order = '');
        if (!empty($post_chk)) {
            $like = $this->common_model->findWhere($table = 'ws_text_likes', array('post_id' => $post_id, 'user_id' => $user_id, 'text_id' => $text_id), $multi_record = false, $order = '');
            //check already liked w.r.t image id
            if (empty($like)) {
                //update count of previously likes
                $newlike = $this->common_model->findWhere($table = 'ws_text_likes', array('post_id' => $post_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $previousliked_data = array('id' => $newlike['text_id']);

                $newlikeval = $this->common_model->findWhere($table = 'ws_text', array('id' => $newlike['text_id']), $multi_record = false, $order = '');
                $val = $newlikeval['likes'] - 1;
                $previouslyimagelike = array('likes' => $val);
                $this->common_model->updateWhere('ws_text', $previousliked_data, $previouslyimagelike);

                //delete previously liked any image w.r.t post start
                $deleteliked_data = array('post_id' => $post_id, 'user_id' => $user_id);
                $this->common_model->delete($table = 'ws_text_likes', $deleteliked_data);


                //delete previously liked any image w.r.t post end
                $post_data = array('text_id' => $text_id, 'user_id' => $user_id, 'post_id' => $post_id);
                $last_id = $this->common_model->add('ws_text_likes', $post_data);

                if ($last_id) {
                    //count no. of likes of image
                    $like_query = "SELECT * FROM ws_text_likes WHERE text_id = '$text_id'";
                    $like_result = $this->common_model->getQuery($like_query);
                    if (!empty($like_result)) {
                        $like_count = count($like_result);
                    } else {
                        $like_count = 0;
                    }

                    $textlike_data = array('likes' => $like_count);
                    $this->common_model->updateWhere('ws_text', $where_data = array('id' => $text_id), $textlike_data);

                    //send notification start for post
                    $post_text = $this->common_model->findWhere($table = 'ws_text', array('id' => $text_id), $multi_record = false, $order = '');
                    $post_id_val = $post_text['post_id'];
                    $post_owner = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id_val, 'status' => 1), $multi_record = false, $order = '');
                    $receiver = $post_owner['user_id'];

                    if ($receiver != $user_id) {
                        $notify_chk = $this->check_notification_set($receiver, 'vote');
                        //save notification
                        
                        if ($notify_chk == true) {
                            $this->save_notification($receiver, 'like', $user_id, $post_id_val);
                            $this->send_notification($receiver, $user_id, 'like', '', '', $post_id_val, '', '');
                            
                        }
                    }

                    //send notification end for post
                    $chk_unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');

                    if ($chk_unlike) {
                        $unlikechkremove_data = array('text_id' => $text_id, 'user_id' => $user_id);
                        $this->common_model->delete($table = 'ws_text_unlikes', $unlikechkremove_data);

                        $unlikechk_query = "SELECT count(*) as count FROM ws_text_unlikes WHERE text_id = '$text_id'";
                        $unlikechk_result = $this->common_model->getQuery($unlikechk_query);

                        $textunlikechk_data = array('unlikes' => $unlikechk_result[0]['count']);
                        $this->common_model->updateWhere('ws_text', $where_data = array('id' => $text_id), $textunlikechk_data);
                    }
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'Not able to vote your opinion'
                    );
                    $this->response($data, 200);
                }
            } else {
                //remove like
                $likeremove_data = array('text_id' => $text_id, 'user_id' => $user_id, 'post_id' => $post_id);
                $this->common_model->delete($table = 'ws_text_likes', $likeremove_data);

                //count change
                $likeremove_query = "SELECT * FROM ws_text_likes WHERE text_id = '$text_id'";
                $likeremove_result = $this->common_model->getQuery($likeremove_query);
                if (!empty($likeremove_result)) {
                    $likeremove_count = count($likeremove_result);
                } else {
                    $likeremove_count = 0;
                }
                //die;
                $textlikeremove_data = array('likes' => $likeremove_count);
                $this->common_model->updateWhere('ws_text', $where_data = array('id' => $text_id), $textlikeremove_data);
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Not able to remove your opinion'
            );
            $this->response($data, 200);
        }

        //text data
        $text = $this->db->select('id,text,likes')->get_where('ws_text', array('post_id' => $post_id))->result_array();
        $textDetails = array();
        $myTextOpinion = 0; //either user has voted on any option
        if (count($text) > 0) {
            foreach ($text as &$text_likes) {
                $likes_count = (int) $text_likes['likes'];
                $text_id = (int) $text_likes['id'];
                //likes status
                $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $text_likes['like_status'] = (!empty($like) ) ? 'liked' : 'not liked';
                if($text_likes['like_status'] == 'liked')
                {
                    $myTextOpinion =  1;
                }
            }
        }else{
            $text = array();
        }
        $textDetails['textOptions'] = $text;
        $textDetails['post_id'] = $post_id;
        $textDetails['myPollReaction'] = $myTextOpinion;

        $data = array(
            'status' => 1,
            'text' => $textDetails
        );
        $this->response($data, 200);
    }

    public function voteImageLikePoll_post() {
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $image_id = $this->input->post('image_id');
        $this->check_empty($image_id, 'Please add image_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        //check post w.r.t image_id

        $post_chk = $this->common_model->findWhere($table = 'ws_images', array('image_id' => $image_id, 'post_id' => $post_id), $multi_record = false, $order = '');
        if (!empty($post_chk)) {
            $like = $this->common_model->findWhere($table = 'ws_likes', array('post_id' => $post_id, 'user_id' => $user_id, 'image_id' => $image_id), $multi_record = false, $order = '');
            //check already liked w.r.t image id
            if (empty($like)) {

                //update count of previously likes
                $newlike = $this->common_model->findWhere($table = 'ws_likes', array('post_id' => $post_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $previousliked_data = array('image_id' => $newlike['image_id']);

                $newlikeval = $this->common_model->findWhere($table = 'ws_images', array('image_id' => $newlike['image_id']), $multi_record = false, $order = '');
                $val = $newlikeval['likes'] - 1;
                $previouslyimagelike = array('likes' => $val);
                $this->common_model->updateWhere('ws_images', $previousliked_data, $previouslyimagelike);

                //delete previously liked any image w.r.t post start
                $deleteliked_data = array('post_id' => $post_id, 'user_id' => $user_id);
                $this->common_model->delete($table = 'ws_likes', $deleteliked_data);

                //delete previously liked any image w.r.t post end

                $post_data = array('image_id' => $image_id, 'user_id' => $user_id, 'post_id' => $post_id);
                $last_id = $this->common_model->add('ws_likes', $post_data);

                if ($last_id) {
                    //count no. of likes of image
                    $like_query = "SELECT * FROM ws_likes WHERE image_id = '$image_id'";
                    $like_result = $this->common_model->getQuery($like_query);
                    if (!empty($like_result)) {
                        $like_count = count($like_result);
                    } else {
                        $like_count = 0;
                    }
                    //update no. of likes in images table
                    $imagelike_data = array('likes' => $like_count);
                    $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imagelike_data);

                    //send notification start for post

                    $post_img = $this->common_model->findWhere($table = 'ws_images', array('image_id' => $image_id), $multi_record = false, $order = '');
                    $post_id_val = $post_img['post_id'];
                    $post_owner = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id_val, 'status' => 1), $multi_record = false, $order = '');
                    $receiver = $post_owner['user_id'];

                    if ($receiver != $user_id) {
                        $notify_chk = $this->check_notification_set($receiver, 'vote');
                        //save notification
                        
                        if ($notify_chk == true) {
                            $this->save_notification($receiver, 'like', $user_id, $post_id_val);
                            $this->send_notification($receiver, $user_id, 'like', '', '', $post_id_val, '', '');
                            
                            //$this->save_multiple_notification($receiver, 'like', $user_id, $post_id_val);
                        }
                    }


                    //send notification end for post
                    //chk unlike(less unlike count by 1 if previously unliked)


                    $chk_unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $image_id, 'user_id' => $user_id), $multi_record = false, $order = '');

                    if ($chk_unlike) {
                        $unlikechkremove_data = array('image_id' => $image_id, 'user_id' => $user_id);
                        $this->common_model->delete($table = 'ws_unlikes', $unlikechkremove_data);

                        $unlikechk_query = "SELECT count(*) as count FROM ws_unlikes WHERE image_id = '$image_id'";
                        $unlikechk_result = $this->common_model->getQuery($unlikechk_query);

                        $imageunlikechk_data = array('unlikes' => $unlikechk_result[0]['count']);
                        $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imageunlikechk_data);
                    }
                    $data = array(
                        'status' => 1,
                        'message' => 'like added'
                    );
                } else {
                    $data = array(
                        'status' => 1,
                        'message' => 'Failed'
                    );
                }
            } else {
                //remove like
                $likeremove_data = array('image_id' => $image_id, 'user_id' => $user_id, 'post_id' => $post_id);
                $this->common_model->delete($table = 'ws_likes', $likeremove_data);

                //count change
                $likeremove_query = "SELECT * FROM ws_likes WHERE image_id = '$image_id'";
                $likeremove_result = $this->common_model->getQuery($likeremove_query);
                if (!empty($likeremove_result)) {
                    $likeremove_count = count($likeremove_result);
                } else {
                    $likeremove_count = 0;
                }
                //die;
                $imagelikeremove_data = array('likes' => $likeremove_count);
                $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imagelikeremove_data);
                $data = array(
                    'status' => 0,
                    'message' => 'Already liked post image'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Either post_id or image_id is wrong'
            );
        }

        //images data
        $images = $this->db->select('image_id ,image_name,crop_image,likes,unlikes')->get_where('ws_images', array('post_id' => $post_id))->result_array();
        $imageDetails = array();
        $myImageOpinion = 0; //either user has voted on any option. 0= neither liked nor disliked,1=liked,2=disliked
        if (count($images) > 0) {
            //check like of user id
            foreach ($images as &$images_likes) {
                $images_likes['image_name'] = (!empty($images_likes['image_name']) ) ? $post_base_url.$images_likes['image_name'] : '';
                $images_likes['crop_image'] = (!empty($images_likes['crop_image']) ) ? $post_base_url.$images_likes['crop_image'] : '';
                $likes_count = (int) $images_likes['likes'];

                $unlikes_count = (int) $images_likes['unlikes'];
                $img_id = (int) $images_likes['image_id'];
                //likes status
                $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $images_likes['like_status'] = (!empty($like) ) ? 'liked' : 'not liked';
                
                //unlikes status
                $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $images_likes['unlike_status'] = (!empty($unlike) ) ? 'disliked' : 'not disliked';

                if($images_likes['like_status'] == 'liked')
                {
                    $myImageOpinion =  1;
                }elseif($images_likes['unlike_status'] == 'disliked'){
                    $myImageOpinion =  2;
                }
            }
        }else{
            $images = array();
        }
        $imageDetails['imageOptions'] = $images;
        $imageDetails['post_id'] = $detail['post_id'];
        $imageDetails['myPollReaction'] = $myImageOpinion;

        $data = array(
            'status' => 1,
            'image' => $imageDetails
        );
        $this->response($data, 200);
    }

    public function voteImageUnlikePoll_post() {

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $image_id = $this->input->post('image_id');
        $this->check_empty($image_id, 'Please add image_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');

        $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $image_id, 'user_id' => $user_id), $multi_record = false, $order = '');
        //check already unliked
        if (empty($unlike)) {

            $post_data = array('image_id' => $image_id, 'user_id' => $user_id);
            $last_id = $this->common_model->add('ws_unlikes', $post_data);

            if ($last_id) {
                //count no. of unlikes of image
                $unlike_query = "SELECT * FROM ws_unlikes WHERE image_id = '$image_id'";
                $unlike_result = $this->common_model->getQuery($unlike_query);

                if (!empty($unlike_result)) {
                    $unlike_count = count($unlike_result);
                } else {
                    $unlike_count = 0;
                }
                //update no. of unlikes in images table

                $imageunlike_data = array('unlikes' => $unlike_count);
                $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imageunlike_data);
                
                //count no. of likes of image
                $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $image_id, 'user_id' => $user_id), $multi_record = false, $order = '');

                //check like
                if (!empty($like)) {
                    $likeupdated_data = array('image_id' => $image_id, 'user_id' => $user_id);
                    $this->common_model->delete($table = 'ws_likes', $likeupdated_data);
                    $like_query = "SELECT * FROM ws_likes WHERE image_id = '$image_id'";
                    $like_result = $this->common_model->getQuery($like_query);
                    if (!empty($like_result)) {
                        $like_count = count($like_result);
                    } else {
                        $like_count = 0;
                    }
                    //update no. of likes in images table
                    $imagelike_data = array('likes' => $like_count);
                    $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imagelike_data);
                }


                //send notification start for post

                $post_img = $this->common_model->findWhere($table = 'ws_images', array('image_id' => $image_id), $multi_record = false, $order = '');
                $post_id_val = $post_img['post_id'];
                $post_owner = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id_val, 'status' => 1), $multi_record = false, $order = '');
                $receiver = $post_owner['user_id'];
                
                //check notification status
                $notify_chk = $this->check_notification_set($receiver, 'post');
                //save notification
                
                if ($notify_chk == true && $post_owner['user_id'] != $user_id) {
                     $this->save_notification($receiver , 'unlike' , $user_id , $post_id_val);
                    $this->send_notification($receiver , $user_id , 'unlike' , '' , '' , $post_id_val);
                   
                }

                //send notification end for post

                $data = array(
                    'status' => 1,
                    'message' => 'unlike added'
                );
            } else {
                $data = array(
                    'status' => 1,
                    'message' => 'Failed'
                );
            }
        } else {
            //remove unlike
            $unlikeremove_data = array('image_id' => $image_id, 'user_id' => $user_id);
            $this->common_model->delete($table = 'ws_unlikes', $unlikeremove_data);

            //count no. of unlikes of image
            $unlike_query = "SELECT * FROM ws_unlikes WHERE image_id = '$image_id'";
            $unlike_result = $this->common_model->getQuery($unlike_query);
            if (!empty($unlike_result)) {
                $unlike_count = count($unlike_result);
            } else {
                $unlike_count = 0;
            }
            //update no. of unlikes in images table
            $imageunlike_data = array('unlikes' => $unlike_count);
            $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imageunlike_data);

            //chk like(less like count by 1 if previously liked)
            $chk_like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $image_id, 'user_id' => $user_id), $multi_record = false, $order = '');
            if ($chk_like) {
                $likechkremove_data = array('image_id' => $image_id, 'user_id' => $user_id);
                $this->common_model->delete($table = 'ws_likes', $likechkremove_data);

                $likechk_query = "SELECT count(*) as count FROM ws_likes WHERE image_id = '$image_id'";
                $likechk_result = $this->common_model->getQuery($likechk_query);

                $imagelikechk_data = array('likes' => $likechk_result[0]['count']);
                $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imagelikechk_data);
            }
            $data = array(
                'status' => 0,
                'message' => 'Already unliked'
            );
        }

        //images data
        $images = $this->db->select('image_id ,image_name,crop_image,likes,unlikes')->get_where('ws_images', array('post_id' => $post_id))->result_array();
        $imageDetails = array();
        $myImageOpinion = 0; //either user has voted on any option. 0= neither liked nor disliked,1=liked,2=disliked
        if (count($images) > 0) {
            //check like of user id
            foreach ($images as &$images_likes) {
                $images_likes['image_name'] = (!empty($images_likes['image_name']) ) ? $post_base_url.$images_likes['image_name'] : '';
                $images_likes['crop_image'] = (!empty($images_likes['crop_image']) ) ? $post_base_url.$images_likes['crop_image'] : '';
                $likes_count = (int) $images_likes['likes'];

                $unlikes_count = (int) $images_likes['unlikes'];
                $img_id = (int) $images_likes['image_id'];
                //likes status
                $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $images_likes['like_status'] = (!empty($like) ) ? 'liked' : 'not liked';
                
                //unlikes status
                $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $images_likes['unlike_status'] = (!empty($unlike) ) ? 'disliked' : 'not disliked';

                if($images_likes['like_status'] == 'liked')
                {
                    $myImageOpinion =  1;
                }elseif($images_likes['unlike_status'] == 'disliked'){
                    $myImageOpinion =  2;
                }
            }
        }else{
            $images = array();
        }
        $imageDetails['imageOptions'] = $images;
        $imageDetails['post_id'] = $detail['post_id'];
        $imageDetails['myPollReaction'] = $myImageOpinion;

        $data = array(
            'status' => 1,
            'image' => $imageDetails
        );

        //images data
        $this->response($data, 200);
    }

    function get_task_post() {

        $limit = 10;

        $offset = $this->input->post('offset');
        $this->check_integer_empty($offset, 'Please add offset');

        $base_url = $this->baseurl;
        //$post_base_url = $this->baseurl . 'uploads/post_images/';
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');

        $type = $this->input->post('type');
        $this->check_empty($type, 'Please add type');

        $datetime1 = date('Y-m-d H:i:s', time());
        //$datetime1 = $this->input->post('datetime1');
        //$this->check_empty($datetime1, 'Please add datetime1');

        //check on last updated
        $last_used = array('last_used_app' => date('Y-m-d h:i'));
        $this->common_model->updateWhere('ws_users', $where_data = array('id' => $user_id), $last_used);

        //check on last updated

        if ($type == 'my_post') {
            /* get all the posts by the user */
            $result1 = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $user_id, 'status' => '1'))->result_array();
            $result = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $user_id, 'status' => '1') , $limit, $offset)->result_array();
            
            //extra detail
            $post_count = count($result1);
            $userValue = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');
            $following = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('user_id' => $user_id))->result_array();
            $following_count = count($following);
            $follower = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
            $follower_count = count($follower);
            $notification = $this->db->order_by('added_at', 'desc')->get_where('ws_notifications', array('receiver_id' => $user_id, 'status' => 0))->result_array();
            $notification_count = count($notification);
            //extra detail
            if (empty($result)) {
                $result = array();
            } else {
                /* provide the number of likes , last comment and images for a post */
                //echo '<pre>';print_r($result);die;
                foreach ($result as &$post) {
                    //comment count
                    $postID = $post['post_id'];
                    $poll_link = $base_url.'web/?id='.$postID;
                    $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
                    $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
                    $commentCount = $this->common_model->getQuery($comment_post);

                    $post['comment_count'] = $commentCount[0]['count'];
                    //post creator
                    $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                    $post['post_creator'] = (!empty($post_sender['fullname']) ) ? $post_sender['fullname'] : '';
                    $post['creator_pic'] = (!empty($post_sender['profile_pic']) ) ? $base_url . $post_sender['profile_pic'] : '';
                    $post['creator_unique_name'] = (!empty($post_sender['unique_name']) ) ? $post_sender['unique_name'] : '';
                    $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);
                    //group_name 
                    $sharegroup_name = array();
                    if ($post['group_id'] != 0) {
                        foreach (explode(',', $post['group_id']) as $key => $value) {
                            $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                            $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                        }
                    } else {
                        $sharegroup_name = '';
                    }
                    $post['group_name'] = $sharegroup_name;
                    //frnd name
                    $sharefriend_name = array();
                    if ($post['friend_id'] != 0) {
                        foreach (explode(',', $post['friend_id']) as $key => $value) {
                            $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value, 'activated' => 1), $multi_record = false, $order = '');
                            $sharefriend_name[] = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                        }
                    } else {
                        $sharefriend_name = '';
                    }
                    $post['friend_name'] = $sharefriend_name;

                    //text data
                    $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                    $totalPostTextLikes =0;
                    foreach($text as $tx)
                    {
                        $totalPostTextLikes +=  $tx['likes'];
                    }
                    $total_textprop = 0;
                    $loop_textindex = 0;
                    if (count($text) > 0) {
                        $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();

                        //check like of user id
                        foreach ($post['text'] as &$text_likes) {
                            $loop_textindex++;
                            $likes_count = (int) $text_likes['likes'];

                            $unlikes_count = (int) $text_likes['unlikes'];
                            $text_id = (int) $text_likes['id'];

                            
                            //likes status
                            if ($likes_count > 0) {

                                $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                                if(count($text) == $loop_textindex && $loop_textindex > 1){
                                    $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                                }else{
                                    $prop = round ( $likesproportion);
                                    $total_textprop += $prop;
                                    $text_likes['likes_proportion'] = $prop.'%';
                                }

                                $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                $text_likes['like_status'] = $like_status;
                                

                                //likes array

                                $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                $this->db->from('ws_text_likes l');
                                $this->db->join('ws_users u', 'l.user_id = u.id');
                                $this->db->where('l.text_id', $text_id);

                                $text_likes['likes_detail'] = $this->db->get()->result_array();
                                if ($text_likes['likes_detail']) {
                                    foreach ($text_likes['likes_detail'] as &$detail_friend) {
                                        $fr_id = $detail_friend['user_id'];
                                        $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                        $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                        if ($like_frnd) {
                                            $detail_friend['is_friend'] = 1;
                                        } else {
                                            $detail_friend['is_friend'] = 0;
                                        }
                                    }
                                }
                            } else {
                                $text_likes['likes_detail'] = array();
                                $text_likes['like_status'] = 'not liked';
                                $text_likes['likes_proportion'] = '0%';
                            }

                            //unlikes array
                            if ($unlikes_count > 0) {

                                $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                $text_likes['unlike_status'] = $unlike_status;
                                //unlikes array

                                $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                $this->db->from('ws_text_unlikes ul');
                                $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                $this->db->where('ul.text_id', $text_id);

                                $text_likes['unlikes_detail'] = $this->db->get()->result_array();
                                if ($text_likes['unlikes_detail']) {
                                    foreach ($text_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                        $fr_id = $unlikedetail_friend['user_id'];
                                        $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                        $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                        if ($unlike_frnd) {
                                            $unlikedetail_friend['is_friend'] = 1;
                                        } else {
                                            $unlikedetail_friend['is_friend'] = 0;
                                        }
                                    }
                                }
                            } else {
                                $text_likes['unlikes_detail'] = array();
                                $text_likes['unlike_status'] = 'not disliked';
                            }
                        }
                    } else {
                        $post['text'] = array();
                    }

                    //images data

                    $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                    $totalPostImageLikes =0;
                    $totalPostImageUnlikes =0;
                    foreach($img as $im)
                    {
                    $totalPostImageLikes +=  $im['likes'];
                    $totalPostImageUnlikes +=  $im['unlikes'];
                    }
                    $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
                    $total_imgprop = 0;
                    $loop_imgindex = 0;
                    if (count($img) > 0) {
                        $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();

                        //check like of user id
                        foreach ($post['images'] as &$images_likes) {
                            $loop_imgindex++;
                            $likes_count = (int) $images_likes['likes'];

                            $unlikes_count = (int) $images_likes['unlikes'];
                            $img_id = (int) $images_likes['image_id'];

                            
                            //likes status
                            if ($likes_count > 0) {
                                $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                                if(count($img) == $loop_imgindex  && $loop_imgindex > 1){
                                $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                                }else{
                                $prop = round ( $likesproportion);
                                $total_imgprop += $prop;
                                $images_likes['likes_proportion'] = $prop.'%';
                                }

                                $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                $images_likes['like_status'] = $like_status;
                                

                                //likes array

                                $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                $this->db->from('ws_likes l');
                                $this->db->join('ws_users u', 'l.user_id = u.id');
                                $this->db->where('l.image_id', $img_id);

                                $images_likes['likes_detail'] = $this->db->get()->result_array();
                                if ($images_likes['likes_detail']) {
                                    foreach ($images_likes['likes_detail'] as &$detail_friend) {
                                        $fr_id = $detail_friend['user_id'];
                                        $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                        $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                        if ($like_frnd) {
                                            $detail_friend['is_friend'] = 1;
                                        } else {
                                            $detail_friend['is_friend'] = 0;
                                        }
                                    }
                                }
                            } else {
                                $images_likes['likes_detail'] = array();
                                $images_likes['like_status'] = 'not liked';
                                $images_likes['likes_proportion'] = '0%';
                            }

                            //unlikes array
                            $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                            if(count($img) == 1){
                                $prop = round ( $unlikesproportion);
                                $total_imgprop += $prop;
                                $images_likes['unlikes_proportion'] = $prop.'%';
                            }
                            if ($unlikes_count > 0) {

                                $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                $images_likes['unlike_status'] = $unlike_status;
                                
                                //unlikes array

                                $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                $this->db->from('ws_unlikes ul');
                                $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                $this->db->where('ul.image_id', $img_id);

                                $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                                if ($images_likes['unlikes_detail']) {
                                    foreach ($images_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                        $fr_id = $unlikedetail_friend['user_id'];
                                        $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                        $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                        if ($unlike_frnd) {
                                            $unlikedetail_friend['is_friend'] = 1;
                                        } else {
                                            $unlikedetail_friend['is_friend'] = 0;
                                        }
                                    }
                                }
                            } else {
                                $images_likes['unlikes_detail'] = array();
                                $images_likes['unlike_status'] = 'not disliked';
                            }
                        }
                    } else {
                        $post['images'] = array();
                    }
                    //videos data
                    $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

                    if (count($vid) > 0) {
                        $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                        foreach ($post['video'] as &$video_likes) {
                            
                            $likes_count = (int) $video_likes['likes'];

                            $unlikes_count = (int) $video_likes['unlikes'];
                            $vid_id = (int) $video_likes['video_id'];
                            //likes status
                            if ($likes_count > 0) {
                                $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                $video_likes['like_status'] = $like_status;

                                //likes array

                                $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                $this->db->from('ws_video_likes l');
                                $this->db->join('ws_users u', 'l.user_id = u.id');
                                $this->db->where('l.video_id', $vid_id);

                                $video_likes['likes_detail'] = $this->db->get()->result_array();

                                if ($video_likes['likes_detail']) {
                                    foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                        $fr_id = $vidlikedetail_friend['user_id'];
                                        $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                        $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                        if ($vidlike_frnd) {
                                            $vidlikedetail_friend['is_friend'] = 1;
                                        } else {
                                            $vidlikedetail_friend['is_friend'] = 0;
                                        }
                                    }
                                }
                            } else {
                                $video_likes['likes_detail'] = array();
                                $video_likes['like_status'] = 'not liked';
                            }

                            //unlikes array
                            if ($unlikes_count > 0) {
                            $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                            $video_likes['unlike_status'] = $unlike_status;

                            $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                            $this->db->from('ws_video_unlikes ul');
                            $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                            $this->db->where('ul.video_id', $vid_id);

                            $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                        } else {
                            $video_likes['unlikes_detail'] = array();
                            $video_likes['unlike_status'] = 'not disliked';
                        }
                        }
                    } else {
                        $post['video'] = array();
                    }

                    //last comment data
                    $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();


                    if (count($last_comment) > 0) {
                        //$post['last_comment'] = array();
                        $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();
                        sort($post['last_comment']);

                        foreach ($post['last_comment'] as &$commentDetail) {
                            $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                            $commentDetail['sender_name'] = $comment_sender['fullname'];
                            $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                            $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);
                            $commentDetail['commentuser_name'] = array();
                            if (!empty($commentDetail['mention_users'])) {
                                foreach (explode(',', $commentDetail['mention_users']) as $key => $value) {
                                    $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                                    $commentDetail['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                                }
                            }
                        }
                    } else {
                        //$post['last_comment'] = json_decode('{}');
                        $post['last_comment'] = array();
                    }

                    //tag detail start
                    $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
                    if (count($tag) > 0) {
                        $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                        //check like of user id
                        foreach ($post['tagged_data'] as &$tags) {
                            $user_id_val = (int) $tags['user_id'];
                            if ($user_id_val > 0) {
                                $tag_frnd_id = $user_id_val;
                                $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                                $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                                $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                                $tags['tag_frnd'] = $tag_frnd_name;
                            } else {
                                $tags['tag_frnd'] = '';
                            }
                        }
                    } else {
                        $post['tagged_data'] = array();
                    }

                    $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                    if (count($tagwrd) > 0) {
                        $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                    } else {
                        $post['taggedword_data'] = array();
                    }
                }
            }

            $data = array(
                'status' => 1,
                'base_url' => $base_url,
                'post_images_url' => $post_base_url,
                'post_count' => $post_count,
                'following_count' => $following_count,
                'follower_count' => $follower_count,
                'notification_count' => $notification_count,
                'full_name' => (!empty($userValue['fullname']) ? $userValue['fullname'] : ''),
                'unique_name' => (!empty($userValue['unique_name']) ? $userValue['unique_name'] : ''),
                'profile_pic' => (!empty($userValue['profile_pic']) ? $userValue['profile_pic'] : ''),
                'data' => $result
            );

            $this->response($data, 200);
        } else if ($type == 'friends_post') {
            /* Get all the posts by the friends of the user */

            $this->db->select('friend_id');
            $friends1 = $this->db->get_where('ws_friend_list', array('user_id' => $user_id, 'status' => '1'))->result_array();


            $this->db->select('user_id as friend_id');
            $friends2 = $this->db->get_where('ws_friend_list', array('friend_id' => $user_id, 'status' => '1'))->result_array();
            $friends2 = array_values($friends2);

            $friend = array_merge($friends1, $friends2);
            $friendDetail = array();
            //echo '<pre>';print_r($friend);die;
            foreach ($friend as $fr) {
                if (count($this->db->get_where('ws_posts', array('user_id' => $fr['friend_id'], 'status' => 1))->result_array()) > 0) {
                    $friends_posts = $this->db->get_where('ws_posts', array('user_id' => $fr['friend_id'], 'status' => 1) , $limit, $offset)->result_array();
                }
                if (!empty($friends_posts)) {
                    foreach ($friends_posts as &$post) {//start friendpost
                        //comment count
                        $postID = $post['post_id'];
                        $poll_link = $base_url.'web/?id='.$postID;
                        $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
                        $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
                        $commentCount = $this->common_model->getQuery($comment_post);

                        $post['comment_count'] = $commentCount[0]['count'];

                        //post creator

                        $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                        $post['post_creator'] = $post_sender['fullname'];
                        $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);

                        //group_name 
                        $sharegroup_name = '';
                        if ($post['group_id'] != 0) {
                            foreach (explode(',', $post['group_id']) as $key => $value) {
                                $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                                $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                            }
                        } else {
                            $sharegroup_name = '';
                        }
                        $post['group_name'] = $sharegroup_name;
                        //frnd name
                        if ($post['friend_id'] != 0) {
                            $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['friend_id'], 'activated' => 1), $multi_record = false, $order = '');
                            $sharefriend_name = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                        } else {
                            $sharefriend_name = '';
                        }
                        $post['friend_name'] = $sharefriend_name;

                        //text data
                        $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                        $totalPostTextLikes =0;
                        foreach($text as $tx)
                        {
                            $totalPostTextLikes +=  $tx['likes'];
                        }
                        $total_textprop = 0;
                        $loop_textindex = 0;
                        if (count($text) > 0) {
                            $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                            //check like of user id
                            //echo '<pre>';print_r($post['images']);die;
                            foreach ($post['text'] as &$text_likes) {
                                $loop_textindex++;
                                $likes_count = (int) $text_likes['likes'];
                                $unlikes_count = (int) $text_likes['unlikes'];
                                $text_id = (int) $text_likes['id'];

                                
                                if ($likes_count > 0) {

                                    $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                                    if(count($text) == $loop_textindex && $loop_textindex > 1){
                                    $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                                    }else{
                                    $prop = round ( $likesproportion);
                                    $total_textprop += $prop;
                                    $text_likes['likes_proportion'] = $prop.'%';
                                    }
                                    
                                    $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $text_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_text_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.text_id', $text_id);

                                    $text_likes['likes_detail'] = $this->db->get()->result_array();

                                    if ($text_likes['likes_detail']) {
                                        foreach ($text_likes['likes_detail'] as &$detail_friend) {
                                            $fr_id = $detail_friend['user_id'];
                                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                            if ($like_frnd) {
                                                $detail_friend['is_friend'] = 1;
                                            } else {
                                                $detail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $text_likes['like_status'] = 'not liked';
                                    $text_likes['likes_detail'] = array();
                                    $text_likes['likes_proportion'] = '0%';
                                }

                                //unlikes array
                                if ($unlikes_count > 0) {
                                    $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $text_likes['unlike_status'] = $unlike_status;

                                    //unlikes array
                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_text_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.text_id', $text_id);

                                    $text_likes['unlikes_detail'] = $this->db->get()->result_array();

                                    if ($text_likes['unlikes_detail']) {
                                        foreach ($text_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                            $fr_id = $unlikedetail_friend['user_id'];
                                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                            if ($unlike_frnd) {
                                                $unlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $unlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $text_likes['unlikes_detail'] = array();
                                    $text_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['text'] = array();
                        }

                        //images detail
                        $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                        $totalPostImageLikes =0;
                        $totalPostImageUnlikes =0;
                        foreach($img as $im)
                        {
                        $totalPostImageLikes +=  $im['likes'];
                        $totalPostImageUnlikes +=  $im['unlikes'];
                        }
                        $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
                        $total_imgprop = 0;
                        $loop_imgindex = 0;
                        if (count($img) > 0) {
                            $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                            //check like of user id
                            foreach ($post['images'] as &$images_likes) {
                                $loop_imgindex++;
                                $likes_count = (int) $images_likes['likes'];
                                $unlikes_count = (int) $images_likes['unlikes'];
                                $img_id = (int) $images_likes['image_id'];

                                
                                if ($likes_count > 0) {

                                    $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                                    if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                                    $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                                    }else{
                                    $prop = round ( $likesproportion);
                                    $total_imgprop += $prop;
                                    $images_likes['likes_proportion'] = $prop.'%';
                                    }
                                    
                                    $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $images_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.image_id', $img_id);

                                    $images_likes['likes_detail'] = $this->db->get()->result_array();

                                    if ($images_likes['likes_detail']) {
                                        foreach ($images_likes['likes_detail'] as &$detail_friend) {
                                            $fr_id = $detail_friend['user_id'];
                                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                            if ($like_frnd) {
                                                $detail_friend['is_friend'] = 1;
                                            } else {
                                                $detail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $images_likes['like_status'] = 'not liked';
                                    $images_likes['likes_detail'] = array();
                                    $images_likes['likes_proportion'] = '0%';
                                }

                                //unlikes array
                                $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                                //$images_likes['unlikes_proportion'] = (  (round ( $unlikesproportion , 0) ) ).'%';
                                if(count($img) == 1){
                                    $prop = round ( $unlikesproportion);
                                    $total_imgprop += $prop;
                                    $images_likes['unlikes_proportion'] = $prop.'%';
                                }
                                if ($unlikes_count > 0) {
                                    $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $images_likes['unlike_status'] = $unlike_status;

                                    //unlikes array

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.image_id', $img_id);

                                    $images_likes['unlikes_detail'] = $this->db->get()->result_array();

                                    if ($images_likes['unlikes_detail']) {
                                        foreach ($images_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                            $fr_id = $unlikedetail_friend['user_id'];
                                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                            if ($unlike_frnd) {
                                                $unlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $unlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $images_likes['unlikes_detail'] = array();
                                    $images_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['images'] = array();
                        }
                        //videos detail
                        $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

                        if (count($vid) > 0) {
                            $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                            foreach ($post['video'] as &$video_likes) {
                                
                                $likes_count = (int) $video_likes['likes'];

                                $unlikes_count = (int) $video_likes['unlikes'];
                                $vid_id = (int) $video_likes['video_id'];
                                //likes status
                                if ($likes_count > 0) {
                                    $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $video_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_video_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.video_id', $vid_id);

                                    $video_likes['likes_detail'] = $this->db->get()->result_array();

                                    if ($video_likes['likes_detail']) {
                                        foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                            $fr_id = $vidlikedetail_friend['user_id'];
                                            $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                            if ($vidlike_frnd) {
                                                $vidlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $vidlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $video_likes['likes_detail'] = array();
                                    $video_likes['like_status'] = 'not liked';
                                }

                                //unlikes array
                                if ($unlikes_count > 0) {
                                    //unlikes array
                                    $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $video_likes['unlike_status'] = $unlike_status;

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_video_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.video_id', $vid_id);

                                    $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                                } else {
                                    $video_likes['unlikes_detail'] = array();
                                    $video_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['video'] = array();
                        }

                        //last comment detail
                        $last_comment = $this->db->order_by('added_at', 'ASC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();

                        if (count($last_comment) > 0) {
                            $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();
                            sort($post['last_comment']);
                           
                            foreach ($post['last_comment'] as &$commentDetail) {
                                $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                                $commentDetail['sender_name'] = $comment_sender['fullname'];
                                $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                                $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);
                            }
                        } else {
                            $post['last_comment'] = array();
                        }

                        //tag detail start

                        $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
                        if (count($tag) > 0) {
                            $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                            //check like of user id
                            foreach ($post['tagged_data'] as &$tags) {
                                $user_id_val = (int) $tags['user_id'];
                                if ($user_id_val > 0) {
                                    $tag_frnd_id = $user_id_val;
                                    //echo 'yguhu'.$img_id;die;
                                    $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                                    $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                                    $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                                    $tags['tag_frnd'] = $tag_frnd_name;
                                } else {
                                    $tags['tag_frnd'] = '';
                                }
                            }
                        } else {
                            $post['tagged_data'] = array();
                        }

                        $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                        if (count($tagwrd) > 0) {
                            $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                        } else {
                            $post['taggedword_data'] = array();
                        }
                        //tag detail end
                    }//end friendpost foreach
                    
                } else {
                    $friends_posts = array();
                }
                foreach ($friends_posts as $key => $value) {
                    array_push($friendDetail, $value);
                }
            }
            $data = array(
                'status' => 1,
                'base_url' => $base_url,
                'post_images_url' => $post_base_url,
                'data' => $friendDetail
            );

            $this->response($data, 200);

            /* if the user is following celebrities */
        } elseif ($type == 'celeb_post') {
            $celeb_ids = $this->db->get_where('ws_followers_celebrity', array('user_id' => $user_id))->result_array();

            $celebDetail = array();
            $celeb_data = array();
            foreach ($celeb_ids as $fr) {

                if (count($this->db->get_where('ws_posts', array('user_id' => $fr['celebrity_id'], 'status' => 1))->result_array()) > 0) {
                    $celeb_data = $this->db->get_where('ws_posts', array('user_id' => $fr['celebrity_id'], 'status' => 1) , $limit, $offset)->result_array();
                }
                if (!empty($celeb_data)) {
                    
                    foreach ($celeb_data as &$post) {

                        $postID = $post['post_id'];
                        $poll_link = $base_url.'web/?id='.$postID;
                        $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
                        $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
                        $commentCount = $this->common_model->getQuery($comment_post);

                        $post['comment_count'] = $commentCount[0]['count'];

                        //post creator

                        $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                        $post['post_creator'] = $post_sender['fullname'];
                        $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);

                        //group_name 
                        $sharegroup_name = '';
                        if ($post['group_id'] != 0) {
                            foreach (explode(',', $post['group_id']) as $key => $value) {
                                $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                                $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                            }
                        } else {
                            $sharegroup_name = '';
                        }
                        $post['group_name'] = $sharegroup_name;
                        //frnd name
                        if ($post['friend_id'] != 0) {
                            $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['friend_id'], 'activated' => 1), $multi_record = false, $order = '');
                            $sharefriend_name = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                        } else {
                            $sharefriend_name = '';
                        }
                        $post['friend_name'] = $sharefriend_name;

                        //text data
                        $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                        $totalPostTextLikes =0;
                        foreach($text as $tx)
                        {
                            $totalPostTextLikes +=  $tx['likes'];
                        }
                        $total_textprop = 0;
                        $loop_textindex = 0;
                        if (count($text) > 0) {
                            $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                            //check like of user id
                            //echo '<pre>';print_r($post['images']);die;
                            foreach ($post['text'] as &$text_likes) {
                                $loop_textindex++;
                                $likes_count = (int) $text_likes['likes'];
                                $unlikes_count = (int) $text_likes['unlikes'];
                                $text_id = (int) $text_likes['id'];

                                
                                if ($likes_count > 0) {

                                    $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                                    if(count($text) == $loop_textindex && $loop_textindex > 1){
                                    $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                                    }else{
                                    $prop = round ( $likesproportion);
                                    $total_textprop += $prop;
                                    $text_likes['likes_proportion'] = $prop.'%';
                                    }
                                    //echo 'yguhu'.$img_id;die;
                                    $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $text_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_text_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.text_id', $text_id);

                                    $text_likes['likes_detail'] = $this->db->get()->result_array();

                                    if ($text_likes['likes_detail']) {
                                        foreach ($text_likes['likes_detail'] as &$detail_friend) {
                                            $fr_id = $detail_friend['user_id'];
                                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                            if ($like_frnd) {
                                                $detail_friend['is_friend'] = 1;
                                            } else {
                                                $detail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $text_likes['like_status'] = 'not liked';
                                    $text_likes['likes_detail'] = array();
                                    $text_likes['likes_proportion'] = '0%';
                                }

                                //unlikes array
                                if ($unlikes_count > 0) {
                                    $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $text_likes['unlike_status'] = $unlike_status;

                                    //unlikes array

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_text_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.image_id', $img_id);

                                    $text_likes['unlikes_detail'] = $this->db->get()->result_array();

                                    if ($text_likes['unlikes_detail']) {
                                        foreach ($text_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                            $fr_id = $unlikedetail_friend['user_id'];
                                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                            if ($unlike_frnd) {
                                                $unlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $unlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $text_likes['unlikes_detail'] = array();
                                    $text_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['text'] = array();
                        }

                        //images detail
                        $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                        $totalPostImageLikes =0;
                        $totalPostImageUnlikes =0;
                        foreach($img as $im)
                        {
                        $totalPostImageLikes +=  $im['likes'];
                        $totalPostImageUnlikes +=  $im['unlikes'];
                        }
                        $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
                        $total_imgprop = 0;
                        $loop_imgindex = 0;
                        if (count($img) > 0) {
                            $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                            foreach ($post['images'] as &$images_likes) {
                                $loop_imgindex++;
                                $likes_count = (int) $images_likes['likes'];
                                $unlikes_count = (int) $images_likes['unlikes'];
                                $img_id = (int) $images_likes['image_id'];

                                
                                if ($likes_count > 0) {
                                    $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                                    if(count($img) == $loop_imgindex  && $loop_imgindex > 1){
                                    $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                                    }else{
                                    $prop = round ( $likesproportion);
                                    $total_imgprop += $prop;
                                    $images_likes['likes_proportion'] = $prop.'%';
                                    }

                                    //echo 'yguhu'.$img_id;die;
                                    $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $images_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.image_id', $img_id);

                                    $images_likes['likes_detail'] = $this->db->get()->result_array();

                                    if ($images_likes['likes_detail']) {
                                        foreach ($images_likes['likes_detail'] as &$detail_friend) {
                                            $fr_id = $detail_friend['user_id'];
                                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                            if ($like_frnd) {
                                                $detail_friend['is_friend'] = 1;
                                            } else {
                                                $detail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $images_likes['like_status'] = 'not liked';
                                    $images_likes['likes_detail'] = array();
                                    $images_likes['likes_proportion'] = '0%';
                                }

                                //unlikes array
                                $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                                if(count($img) == 1){
                                    $prop = round ( $unlikesproportion);
                                    $total_imgprop += $prop;
                                    $images_likes['unlikes_proportion'] = $prop.'%';
                                    }
                                if ($unlikes_count > 0) {
                                    $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $images_likes['unlike_status'] = $unlike_status;

                                    //unlikes array

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.image_id', $img_id);

                                    $images_likes['unlikes_detail'] = $this->db->get()->result_array();

                                    if ($images_likes['unlikes_detail']) {
                                        foreach ($images_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                            $fr_id = $unlikedetail_friend['user_id'];
                                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                            if ($unlike_frnd) {
                                                $unlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $unlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $images_likes['unlikes_detail'] = array();
                                    $images_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['images'] = array();
                        }
                        //videos detail
                        $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

                        if (count($vid) > 0) {
                            $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                            foreach ($post['video'] as &$video_likes) {
                                
                                $likes_count = (int) $video_likes['likes'];

                                $unlikes_count = (int) $video_likes['unlikes'];
                                $vid_id = (int) $video_likes['video_id'];
                                
                                //likes status
                                if ($likes_count > 0) {
                                    $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $video_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_video_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.video_id', $vid_id);

                                    $video_likes['likes_detail'] = $this->db->get()->result_array();

                                    if ($video_likes['likes_detail']) {
                                        foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                            $fr_id = $vidlikedetail_friend['user_id'];
                                            $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                            if ($vidlike_frnd) {
                                                $vidlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $vidlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $video_likes['likes_detail'] = array();
                                    $video_likes['like_status'] = 'not liked';
                                }

                                //unlikes array
                                if ($unlikes_count > 0) {
                                    //unlikes array
                                    $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $video_likes['unlike_status'] = $unlike_status;

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_video_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.video_id', $vid_id);

                                    $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                                } else {
                                    $video_likes['unlikes_detail'] = array();
                                    $video_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['video'] = array();
                        }
                        //last comment
                        $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();

                        if (count($last_comment) > 0) {
                            $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();
                            sort($post['last_comment']);
                           foreach ($post['last_comment'] as &$commentDetail) {
                                $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                                $commentDetail['sender_name'] = $comment_sender['fullname'];
                                $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                                $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);
                            }
                        } else {
                            $post['last_comment'] = array();
                        }

                        //tag detail start
                        $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
                        if (count($tag) > 0) {
                            $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                            //check like of user id
                            foreach ($post['tagged_data'] as &$tags) {
                                $user_id_val = (int) $tags['user_id'];
                                if ($user_id_val > 0) {
                                    $tag_frnd_id = $user_id_val;
                                    //echo 'yguhu'.$img_id;die;
                                    $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                                    $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                                    $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                                    $tags['tag_frnd'] = $tag_frnd_name;
                                } else {
                                    $tags['tag_frnd'] = '';
                                }
                            }
                        } else {
                            $post['tagged_data'] = array();
                        }

                        $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                        if (count($tagwrd) > 0) {
                            $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                        } else {
                            $post['taggedword_data'] = array();
                        }
                        //tag detail end
                    }
                } else {
                    $celeb_data = array();
                }
                foreach ($celeb_data as $key => $value) {
                    array_push($celebDetail, $value);
                }
            }
            $data = array(
                'status' => 1,
                'base_url' => $base_url,
                'post_images_url' => $post_base_url,
                'data' => $celebDetail
            );

            $this->response($data, 200);
        } elseif ($type == 'group_post') {
            /* group post */

            $group_ids = $this->db->get_where('ws_group_members', array('member_id' => $user_id))->result_array();
            $GROUPDETAIL = array();
            $group_data = array();
            foreach ($group_ids as $fr) {

                if (count($this->db->get_where('ws_posts', array('group_id' => $fr['group_id'], 'status' => 1))->result_array()) > 0) {
                    $group_data = $this->db->get_where('ws_posts', array('group_id' => $fr['group_id'], 'status' => 1) , $limit, $offset)->result_array();
                }

                if (!empty($group_data)) {
                    
                    foreach ($group_data as &$post) {
                        $postID = $post['post_id'];
                        $poll_link = $base_url.'web/?id='.$postID;
                        $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
                        $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
                        $commentCount = $this->common_model->getQuery($comment_post);

                        $post['comment_count'] = $commentCount[0]['count'];

                        //post creator

                        $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                        $post['post_creator'] = $post_sender['fullname'];
                        $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);

                        //group_name 
                        $sharegroup_name = '';
                        if ($post['group_id'] != 0) {
                            foreach (explode(',', $post['group_id']) as $key => $value) {
                                $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                                $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                            }
                        } else {
                            $sharegroup_name = '';
                        }
                        $post['group_name'] = $sharegroup_name;
                        //frnd name
                        if ($post['friend_id'] != 0) {
                            $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['friend_id'], 'activated' => 1), $multi_record = false, $order = '');
                            $sharefriend_name = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                        } else {
                            $sharefriend_name = '';
                        }
                        $post['friend_name'] = $sharefriend_name;

                        //text data
                        $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                        $totalPostTextLikes =0;
                        foreach($text as $tx)
                        {
                            $totalPostTextLikes +=  $tx['likes'];
                        }
                        $total_textprop = 0;
                        $loop_textindex = 0;
                        if (count($text) > 0) {
                            $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                            //check like of user id
                            foreach ($post['text'] as &$text_likes) {
                                $loop_textindex++;
                                $likes_count = (int) $text_likes['likes'];
                                $unlikes_count = (int) $text_likes['unlikes'];
                                $text_id = (int) $text_likes['id'];

                                
                                if ($likes_count > 0) {

                                    $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                                    if(count($text) == $loop_textindex && $loop_textindex > 1){
                                    $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                                    }else{
                                    $prop = round ( $likesproportion);
                                    $total_textprop += $prop;
                                    $text_likes['likes_proportion'] = $prop.'%';
                                    }

                                    $like = $this->common_model->findWhere($table = 'ws_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $text_likes['like_status'] = $like_status;

                                    //likes array
                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_text_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.text_id', $text_id);

                                    $text_likes['likes_detail'] = $this->db->get()->result_array();
                                    if ($text_likes['likes_detail']) {
                                        foreach ($text_likes['likes_detail'] as &$detail_friend) {
                                            $fr_id = $detail_friend['user_id'];
                                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                            if ($like_frnd) {
                                                $detail_friend['is_friend'] = 1;
                                            } else {
                                                $detail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $text_likes['like_status'] = 'not liked';
                                    $text_likes['likes_detail'] = array();
                                    $text_likes['likes_proportion'] = '0%';
                                }

                                //unlikes array
                                if ($unlikes_count > 0) {
                                    $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $text_likes['unlike_status'] = $unlike_status;

                                    //unlikes array

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_text_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.text_id', $text_id);

                                    $text_likes['unlikes_detail'] = $this->db->get()->result_array();

                                    if ($text_likes['unlikes_detail']) {
                                        foreach ($text_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                            $fr_id = $unlikedetail_friend['user_id'];
                                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                            if ($unlike_frnd) {
                                                $unlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $unlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $text_likes['unlikes_detail'] = array();
                                    $text_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['text'] = array();
                        }

                        //images detail
                        $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                        $totalPostImageLikes =0;
                        $totalPostImageUnlikes =0;
                        foreach($img as $im)
                        {
                        $totalPostImageLikes +=  $im['likes'];
                        $totalPostImageUnlikes +=  $im['unlikes'];
                        }
                        $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
                        $total_imgprop = 0;
                        $loop_imgindex = 0;
                        if (count($img) > 0) {
                            $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                            //check like of user id
                            foreach ($post['images'] as &$images_likes) {
                                $loop_imgindex++;
                                $likes_count = (int) $images_likes['likes'];
                                $unlikes_count = (int) $images_likes['unlikes'];
                                $img_id = (int) $images_likes['image_id'];

                                
                                if ($likes_count > 0) {

                                    $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                                    if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                                    $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                                    }else{
                                    $prop = round ( $likesproportion);
                                    $total_imgprop += $prop;
                                    $images_likes['likes_proportion'] = $prop.'%';
                                    }

                                    $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $images_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.image_id', $img_id);

                                    $images_likes['likes_detail'] = $this->db->get()->result_array();
                                    if ($images_likes['likes_detail']) {
                                        foreach ($images_likes['likes_detail'] as &$detail_friend) {
                                            $fr_id = $detail_friend['user_id'];
                                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                            if ($like_frnd) {
                                                $detail_friend['is_friend'] = 1;
                                            } else {
                                                $detail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $images_likes['like_status'] = 'not liked';
                                    $images_likes['likes_detail'] = array();
                                    $images_likes['likes_proportion'] = '0%';
                                }

                                //unlikes array
                                $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                                if(count($img) == $loop_imgindex){
                                    $prop = round ( $unlikesproportion);
                                    $total_imgprop += $prop;
                                    $images_likes['unlikes_proportion'] = $prop.'%';
                                }
                                if ($unlikes_count > 0) {
                                    $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $images_likes['unlike_status'] = $unlike_status;

                                    //unlikes array

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.image_id', $img_id);

                                    $images_likes['unlikes_detail'] = $this->db->get()->result_array();

                                    if ($images_likes['unlikes_detail']) {
                                        foreach ($images_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                            $fr_id = $unlikedetail_friend['user_id'];
                                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                            if ($unlike_frnd) {
                                                $unlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $unlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $images_likes['unlikes_detail'] = array();
                                    $images_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['images'] = array();
                        }
                        //videos detail
                        $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

                        if (count($vid) > 0) {
                            $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                            foreach ($post['video'] as &$video_likes) {
                                $likes_count = (int) $video_likes['likes'];

                                $unlikes_count = (int) $video_likes['unlikes'];
                                $vid_id = (int) $video_likes['video_id'];
                                
                                //likes status
                                if ($likes_count > 0) {
                                    
                                    $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                                    $video_likes['like_status'] = $like_status;

                                    //likes array

                                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                                    $this->db->from('ws_video_likes l');
                                    $this->db->join('ws_users u', 'l.user_id = u.id');
                                    $this->db->where('l.video_id', $vid_id);

                                    $video_likes['likes_detail'] = $this->db->get()->result_array();

                                    if ($video_likes['likes_detail']) {
                                        foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                            $fr_id = $vidlikedetail_friend['user_id'];
                                            $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                            $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                            if ($vidlike_frnd) {
                                                $vidlikedetail_friend['is_friend'] = 1;
                                            } else {
                                                $vidlikedetail_friend['is_friend'] = 0;
                                            }
                                        }
                                    }
                                } else {
                                    $video_likes['likes_detail'] = array();
                                    $video_likes['like_status'] = 'not liked';
                                }

                                //unlikes array
                                if ($unlikes_count > 0) {
                                    //unlikes array
                                    $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                                    $video_likes['unlike_status'] = $unlike_status;

                                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                                    $this->db->from('ws_video_unlikes ul');
                                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                                    $this->db->where('ul.video_id', $vid_id);

                                    $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                                } else {
                                    $video_likes['unlikes_detail'] = array();
                                    $video_likes['unlike_status'] = 'not disliked';
                                }
                            }
                        } else {
                            $post['video'] = array();
                        }

                        //last comment detail
                        $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();

                        if (count($last_comment) > 0) {
                            $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();
                            sort($post['last_comment']);
                            
                            foreach ($post['last_comment'] as &$commentDetail) {
                                $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                                $commentDetail['sender_name'] = $comment_sender['fullname'];
                                $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                                $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);
                            }
                        } else {
                            $post['last_comment'] = array();
                        }

                        //tag detail start
                        $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
                        if (count($tag) > 0) {
                            $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                            //check like of user id
                            foreach ($post['tagged_data'] as &$tags) {
                                $user_id_val = (int) $tags['user_id'];
                                if ($user_id_val > 0) {
                                    $tag_frnd_id = $user_id_val;
                                    //echo 'yguhu'.$img_id;die;
                                    $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                                    $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                                    $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                                    $tags['tag_frnd'] = $tag_frnd_name;
                                } else {
                                    $tags['tag_frnd'] = '';
                                }
                            }
                        } else {
                            $post['tagged_data'] = array();
                        }

                        $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                        if (count($tagwrd) > 0) {
                            $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                        } else {
                            $post['taggedword_data'] = array();
                        }
                        //tag detail end
                    }
                } else {
                    $group_data = array();
                }

                foreach ($group_data as $key => $value) {
                    array_push($GROUPDETAIL, $value);
                }
            }
            $gpVal = array_values($this->unique_multidim_array($GROUPDETAIL, 'post_id'));
            $data = array(
                'status' => 1,
                'base_url' => $base_url,
                'post_images_url' => $post_base_url,
                'data' => $gpVal
            );

            $this->response($data, 200);
        } else {
            $data = array(
                'status' => 1,
                'data' => 'unknown type.'
            );
            $this->response($data, 200);
        }
    }

    /**
     * ***********************************************************************************************************************
     * Function Name : get_task_detail_post                                                                                 *
     * Functionality : get individual post detail(post detail,group name,friend name,images,videos,last comment,tagged data)*                                                                    *    
     * @author       : pratibha sinha                                                                                       *
     * @param        : int    user_id                                                                                       *
     * @param        : int    type                                                                                          *
     * revision 0    : author changes_made                                                                                  *
     * ***********************************************************************************************************************
     * */
    /* provide likes also in output */
    function get_poll_detail_post()
    {
        $base_url = $this->baseurl;
        //$post_base_url = $this->baseurl . 'uploads/post_images/';
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';
        $task_id = $this->input->post('task_id');
        $this->check_empty($task_id, 'Please add task id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $datetime1 = date('Y-m-d H:i:s', time());
        //$datetime1 = $this->input->post('datetime1');
        //$this->check_empty($datetime1, 'Please add datetime1');

        //post detail
        $post = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $task_id, 'status' => 1), $multi_record = false, $order = '');

        if(!empty($post))
        {
            $postID = $post['post_id'];
            $poll_link = $base_url.'web/?id='.$task_id;
            $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
            $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
            $commentCount = $this->common_model->getQuery($comment_post);

            //echo '<pre>htgtyh';print_r($commentCount);
            $post['comment_count'] = $commentCount[0]['count'];

            //post creator
            $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
            $post['post_creator'] = $post_sender['fullname'];
            $post['creator_pic'] = (!empty($post_sender['profile_pic']) ) ? $base_url . $post_sender['profile_pic'] : '';
            $post['creator_unique_name'] = (!empty($post_sender['unique_name']) ) ? $post_sender['unique_name'] : '';
            $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);
            
            //group_name 
            $sharegroup_name = array();
            if ($post['group_id'] != 0) {
                foreach (explode(',', $post['group_id']) as $key => $value) {
                    $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                    $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                }
            } else {
                $sharegroup_name = '';
            }
            $post['group_name'] = $sharegroup_name;
            //frnd name
            $sharefriend_name = array();
            if ($post['friend_id'] != 0) {
                foreach (explode(',', $post['friend_id']) as $key => $value) {
                    $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value, 'activated' => 1), $multi_record = false, $order = '');
                    $sharefriend_name[] = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                }
            } else {
                $sharefriend_name = '';
            }
            $post['friend_name'] = $sharefriend_name;

            //text data

            $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
            $totalPostTextLikes =0;
            foreach($text as $tx)
            {
                $totalPostTextLikes +=  $tx['likes'];
            }
            $total_textprop = 0;
            $loop_textindex = 0;
            if (count($text) > 0) {
                $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();

                //check like of user id
                //echo '<pre>';print_r($post['images']);die;
                foreach ($post['text'] as &$text_likes) {
                    $loop_textindex++;
                    $likes_count = (int) $text_likes['likes'];

                    $unlikes_count = (int) $text_likes['unlikes'];
                    $text_id = (int) $text_likes['id'];

                    
                    
                    //likes status
                    if ($likes_count > 0) {

                        $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                        if(count($text) == $loop_textindex && $loop_textindex > 1){
                        $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                        }else{
                        $prop = round ( $likesproportion);
                        $total_textprop += $prop;
                        $text_likes['likes_proportion'] = $prop.'%';
                        }
                        
                        $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                        $text_likes['like_status'] = $like_status;
                        

                        //likes array

                        $this->db->select('l.user_id,u.fullname,u.profile_pic');
                        $this->db->from('ws_text_likes l');
                        $this->db->join('ws_users u', 'l.user_id = u.id');
                        $this->db->where('l.text_id', $text_id);

                        $text_likes['likes_detail'] = $this->db->get()->result_array();

                    } else {
                        $text_likes['likes_detail'] = array();
                        $text_likes['like_status'] = 'not liked';
                        $text_likes['likes_proportion'] = '0%';
                    }

                    //unlikes array
                    if ($unlikes_count > 0) {
                        $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                        $text_likes['unlike_status'] = $unlike_status;

                        //unlikes array

                        $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                        $this->db->from('ws_text_unlikes ul');
                        $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                        $this->db->where('ul.text_id', $text_id);

                        $text_likes['unlikes_detail'] = $this->db->get()->result_array();
                    } else {
                        $text_likes['unlikes_detail'] = array();
                        $text_likes['unlike_status'] = 'not disliked';
                    }
                }
            } else {
                $post['text'] = array();
            }

            //images data

            $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
            $totalPostImageLikes =0;
            $totalPostImageUnlikes =0;
            foreach($img as $im)
            {
                $totalPostImageLikes +=  $im['likes'];
                $totalPostImageUnlikes +=  $im['unlikes'];
            }
            $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
            $total_imgprop = 0;
            $loop_imgindex = 0;
            if (count($img) > 0) {
                $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                
                //check like of user id
                foreach ($post['images'] as &$images_likes) {
                    $loop_imgindex++;
                    $likes_count = (int) $images_likes['likes'];

                    $unlikes_count = (int) $images_likes['unlikes'];
                    $img_id = (int) $images_likes['image_id'];

                    
                    
                    //likes status
                    if ($likes_count > 0) {

                        $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                        if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                        $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                        }else{
                        $prop = round ( $likesproportion);
                        $total_imgprop += $prop;
                        $images_likes['likes_proportion'] = $prop.'%';
                        }
                        
                        $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                        $images_likes['like_status'] = $like_status;


                        //likes array

                        $this->db->select('l.user_id,u.fullname,u.profile_pic');
                        $this->db->from('ws_likes l');
                        $this->db->join('ws_users u', 'l.user_id = u.id');
                        $this->db->where('l.image_id', $img_id);

                        $images_likes['likes_detail'] = $this->db->get()->result_array();

                    } else {
                        $images_likes['likes_detail'] = array();
                        $images_likes['like_status'] = 'not liked';
                        $images_likes['likes_proportion'] = '0%';
                    }

                    //unlikes array
                    $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                    if(count($img) == 1){
                        $prop = round ( $unlikesproportion);
                        $total_imgprop += $prop;
                        $images_likes['unlikes_proportion'] = $prop.'%';
                    }
                    if ($unlikes_count > 0) {
                        $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                        $images_likes['unlike_status'] = $unlike_status;

                        //unlikes array

                        $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                        $this->db->from('ws_unlikes ul');
                        $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                        $this->db->where('ul.image_id', $img_id);

                        $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                    } else {
                        $images_likes['unlikes_detail'] = array();
                        $images_likes['unlike_status'] = 'not disliked';
                    }
                }
            } else {
                $post['images'] = array();
            }
            //videos data
            $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

            if (count($vid) > 0) {
                $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                foreach ($post['video'] as &$video_likes) {
                    
                    $likes_count = (int) $video_likes['likes'];
                    $unlikes_count = (int) $video_likes['unlikes'];
                    $vid_id = (int) $video_likes['video_id'];
                   
                    //likes status
                    if ($likes_count > 0) {
                        
                        $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                        $video_likes['like_status'] = $like_status;

                        //likes array
                        $this->db->select('l.user_id,u.fullname,u.profile_pic');
                        $this->db->from('ws_video_likes l');
                        $this->db->join('ws_users u', 'l.user_id = u.id');
                        $this->db->where('l.video_id', $vid_id);

                        $video_likes['likes_detail'] = $this->db->get()->result_array();

                        if ($video_likes['likes_detail']) {
                            foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                $fr_id = $vidlikedetail_friend['user_id'];
                                $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                if ($vidlike_frnd) {
                                    $vidlikedetail_friend['is_friend'] = 1;
                                } else {
                                    $vidlikedetail_friend['is_friend'] = 0;
                                }
                            }
                        }
                    } else {
                        $video_likes['likes_detail'] = array();
                        $video_likes['like_status'] = 'not liked';
                    }

                    //unlikes array
                    if ($unlikes_count > 0) {
                        $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                        $video_likes['unlike_status'] = $unlike_status;

                        $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                        $this->db->from('ws_video_unlikes ul');
                        $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                        $this->db->where('ul.video_id', $vid_id);

                        $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                    } else {
                        $video_likes['unlikes_detail'] = array();
                        $video_likes['unlike_status'] = 'not disliked';
                    }
                }
            } else {
                $post['video'] = array();
            }

            //last comment data
            $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();


            if (count($last_comment) > 0) {
                $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();
                sort($post['last_comment']);
                
                foreach ($post['last_comment'] as &$commentDetail) {
                    $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                    $commentDetail['sender_name'] = $comment_sender['fullname'];
                    $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                    $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);

                    $commentDetail['commentuser_name'] = array();
                    if (!empty($commentDetail['mention_users'])) {
                        foreach (explode(',', $commentDetail['mention_users']) as $key => $value) {
                            $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                            $commentDetail['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                        }
                    }
                }
            } else {
                $post['last_comment'] = array();
            }

            //tag detail start
            $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
            if (count($tag) > 0) {
                $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                //check like of user id
                foreach ($post['tagged_data'] as &$tags) {
                    $user_id_val = (int) $tags['user_id'];
                    if ($user_id_val > 0) {
                        $tag_frnd_id = $user_id_val;
                        $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                        $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                        $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                        $tags['tag_frnd'] = $tag_frnd_name;
                    } else {
                        $tags['tag_frnd'] = '';
                    }
                }
            } else {
                $post['tagged_data'] = array();
            }

            $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
            if (count($tagwrd) > 0) {
                $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
            } else {
                $post['taggedword_data'] = array();
            }
            //tag detail end
       
         $data = array(
                'status' => 1,
                'base_url' => $base_url,
                'post_images_url' => $post_base_url,
               // 'notification_count' => $notification_count,
                'data' => $post
            );
        }else{
            $data = array(
                'status' => 0,
                'base_url' => $base_url,
                'post_images_url' => $post_base_url,
               // 'notification_count' => $notification_count,
                'data' => array()
            );
        }
        
        $this->response($data, 200);
    }



    function get_task_detail_post() {
        $base_url = $this->baseurl;
        //$post_base_url = $this->baseurl . 'uploads/post_images/';
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';
        $task_id = $this->input->post('task_id');
        $this->check_empty($task_id, 'Please add task id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $datetime1 = date('Y-m-d H:i:s', time());
        //$datetime1 = $this->input->post('datetime1');
        //$this->check_empty($datetime1, 'Please add datetime1');

        //post detail
        $post_detail = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $task_id, 'status' => 1), $multi_record = false, $order = '');
        $post_question = (!empty($post_detail['question']) ) ? $post_detail['question'] : '';
        $post_title = (!empty($post_detail['title']) ) ? $post_detail['title'] : '';
        $post_type = (!empty($post_detail['type']) ) ? $post_detail['type'] : '';
        $post_latitude = (!empty($post_detail['latitude']) ) ? $post_detail['latitude'] : '';
        $post_longitude = (!empty($post_detail['longitude']) ) ? $post_detail['longitude'] : '';
        $post_location = (!empty($post_detail['location']) ) ? $post_detail['location'] : '';
        $post_poll_type = (!empty($post_detail['poll_type']) ) ? $post_detail['poll_type'] : '';
        $post_user_id = (!empty($post_detail['user_id']) ) ? $post_detail['user_id'] : '';
        $post_share_with = (!empty($post_detail['share_with']) ) ? $post_detail['share_with'] : '';
        $post_status = (!empty($post_detail['status']) ) ? $post_detail['status'] : '';
        $post_repost_status = (!empty($post_detail['repost_status']) ) ? $post_detail['repost_status'] : '0';
        $post_added_at = (!empty($post_detail['added_at']) ) ? $post_detail['added_at'] : '';
        $delayed_post = $post_detail['delayed_post'];
        $delayed_text = (!empty($post_detail['delayed_text']) ) ? $post_detail['delayed_text'] : '';
        $delayed_reveal = $post_detail['delayed_reveal'];
        $new_time = $this->time_elapsed_string($datetime1, $post_detail['added_at']);
        $pollLink = $base_url.'web/?id='.$task_id;
        $poll_link = '<iframe src="'.$pollLink.'" height="200" width="300"></iframe>';

        $comment_post = "select count(*) as count from ws_comments where post_id = '$task_id'";
        $commentCount = $this->common_model->getQuery($comment_post);

        $comment_count = $commentCount[0]['count'];

        //post creator

        $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post_detail['user_id'], 'activated' => 1), $multi_record = false, $order = '');
        $post_creator = (!empty($post_sender['fullname']) ) ? $post_sender['fullname'] : '';
        $creator_pic = (!empty($post_sender['profile_pic']) ) ? $base_url . $post_sender['profile_pic'] : '';
        $creator_unique_name = (!empty($post_sender['unique_name']) ) ? $post_sender['unique_name'] : '';

        //group detail

        $groups = $this->db->get_where('ws_posts', array('post_id' => $task_id, 'status' => 1))->result_array();
        
        if ($groups) {
            foreach ($groups as $post) {
                foreach (explode(',', $post['group_id']) as $key => $value) {
                    $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                    $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                }
            }
        }

        $friends = $this->db->get_where('ws_posts', array('post_id' => $task_id, 'status' => 1))->result_array();
        if ($friends) {
            foreach ($friends as $post) {
                foreach (explode(',', $post['friend_id']) as $key => $value) {
                    $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value, 'activated' => 1), $multi_record = false, $order = '');
                    $sharefriend_name[] = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                }
            }    
        }

        //text data
        $text = $this->db->get_where('ws_text', array('post_id' => $task_id))->result_array();
        $totalPostTextLikes =0;
        foreach($text as $tx)
        {
            $totalPostTextLikes +=  $tx['likes'];
        }
        $total_textprop = 0;
        $loop_textindex = 0;
        if ($text) {
            foreach ($text as &$text_likes) {
                $loop_textindex++;
                $likes_count = (int) $text_likes['likes'];
                $unlikes_count = (int) $text_likes['unlikes'];
                $text_id = (int) $text_likes['id'];

                
                if ($likes_count > 0) {
                    $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                    if(count($text) == $loop_textindex && $loop_textindex > 1){
                    $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                    }else{
                    $prop = round ( $likesproportion);
                    $total_textprop += $prop;
                    $text_likes['likes_proportion'] = $prop.'%';
                    }

                    $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                    $text_likes['like_status'] = $like_status;

                    //likes array
                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                    $this->db->from('ws_text_likes l');
                    $this->db->join('ws_users u', 'l.user_id = u.id');
                    $this->db->where('l.text_id', $text_id);

                    $text_likes['likes_detail'] = $this->db->get()->result_array();
                } else {
                    $text_likes['like_status'] = 'not liked';
                    $text_likes['likes_detail'] = array();
                    $text_likes['likes_proportion'] = $prop.'%';
                }

                //unlikes array
                if ($unlikes_count > 0) {
                    $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                    $text_likes['unlike_status'] = $unlike_status;

                    //unlikes array

                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                    $this->db->from('ws_text_unlikes ul');
                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                    $this->db->where('ul.text_id', $text_id);

                    $text_likes['unlikes_detail'] = $this->db->get()->result_array();
                } else {
                    $text_likes['unlike_status'] = 'not disliked';
                    $text_likes['unlikes_detail'] = array();
                }
            }
        }

        //post images detail
        $images = $this->db->get_where('ws_images', array('post_id' => $task_id))->result_array();
        $totalPostImageLikes =0;
        $totalPostImageUnlikes =0;
        foreach($images as $im)
        {
            $totalPostImageLikes +=  $im['likes'];
            $totalPostImageUnlikes +=  $im['unlikes'];
        }
        $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
        $total_imgprop = 0;
        $loop_imgindex = 0;
        if ($images) {
            foreach ($images as &$images_likes) {
                $loop_imgindex++;
                $likes_count = (int) $images_likes['likes'];
                $unlikes_count = (int) $images_likes['unlikes'];
                $img_id = (int) $images_likes['image_id'];

                
                if ($likes_count > 0) {
                    $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                    if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                    $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                    }else{
                    $prop = round ( $likesproportion);
                    $total_imgprop += $prop;
                    $images_likes['likes_proportion'] = $prop.'%';
                    }

                    //echo 'yguhu'.$img_id;die;
                    $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                    $images_likes['like_status'] = $like_status;

                    //likes array
                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                    $this->db->from('ws_likes l');
                    $this->db->join('ws_users u', 'l.user_id = u.id');
                    $this->db->where('l.image_id', $img_id);

                    $images_likes['likes_detail'] = $this->db->get()->result_array();
                } else {
                    $images_likes['like_status'] = 'not liked';
                    $images_likes['likes_detail'] = array();
                    $images_likes['likes_proportion'] = '0%';
                }

                //unlikes array
                $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                if(count($img) == 1){
                    $prop = round ( $unlikesproportion);
                    $total_imgprop += $prop;
                    $images_likes['unlikes_proportion'] = $prop.'%';
                }
                if ($unlikes_count > 0) {
                    $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                    $images_likes['unlike_status'] = $unlike_status;

                    //unlikes array

                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                    $this->db->from('ws_unlikes ul');
                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                    $this->db->where('ul.image_id', $img_id);

                    $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                } else {
                    $images_likes['unlike_status'] = 'not disliked';
                    $images_likes['unlikes_detail'] = array();
                }
            }
        }
        
        //comments detail
        $comments = $this->db->order_by('comment_id', 'asc')->get_where('ws_comments', array('post_id' => $task_id, 'status' => '1'))->result_array();
        if ($comments) {
            foreach ($comments as &$comments_result) {
                $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $comments_result['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                $comments_result['sender_name'] = $comment_sender['fullname'];
                $comments_result['profile_pic'] = $comment_sender['profile_pic'];

                $comments_result['commentuser_name'] = array();
                if (!empty($comments_result['mention_users'])) {
                    foreach (explode(',', $comments_result['mention_users']) as $key => $value) {
                        $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                        $comments_result['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                    }
                }
                if ($comments_result['added_at'] != '') {
                    $resultcomment = $this->time_elapsed_string($datetime1, $comments_result['added_at']);
                } else {
                    $resultcomment = '';
                }
                $comments_result['new_time'] = $resultcomment;
            }
        }

        //tag detail start
        $tagsData = $this->db->get_where('ws_tags', array('post_id' => $task_id))->result_array();

        if ($tagsData) {
            foreach ($tagsData as &$tags) {
                $user_id_val = (int) $tags['user_id'];
                if ($user_id_val > 0) {
                    $tag_frnd_id = $user_id_val;
                    $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                    //echo 'yguhu'.$img_id;die;
                    $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                    $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                    $tags['tag_frnd'] = $tag_frnd_name;
                    $tags['unique_name'] = (!empty($tag_frnd['unique_name']) ) ?$tag_frnd['unique_name'] : '';
                } else {
                    $tags['tag_frnd'] = '';
                }
            }
        }
        $tagwrd = $this->db->get_where('ws_words', array('post_id' => $task_id))->result_array();
        //tag detail end

        //post videos detail
        $videos = $this->db->get_where('ws_videos', array('post_id' => $task_id))->result_array();

        if ($videos) {
            foreach ($videos as &$video_likes) {
                
                $likes_count = (int) $video_likes['likes'];
                $unlikes_count = (int) $video_likes['unlikes'];
                $vid_id = (int) $video_likes['video_id'];
                if ($likes_count > 0) {
                   
                    $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                    $video_likes['like_status'] = $like_status;

                    //likes array
                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                    $this->db->from('ws_video_likes l');
                    $this->db->join('ws_users u', 'l.user_id = u.id');
                    $this->db->where('l.video_id', $vid_id);

                    $video_likes['likes_detail'] = $this->db->get()->result_array();
                } else {
                    $video_likes['like_status'] = 'not liked';
                    $video_likes['likes_detail'] = array();
                }

                //unlikes array
                if ($unlikes_count > 0) {
                    //unlikes array
                    $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                    $video_likes['unlike_status'] = $unlike_status;

                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                    $this->db->from('ws_video_unlikes ul');
                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                    $this->db->where('ul.video_id', $vid_id);

                    $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                } else {
                    $video_likes['unlikes_detail'] = array();
                    $video_likes['unlike_status'] = 'not disliked';
                }
            }
        }

        $result = array(
            'post_id' => $task_id,
            'user_id' => $post_user_id,
            'question' => $post_question,
            'title' => $post_title,
            'type' => $post_type,
            'latitude' => $post_latitude,
            'longitude' => $post_longitude,
            'location' => $post_location,
            'poll_type' => $post_poll_type,
            'share_with' => $post_share_with,
            'delayed_post' => $delayed_post,
            'delayed_text' => $delayed_text,
            'delayed_reveal' => $delayed_reveal,
            'post_creator' => $post_creator,
            'creator_pic' => $creator_pic,
            'creator_unique_name' => $creator_unique_name,
            'status' => $post_status,
            'repost_status' => $post_repost_status,
            'added_at' => $post_added_at,
            'new_time' => $new_time,
            'group_id' => (!empty($post['group_id']) ? $post['group_id'] : ''),
            'group_name' => (!empty($sharegroup_name) ? $sharegroup_name : ''),
            'friend_id' =>(!empty($post['friend_id']) ? $post['friend_id'] : ''),
            'friend_name' => (!empty($sharefriend_name) ? $sharefriend_name : ''),
            'text' => $text,
            'images' => $images,
            'comments' => $comments,
            'tagged_data' => $tagsData,
            'tagged_word' => $tagwrd,
            'videos' => $videos,
            'comment_count' => $comment_count,
            'poll_link' => $poll_link
        );
        $data = array(
            'status' => 1,
            'base_url' => $base_url,
            'post_images_url' => $post_base_url,
            'data' => $result
        );
        $this->response($data, 200);
    }

    /*public function follow_friend_common($user_id)
    {
        $friend_id = 360;
        $follow_chk = $this->common_model->findWhere($table = 'ws_follow', array('user_id' => $user_id, 'friend_id' => $friend_id), $multi_record = false, $order = '');
        if (empty($follow_chk)) {
            $post_data = array('user_id' => $user_id, 'friend_id' => $friend_id);
            $last_id = $this->common_model->add('ws_follow', $post_data);

            return true;
        } else {
            return false;
        }
    }*/

    public function follow_friend_common($user_id)
    {
        $friends_array = array(360 ,725 ,738 , 953);
        
        foreach($friends_array as $fr_id)
        {
           $friend_id = $fr_id;
            $follow_chk = $this->common_model->findWhere($table = 'ws_follow', array('user_id' => $user_id, 'friend_id' => $friend_id), $multi_record = false, $order = '');
            if (empty($follow_chk) && $user_id != $friend_id) {
                $post_data = array('user_id' => $user_id, 'friend_id' => $friend_id);
                $last_id = $this->common_model->add('ws_follow', $post_data);
            }
        }
    }

    public function follow_default($user_id , $friend_id)
    {
        //$users = $this->db->get_where('ws_users' , array('id !=' => 1))->result_array();
        //echo $this->db->last_query();
        //foreach($users as $usr)
        // {
            //$friend_id = 953;
            //$user_id = $usr['id'];
            $follow_chk = $this->common_model->findWhere($table = 'ws_follow', array('user_id' => $user_id, 'friend_id' => $friend_id), $multi_record = false, $order = '');
            if (empty($follow_chk) && $user_id != $friend_id) {
                $post_data = array('user_id' => $user_id, 'friend_id' => $friend_id);
                $last_id = $this->common_model->add('ws_follow', $post_data);
            }
       // }
    }

    public function follow_all_users_to_post()
    {
        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');
        $users = $this->db->get_where('ws_users' , array('id !=' => 1))->result_array();
        
        foreach($users as $usr)
            {
                $user_id = $usr['id'];
                $follow_chk = $this->common_model->findWhere($table = 'ws_follow', array('user_id' => $user_id, 'friend_id' => $friend_id), $multi_record = false, $order = '');
                if (empty($follow_chk) && $user_id != $friend_id) {
                    $post_data = array('user_id' => $user_id, 'friend_id' => $friend_id);
                    $last_id = $this->common_model->add('ws_follow', $post_data);
                }
            }
    }

    public function follow_friend_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');

        if($user_id == $friend_id)
        {
            $data = array(
                'status' => 0,
                'message' => 'error'
            );
        }else{
            $follow_chk = $this->common_model->findWhere($table = 'ws_follow', array('user_id' => $user_id, 'friend_id' => $friend_id), $multi_record = false, $order = '');
            if (empty($follow_chk)) {
                $post_data = array('user_id' => $user_id, 'friend_id' => $friend_id);
                $last_id = $this->common_model->add('ws_follow', $post_data);

                //send notification start for post
                $block_chk = $this->check_block($friend_id, $user_id);
                if ($block_chk == false) {
                    //echo '11';die;
                    $this->save_notification($friend_id, 'follow', $user_id, '');
                    $this->send_notification($friend_id, $user_id, 'follow', '', '', '', '', $last_id);
                }
                //send notification end for post
                $data = array(
                    'status' => 1,
                    'message' => 'success'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'error'
                );
            }
        }
        $this->response($data, 200);
    }

    public function unfollow_friend_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');

        $follow_chk = $this->common_model->findWhere($table = 'ws_follow', array('user_id' => $user_id, 'friend_id' => $friend_id), $multi_record = false, $order = '');
        if (!empty($follow_chk)) {
            //remove follow
            $follow_data = array('user_id' => $user_id, 'friend_id' => $friend_id);
            $this->common_model->delete($table = 'ws_follow', $follow_data);
            $data = array(
                'status' => 1,
                'message' => 'success'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'error'
            );
        }
        $this->response($data, 200);
    }

    /**
     * ************************************************************************************************
     * Function Name : add_like_image_post                                                           *
     * Functionality : like image                                                                    *    
     * @author       : pratibha sinha                                                                *
     * @param        : int    image_id                                                               *
     * @param        : int    user_id                                                                *
     * revision 0    : author changes_made                                                           *
     * ************************************************************************************************
     * */
    
    
    public function add_like_text_post() {
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $text_id = $this->input->post('text_id');
        $this->check_empty($text_id, 'Please add text_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        //$deep_link_follow = $this->input->post('deep_link_follow');

        //check post w.r.t image_id

        $postResult = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
        if($postResult['delayed_reveal'] == 1){
            $data = array(
                            'status' => 2,
                            'message' => 'Post Reveal'
                        );
        }elseif($postResult['delayed_reveal'] == 2){
            $data = array(
                            'status' => 3,
                            'message' => 'Post Close'
                        );
        }else{
            $delayed_post = $postResult['delayed_post'];
            $delayed_text = $postResult['delayed_text'];
            $delayed_reveal = $postResult['delayed_reveal'];

            $post_chk = $this->common_model->findWhere($table = 'ws_text', array('id' => $text_id, 'post_id' => $post_id), $multi_record = false, $order = '');
            if (!empty($post_chk)) {
                $like = $this->common_model->findWhere($table = 'ws_text_likes', array('post_id' => $post_id, 'user_id' => $user_id, 'text_id' => $text_id), $multi_record = false, $order = '');
                //check already liked w.r.t image id
                if (empty($like)) {
                    //update count of previously likes
                    $newlike = $this->common_model->findWhere($table = 'ws_text_likes', array('post_id' => $post_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $previousliked_data = array('id' => $newlike['text_id']);

                    $newlikeval = $this->common_model->findWhere($table = 'ws_text', array('id' => $newlike['text_id']), $multi_record = false, $order = '');
                    $val = $newlikeval['likes'] - 1;
                    $previouslyimagelike = array('likes' => $val);
                    $this->common_model->updateWhere('ws_text', $previousliked_data, $previouslyimagelike);

                    //delete previously liked any image w.r.t post start
                    $deleteliked_data = array('post_id' => $post_id, 'user_id' => $user_id);
                    $this->common_model->delete($table = 'ws_text_likes', $deleteliked_data);


                    //delete previously liked any image w.r.t post end
                    $post_data = array('text_id' => $text_id, 'user_id' => $user_id, 'post_id' => $post_id);
                    $last_id = $this->common_model->add('ws_text_likes', $post_data);

                    if ($last_id) {
                        //count no. of likes of image
                        $like_query = "SELECT * FROM ws_text_likes WHERE text_id = '$text_id'";
                        $like_result = $this->common_model->getQuery($like_query);
                        if (!empty($like_result)) {
                            $like_count = count($like_result);
                        } else {
                            $like_count = 0;
                        }

                        $textlike_data = array('likes' => $like_count);
                        $this->common_model->updateWhere('ws_text', $where_data = array('id' => $text_id), $textlike_data);

                        //send notification start for post
                        $post_text = $this->common_model->findWhere($table = 'ws_text', array('id' => $text_id), $multi_record = false, $order = '');
                        $post_id_val = $post_text['post_id'];
                        $post_owner = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id_val, 'status' => 1), $multi_record = false, $order = '');
                        $receiver = $post_owner['user_id'];

                        if ($receiver != $user_id) {
                            $notify_chk = $this->check_notification_set($receiver, 'vote');
                            //save notification
                            
                            if ($notify_chk == true) {
                                $this->save_notification($receiver, 'like', $user_id, $post_id_val);
                                $this->send_notification($receiver, $user_id, 'like', '', '', $post_id_val, '', '');
                                
                            }
                        }

                        //send notification end for post
                        $chk_unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');

                        if ($chk_unlike) {
                            $unlikechkremove_data = array('text_id' => $text_id, 'user_id' => $user_id);
                            $this->common_model->delete($table = 'ws_text_unlikes', $unlikechkremove_data);

                            $unlikechk_query = "SELECT count(*) as count FROM ws_text_unlikes WHERE text_id = '$text_id'";
                            $unlikechk_result = $this->common_model->getQuery($unlikechk_query);

                            $textunlikechk_data = array('unlikes' => $unlikechk_result[0]['count']);
                            $this->common_model->updateWhere('ws_text', $where_data = array('id' => $text_id), $textunlikechk_data);
                        }

                       // if($deep_link_follow == 1){
                            $this->follow_default($user_id , $postResult['user_id']);
                       // }
                        
                    $data = array(
                            'status' => 1,
                            'delayed_post' => $delayed_post,
                            'delayed_text' => $delayed_text,
                            'delayed_reveal' => $delayed_reveal,
                            'message' => 'like added'
                        );
                    } else {
                        $data = array(
                            'status' => 1,
                            'message' => 'Failed'
                        );
                    }
                } else {
                    //remove like
                    $likeremove_data = array('text_id' => $text_id, 'user_id' => $user_id, 'post_id' => $post_id);
                    $this->common_model->delete($table = 'ws_text_likes', $likeremove_data);

                    //count change
                    $likeremove_query = "SELECT * FROM ws_text_likes WHERE text_id = '$text_id'";
                    $likeremove_result = $this->common_model->getQuery($likeremove_query);
                    if (!empty($likeremove_result)) {
                        $likeremove_count = count($likeremove_result);
                    } else {
                        $likeremove_count = 0;
                    }
                    //die;
                    $textlikeremove_data = array('likes' => $likeremove_count);
                    $this->common_model->updateWhere('ws_text', $where_data = array('id' => $text_id), $textlikeremove_data);

                    $data = array(
                        'status' => 0,
                        'message' => 'Already liked post text'
                    );
                }
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Either post_id or text_id is wrong'
                );
            }

            //text data start
            $text = $this->db->get_where('ws_text', array('post_id' => $post_id))->result_array();
            $totalPostTextLikes =0;
            foreach($text as $tx)
            {
                $totalPostTextLikes +=  $tx['likes'];
            }
            $total_textprop = 0;
            $loop_textindex = 0;
            if (count($text) > 0) {
            $data['detail'] = $this->db->get_where('ws_text', array('post_id' => $post_id))->result_array();

            //check like of user id
            foreach ($data['detail'] as &$text_likes) {

                $loop_textindex++;
                $likes_count = (int) $text_likes['likes'];

                $unlikes_count = (int) $text_likes['unlikes'];
                $text_id = (int) $text_likes['id'];

                
                //likes status
                if ($likes_count > 0) {

                    $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                    $text_likes['likes_proportion'] =(  (round ( $likesproportion , 0) ) ).'%';
                    if(count($text) == $loop_textindex && $loop_textindex > 1){
                    $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                    }else{
                    $prop = round ( $likesproportion);
                    $total_textprop += $prop;
                    $text_likes['likes_proportion'] = $prop.'%';
                    }
                    
                    $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                    $text_likes['like_status'] = $like_status;

                    //likes array
                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                    $this->db->from('ws_text_likes l');
                    $this->db->join('ws_users u', 'l.user_id = u.id');
                    $this->db->where('l.text_id', $text_id);

                    $text_likes['likes_detail'] = $this->db->get()->result_array();
                    if ($text_likes['likes_detail']) {
                        foreach ($text_likes['likes_detail'] as &$detail_friend) {
                            $fr_id = $detail_friend['user_id'];
                            $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                            $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                            if ($like_frnd) {
                                $detail_friend['is_friend'] = 1;
                            } else {
                                $detail_friend['is_friend'] = 0;
                            }
                        }
                    }
                } else {
                    $text_likes['likes_detail'] = array();
                    $text_likes['like_status'] = 'not liked';
                    $text_likes['likes_proportion'] = '0%';
                }

                //unlikes array
                if ($unlikes_count > 0) {

                    $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                    $text_likes['unlike_status'] = $unlike_status;
                    //unlikes array

                    $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                    $this->db->from('ws_text_unlikes ul');
                    $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                    $this->db->where('ul.text_id', $text_id);

                    $text_likes['unlikes_detail'] = $this->db->get()->result_array();
                    if ($text_likes['unlikes_detail']) {
                        foreach ($text_likes['unlikes_detail'] as &$unlikedetail_friend) {
                            $fr_id = $unlikedetail_friend['user_id'];
                            $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                            $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                            if ($unlike_frnd) {
                                $unlikedetail_friend['is_friend'] = 1;
                            } else {
                                $unlikedetail_friend['is_friend'] = 0;
                            }
                        }
                    }
                } else {
                    $text_likes['unlikes_detail'] = array();
                    $text_likes['unlike_status'] = 'not disliked';
                }
            }
        } else {
            $data['detail'] = array();
        }
        }
        
    $this->response($data, 200);
    }

    /* Add like on image */

    public function add_like_image_post() {
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $image_id = $this->input->post('image_id');
        $this->check_empty($image_id, 'Please add image_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        //$deep_link_follow = $this->input->post('deep_link_follow');

        //check post w.r.t image_id
        $postResult = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
        if($postResult['delayed_reveal'] == 1){
            $data = array(
                            'status' => 2,
                            'message' => 'Post Reveal'
                        );
        }elseif($postResult['delayed_reveal'] == 2){
            $data = array(
                            'status' => 3,
                            'message' => 'Post Close'
                        );
        }else{
            $delayed_post = $postResult['delayed_post'];
            $delayed_text = $postResult['delayed_text'];
            $delayed_reveal = $postResult['delayed_reveal'];

            $post_chk = $this->common_model->findWhere($table = 'ws_images', array('image_id' => $image_id, 'post_id' => $post_id), $multi_record = false, $order = '');
            if (!empty($post_chk)) {
                $like = $this->common_model->findWhere($table = 'ws_likes', array('post_id' => $post_id, 'user_id' => $user_id, 'image_id' => $image_id), $multi_record = false, $order = '');
                //check already liked w.r.t image id
                if (empty($like)) {

                    //update count of previously likes
                    $newlike = $this->common_model->findWhere($table = 'ws_likes', array('post_id' => $post_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $previousliked_data = array('image_id' => $newlike['image_id']);

                    $newlikeval = $this->common_model->findWhere($table = 'ws_images', array('image_id' => $newlike['image_id']), $multi_record = false, $order = '');
                    $val = $newlikeval['likes'] - 1;
                    $previouslyimagelike = array('likes' => $val);
                    $this->common_model->updateWhere('ws_images', $previousliked_data, $previouslyimagelike);

                    //delete previously liked any image w.r.t post start
                    $deleteliked_data = array('post_id' => $post_id, 'user_id' => $user_id);
                    $this->common_model->delete($table = 'ws_likes', $deleteliked_data);

                    //delete previously liked any image w.r.t post end

                    $post_data = array('image_id' => $image_id, 'user_id' => $user_id, 'post_id' => $post_id);
                    $last_id = $this->common_model->add('ws_likes', $post_data);

                    if ($last_id) {
                        //count no. of likes of image
                        $like_query = "SELECT * FROM ws_likes WHERE image_id = '$image_id'";
                        $like_result = $this->common_model->getQuery($like_query);
                        if (!empty($like_result)) {
                            $like_count = count($like_result);
                        } else {
                            $like_count = 0;
                        }
                        //update no. of likes in images table
                        $imagelike_data = array('likes' => $like_count);
                        $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imagelike_data);

                        //send notification start for post

                        $post_img = $this->common_model->findWhere($table = 'ws_images', array('image_id' => $image_id), $multi_record = false, $order = '');
                        $post_id_val = $post_img['post_id'];
                        $post_owner = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id_val, 'status' => 1), $multi_record = false, $order = '');
                        $receiver = $post_owner['user_id'];

                        if ($receiver != $user_id) {
                            $notify_chk = $this->check_notification_set($receiver, 'vote');
                            //save notification
                            
                            if ($notify_chk == true) {
                                $this->save_notification($receiver, 'like', $user_id, $post_id_val);
                                $this->send_notification($receiver, $user_id, 'like', '', '', $post_id_val, '', '');
                                
                                //$this->save_multiple_notification($receiver, 'like', $user_id, $post_id_val);
                            }
                        }


                        //send notification end for post
                        //chk unlike(less unlike count by 1 if previously unliked)


                        $chk_unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $image_id, 'user_id' => $user_id), $multi_record = false, $order = '');

                        if ($chk_unlike) {
                            $unlikechkremove_data = array('image_id' => $image_id, 'user_id' => $user_id);
                            $this->common_model->delete($table = 'ws_unlikes', $unlikechkremove_data);

                            $unlikechk_query = "SELECT count(*) as count FROM ws_unlikes WHERE image_id = '$image_id'";
                            $unlikechk_result = $this->common_model->getQuery($unlikechk_query);

                            $imageunlikechk_data = array('unlikes' => $unlikechk_result[0]['count']);
                            $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imageunlikechk_data);
                        }

                        //if($deep_link_follow == 1){
                            $this->follow_default($user_id , $postResult['user_id']);
                        //}
                        $data = array(
                            'status' => 1,
                            'delayed_post' => $delayed_post,
                            'delayed_text' => $delayed_text,
                            'delayed_reveal' => $delayed_reveal,
                            'message' => 'like added'
                        );
                    } else {
                        $data = array(
                            'status' => 1,
                            'message' => 'Failed'
                        );
                    }
                } else {
                    //remove like
                    $likeremove_data = array('image_id' => $image_id, 'user_id' => $user_id, 'post_id' => $post_id);
                    $this->common_model->delete($table = 'ws_likes', $likeremove_data);

                    //count change
                    $likeremove_query = "SELECT * FROM ws_likes WHERE image_id = '$image_id'";
                    $likeremove_result = $this->common_model->getQuery($likeremove_query);
                    if (!empty($likeremove_result)) {
                        $likeremove_count = count($likeremove_result);
                    } else {
                        $likeremove_count = 0;
                    }
                    //die;
                    $imagelikeremove_data = array('likes' => $likeremove_count);
                    $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imagelikeremove_data);
                    $data = array(
                        'status' => 0,
                        'message' => 'Already liked post image'
                    );
                }
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Either post_id or image_id is wrong'
                );
            }

            //images data

            $img = $this->db->get_where('ws_images', array('post_id' => $post_id))->result_array();
            $totalPostImageLikes =0;
            $totalPostImageUnlikes =0;
            foreach($img as $im)
            {
            $totalPostImageLikes +=  $im['likes'];
            $totalPostImageUnlikes +=  $im['unlikes'];
            }
            $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
            $total_imgprop = 0;
            $loop_imgindex = 0;
            if (count($img) > 0) {
                $data['detail'] = $this->db->get_where('ws_images', array('post_id' => $post_id))->result_array();

                //check like of user id
                foreach ($data['detail'] as &$images_likes) {
                    $loop_imgindex++;

                    $likes_count = (int) $images_likes['likes'];

                    $unlikes_count = (int) $images_likes['unlikes'];
                    $img_id = (int) $images_likes['image_id'];

                    
                    //likes status
                    if ($likes_count > 0) {
                        $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                        if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                        $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                        }else{
                        $prop = round ( $likesproportion);
                        $total_imgprop += $prop;
                        $images_likes['likes_proportion'] = $prop.'%';
                        }

                        $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                        $images_likes['like_status'] = $like_status;

                        //likes array

                        $this->db->select('l.user_id,u.fullname,u.profile_pic');
                        $this->db->from('ws_likes l');
                        $this->db->join('ws_users u', 'l.user_id = u.id');
                        $this->db->where('l.image_id', $img_id);

                        $images_likes['likes_detail'] = $this->db->get()->result_array();
                        if ($images_likes['likes_detail']) {
                            foreach ($images_likes['likes_detail'] as &$detail_friend) {
                                $fr_id = $detail_friend['user_id'];
                                $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                if ($like_frnd) {
                                    $detail_friend['is_friend'] = 1;
                                } else {
                                    $detail_friend['is_friend'] = 0;
                                }
                            }
                        }
                    } else {
                        $images_likes['likes_detail'] = array();
                        $images_likes['like_status'] = 'not liked';
                        $images_likes['likes_proportion'] = '0%';
                    }

                    //unlikes array
                    $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                    if(count($img) == 1){
                        $prop = round ( $unlikesproportion);
                        $total_imgprop += $prop;
                        $images_likes['unlikes_proportion'] = $prop.'%';
                    }
                    if ($unlikes_count > 0) {

                        $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                        $images_likes['unlike_status'] = $unlike_status;
                        //unlikes array

                        $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                        $this->db->from('ws_unlikes ul');
                        $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                        $this->db->where('ul.image_id', $img_id);

                        $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                        if ($images_likes['unlikes_detail']) {
                            foreach ($images_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                $fr_id = $unlikedetail_friend['user_id'];
                                $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                if ($unlike_frnd) {
                                    $unlikedetail_friend['is_friend'] = 1;
                                } else {
                                    $unlikedetail_friend['is_friend'] = 0;
                                }
                            }
                        }
                    } else {
                        $images_likes['unlikes_detail'] = array();
                        $images_likes['unlike_status'] = 'not disliked';
                    }
                }
            } else {
                $data['detail'] = array();
            }
            //images data
        }
        $this->response($data, 200);
    }

    /**
     * ************************************************************************************************
     * Function Name : add_unlike_image_post                                                         *
     * Functionality : unlike image                                                                  *    
     * @author       : pratibha sinha                                                                *
     * @param        : int    image_id                                                               *
     * @param        : int    user_id                                                                *
     * revision 0    : author changes_made                                                           *
     * ************************************************************************************************
     * */
    /* Add unlike on image */

    public function add_unlike_image_post() {

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $image_id = $this->input->post('image_id');
        $this->check_empty($image_id, 'Please add image_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');

        //$deep_link_follow = $this->input->post('deep_link_follow');

        $imagesCount = $this->db->get_where('ws_images' , array('post_id' => $post_id))->result_array();
        if(count($imagesCount) > 1){
            $data = array(
                        'status' => 0,
                        'message' => 'Error'
                    );
            $this->response($data, 200);
        }

        $postResult = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
        if($postResult['delayed_reveal'] == 1){
            $data = array(
                            'status' => 2,
                            'message' => 'Post Reveal'
                        );
        }elseif($postResult['delayed_reveal'] == 2){
            $data = array(
                            'status' => 3,
                            'message' => 'Post Close'
                        );
        }else{
            $delayed_post = $postResult['delayed_post'];
            $delayed_text = $postResult['delayed_text'];
            $delayed_reveal = $postResult['delayed_reveal'];

            $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $image_id, 'user_id' => $user_id), $multi_record = false, $order = '');
            //check already unliked
            if (empty($unlike)) {

                $post_data = array('image_id' => $image_id, 'user_id' => $user_id);
                $last_id = $this->common_model->add('ws_unlikes', $post_data);

                if ($last_id) {
                    //count no. of unlikes of image
                    $unlike_query = "SELECT * FROM ws_unlikes WHERE image_id = '$image_id'";
                    $unlike_result = $this->common_model->getQuery($unlike_query);

                    if (!empty($unlike_result)) {
                        $unlike_count = count($unlike_result);
                    } else {
                        $unlike_count = 0;
                    }
                    //update no. of unlikes in images table

                    $imageunlike_data = array('unlikes' => $unlike_count);
                    $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imageunlike_data);
                    
                    //count no. of likes of image
                    $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $image_id, 'user_id' => $user_id), $multi_record = false, $order = '');

                    //check like
                    if (!empty($like)) {
                        $likeupdated_data = array('image_id' => $image_id, 'user_id' => $user_id);
                        $this->common_model->delete($table = 'ws_likes', $likeupdated_data);
                        $like_query = "SELECT * FROM ws_likes WHERE image_id = '$image_id'";
                        $like_result = $this->common_model->getQuery($like_query);
                        if (!empty($like_result)) {
                            $like_count = count($like_result);
                        } else {
                            $like_count = 0;
                        }
                        //update no. of likes in images table
                        $imagelike_data = array('likes' => $like_count);
                        $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imagelike_data);
                    }


                    //send notification start for post

                    $post_img = $this->common_model->findWhere($table = 'ws_images', array('image_id' => $image_id), $multi_record = false, $order = '');
                    $post_id_val = $post_img['post_id'];
                    $post_owner = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id_val, 'status' => 1), $multi_record = false, $order = '');
                    $receiver = $post_owner['user_id'];
                    
                    //check notification status
                    $notify_chk = $this->check_notification_set($receiver, 'post');
                    //save notification
                    
                    if ($notify_chk == true && $post_owner['user_id'] != $user_id) {
                        $this->save_notification($receiver , 'unlike' , $user_id , $post_id_val);
                        $this->send_notification($receiver , $user_id , 'unlike' , '' , '' , $post_id_val);
                        
                    }

                    //send notification end for post

                    //if($deep_link_follow == 1){
                            $this->follow_default($user_id , $postResult['user_id']);
                    //}

                    $data = array(
                        'status' => 1,
                        'delayed_post' => $delayed_post,
                        'delayed_text' => $delayed_text,
                        'delayed_reveal' => $delayed_reveal,
                        'message' => 'unlike added'
                    );
                } else {
                    $data = array(
                        'status' => 1,
                        'message' => 'Failed'
                    );
                }
            } else {
                //remove unlike
                $unlikeremove_data = array('image_id' => $image_id, 'user_id' => $user_id);
                $this->common_model->delete($table = 'ws_unlikes', $unlikeremove_data);

                //count no. of unlikes of image
                $unlike_query = "SELECT * FROM ws_unlikes WHERE image_id = '$image_id'";
                $unlike_result = $this->common_model->getQuery($unlike_query);
                if (!empty($unlike_result)) {
                    $unlike_count = count($unlike_result);
                } else {
                    $unlike_count = 0;
                }
                //update no. of unlikes in images table
                $imageunlike_data = array('unlikes' => $unlike_count);
                $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imageunlike_data);

                //chk like(less like count by 1 if previously liked)
                $chk_like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $image_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                if ($chk_like) {
                    $likechkremove_data = array('image_id' => $image_id, 'user_id' => $user_id);
                    $this->common_model->delete($table = 'ws_likes', $likechkremove_data);

                    $likechk_query = "SELECT count(*) as count FROM ws_likes WHERE image_id = '$image_id'";
                    $likechk_result = $this->common_model->getQuery($likechk_query);

                    $imagelikechk_data = array('likes' => $likechk_result[0]['count']);
                    $this->common_model->updateWhere('ws_images', $where_data = array('image_id' => $image_id), $imagelikechk_data);
                }
                $data = array(
                    'status' => 0,
                    'message' => 'Already unliked'
                );
            }

            //images data

            $img = $this->db->get_where('ws_images', array('post_id' => $post_id))->result_array();
            $totalPostImageLikes =0;
            $totalPostImageUnlikes =0;
            foreach($img as $im)
            {
            $totalPostImageLikes +=  $im['likes'];
            $totalPostImageUnlikes +=  $im['unlikes'];
            }
            $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
            $total_imgprop = 0;
            $loop_imgindex = 0;
            if (count($img) > 0) {
                $data['detail'] = $this->db->get_where('ws_images', array('post_id' => $post_id))->result_array();

                //check like of user id
                foreach ($data['detail'] as &$images_likes) {
                    $loop_imgindex++;
                    $likes_count = (int) $images_likes['likes'];

                    $unlikes_count = (int) $images_likes['unlikes'];
                    $img_id = (int) $images_likes['image_id'];

                    
                    //likes status
                    if ($likes_count > 0) {

                        $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                        if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                        $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                        }else{
                        $prop = round ( $likesproportion);
                        $total_imgprop += $prop;
                        $images_likes['likes_proportion'] = $prop.'%';
                        }
                        
                        $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                        $images_likes['like_status'] = $like_status;

                        //likes array

                        $this->db->select('l.user_id,u.fullname,u.profile_pic');
                        $this->db->from('ws_likes l');
                        $this->db->join('ws_users u', 'l.user_id = u.id');
                        $this->db->where('l.image_id', $img_id);

                        $images_likes['likes_detail'] = $this->db->get()->result_array();
                        if ($images_likes['likes_detail']) {
                            foreach ($images_likes['likes_detail'] as &$detail_friend) {
                                $fr_id = $detail_friend['user_id'];
                                $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                if ($like_frnd) {
                                    $detail_friend['is_friend'] = 1;
                                } else {
                                    $detail_friend['is_friend'] = 0;
                                }
                            }
                        }
                    } else {
                        $images_likes['likes_detail'] = array();
                        $images_likes['like_status'] = 'not liked';
                        $images_likes['likes_proportion'] = '0%';
                    }

                    //unlikes array
                    $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                    if(count($img) == 1){
                        $prop = round ( $unlikesproportion);
                        $total_imgprop += $prop;
                        $images_likes['unlikes_proportion'] = $prop.'%';
                    }
                    if ($unlikes_count > 0) {

                        $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                        $images_likes['unlike_status'] = $unlike_status;
                        //unlikes array

                        $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                        $this->db->from('ws_unlikes ul');
                        $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                        $this->db->where('ul.image_id', $img_id);

                        $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                        if ($images_likes['unlikes_detail']) {
                            foreach ($images_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                $fr_id = $unlikedetail_friend['user_id'];
                                $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                if ($unlike_frnd) {
                                    $unlikedetail_friend['is_friend'] = 1;
                                } else {
                                    $unlikedetail_friend['is_friend'] = 0;
                                }
                            }
                        }
                    } else {
                        $images_likes['unlikes_detail'] = array();
                        $images_likes['unlike_status'] = 'not disliked';
                    }
                }
            } else {
                $data['detail'] = array();
            }
            //images data
        }
        
        $this->response($data, 200);
    }

    public function add_like_video_post() {
        
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $video_id = $this->input->post('video_id');
        $this->check_empty($video_id, 'Please add video_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        //check post w.r.t image_id

        $post_chk = $this->common_model->findWhere($table = 'ws_videos', array('video_id' => $video_id, 'post_id' => $post_id), $multi_record = false, $order = '');
        if (!empty($post_chk)) {
            $like = $this->common_model->findWhere($table = 'ws_video_likes', array('post_id' => $post_id, 'user_id' => $user_id, 'video_id' => $video_id), $multi_record = false, $order = '');
            //check already liked w.r.t image id
            if (empty($like)) {

                //update count of previously likes
                $newlike = $this->common_model->findWhere($table = 'ws_video_likes', array('post_id' => $post_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $previousliked_data = array('video_id' => $newlike['video_id']);

                $newlikeval = $this->common_model->findWhere($table = 'ws_videos', array('video_id' => $newlike['video_id']), $multi_record = false, $order = '');
                $val = $newlikeval['likes'] - 1;
                $previouslyimagelike = array('likes' => $val);
                $this->common_model->updateWhere('ws_videos', $previousliked_data, $previouslyimagelike);

                //delete previously liked any image w.r.t post start
                $deleteliked_data = array('post_id' => $post_id, 'user_id' => $user_id);
                $this->common_model->delete($table = 'ws_video_likes', $deleteliked_data);

                //delete previously liked any image w.r.t post end

                $post_data = array('video_id' => $video_id, 'user_id' => $user_id, 'post_id' => $post_id);
                $last_id = $this->common_model->add('ws_video_likes', $post_data);

                if ($last_id) {
                    //count no. of likes of image
                    $like_query = "SELECT * FROM ws_video_likes WHERE video_id = '$video_id'";
                    $like_result = $this->common_model->getQuery($like_query);
                    if (!empty($like_result)) {
                        $like_count = count($like_result);
                    } else {
                        $like_count = 0;
                    }

                    //update no. of likes in images table
                    $imagelike_data = array('likes' => $like_count);
                    $this->common_model->updateWhere('ws_videos', $where_data = array('video_id' => $video_id), $imagelike_data);

                    //send notification start for post
                    $post_img = $this->common_model->findWhere($table = 'ws_videos', array('video_id' => $video_id), $multi_record = false, $order = '');
                    $post_id_val = $post_img['post_id'];
                    $post_owner = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id_val, 'status' => 1), $multi_record = false, $order = '');
                    $receiver = $post_owner['user_id'];

                    if ($receiver != $user_id) {
                        $notify_chk = $this->check_notification_set($receiver, 'vote');
                        //save notification
                        
                        if ($notify_chk == true) {
                            $this->save_notification($receiver, 'like', $user_id, $post_id_val);
                            $this->send_notification($receiver, $user_id, 'like', '', '', $post_id_val, '', '');
                            
                            //$this->save_multiple_notification($receiver, 'like', $user_id, $post_id_val);
                        }
                    }
                    //send notification end for post
                    //chk unlike(less unlike count by 1 if previously unliked)

                    $chk_unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $video_id, 'user_id' => $user_id), $multi_record = false, $order = '');

                    if ($chk_unlike) {
                        $unlikechkremove_data = array('video_id' => $video_id, 'user_id' => $user_id);
                        $this->common_model->delete($table = 'ws_video_unlikes', $unlikechkremove_data);

                        $unlikechk_query = "SELECT count(*) as count FROM ws_video_unlikes WHERE video_id = '$video_id'";
                        $unlikechk_result = $this->common_model->getQuery($unlikechk_query);

                        $imageunlikechk_data = array('unlikes' => $unlikechk_result[0]['count']);
                        $this->common_model->updateWhere('ws_videos', $where_data = array('video_id' => $video_id), $imageunlikechk_data);
                    }
                    $data = array(
                        'status' => 1,
                        'message' => 'like added'
                    );
                } else {
                    $data = array(
                        'status' => 1,
                        'message' => 'Failed'
                    );
                }
            } else {
                //remove like
                $likeremove_data = array('video_id' => $video_id, 'user_id' => $user_id, 'post_id' => $post_id);
                $this->common_model->delete($table = 'ws_video_likes', $likeremove_data);

                //count change
                $likeremove_query = "SELECT * FROM ws_video_likes WHERE video_id = '$video_id'";
                $likeremove_result = $this->common_model->getQuery($likeremove_query);
                //echo '<pre>';var_dump($likeremove_result);
                if (!empty($likeremove_result)) {
                    $likeremove_count = count($likeremove_result);
                } else {
                    $likeremove_count = 0;
                }
                //die;
                $imagelikeremove_data = array('likes' => $likeremove_count);
                $this->common_model->updateWhere('ws_videos', $where_data = array('video_id' => $video_id), $imagelikeremove_data);

                $data = array(
                    'status' => 0,
                    'message' => 'Already liked post video'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Either post_id or video_id is wrong'
            );
        }

        //videos data
        $vid = $this->db->get_where('ws_videos', array('post_id' => $post_id))->result_array();

        if (count($vid) > 0) {
            $data['detail'] = $this->db->get_where('ws_videos', array('post_id' => $post_id))->result_array();
            foreach ($data['detail'] as &$video_likes) {
                
                $likes_count = (int) $video_likes['likes'];

                $unlikes_count = (int) $video_likes['unlikes'];
                $vid_id = (int) $video_likes['video_id'];
                //likes status
                if ($likes_count > 0) {
                    
                    $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                    $video_likes['like_status'] = $like_status;

                    //likes array

                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                    $this->db->from('ws_video_likes l');
                    $this->db->join('ws_users u', 'l.user_id = u.id');
                    $this->db->where('l.video_id', $vid_id);

                    $video_likes['likes_detail'] = $this->db->get()->result_array();

                    if ($video_likes['likes_detail']) {
                        foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                            $fr_id = $vidlikedetail_friend['user_id'];
                            $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                            $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                            if ($vidlike_frnd) {
                                $vidlikedetail_friend['is_friend'] = 1;
                            } else {
                                $vidlikedetail_friend['is_friend'] = 0;
                            }
                        }
                    }
                } else {
                    $video_likes['likes_detail'] = array();
                    $video_likes['like_status'] = 'not liked';
                }

                //unlikes array
                if ($unlikes_count > 0) {
                //unlikes array
                $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                $video_likes['unlike_status'] = $unlike_status;

                $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                $this->db->from('ws_video_unlikes ul');
                $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                $this->db->where('ul.video_id', $vid_id);

                $video_likes['unlikes_detail'] = $this->db->get()->result_array();

            } else {
                $video_likes['unlikes_detail'] = array();
                $video_likes['unlike_status'] = 'not disliked';
            }
            }
        } else {
            $data['detail'] = array();
        }
        $this->response($data, 200);
    }

    public function add_unlike_video_post() {
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $video_id = $this->input->post('video_id');
        $this->check_empty($video_id, 'Please add video_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');

        $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $video_id, 'user_id' => $user_id), $multi_record = false, $order = '');
        //check already unliked
        if (empty($unlike)) {

            $post_data = array('video_id' => $video_id, 'user_id' => $user_id);
            $last_id = $this->common_model->add('ws_video_unlikes', $post_data);

            if ($last_id) {
                //count no. of unlikes of image
                $unlike_query = "SELECT * FROM ws_video_unlikes WHERE video_id = '$video_id'";
                $unlike_result = $this->common_model->getQuery($unlike_query);

                if (!empty($unlike_result)) {
                    $unlike_count = count($unlike_result);
                } else {
                    $unlike_count = 0;
                }
                //update no. of unlikes in images table
                $imageunlike_data = array('unlikes' => $unlike_count);
                $this->common_model->updateWhere('ws_videos', $where_data = array('video_id' => $video_id), $imageunlike_data);

                //count no. of likes of image
                $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $video_id, 'user_id' => $user_id), $multi_record = false, $order = '');

                //check like
                if (!empty($like)) {
                    $likeupdated_data = array('video_id' => $video_id, 'user_id' => $user_id);
                    $this->common_model->delete($table = 'ws_video_likes', $likeupdated_data);
                    $like_query = "SELECT * FROM ws_video_likes WHERE video_id = '$video_id'";
                    $like_result = $this->common_model->getQuery($like_query);
                    if (!empty($like_result)) {
                        $like_count = count($like_result);
                    } else {
                        $like_count = 0;
                    }
                    //update no. of likes in images table
                    $imagelike_data = array('likes' => $like_count);
                    $this->common_model->updateWhere('ws_videos', $where_data = array('video_id' => $video_id), $imagelike_data);
                }


                //send notification start for post
                $post_img = $this->common_model->findWhere($table = 'ws_videos', array('video_id' => $video_id), $multi_record = false, $order = '');
                $post_id_val = $post_img['post_id'];
                $post_owner = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id_val, 'status' => 1), $multi_record = false, $order = '');
                $receiver = $post_owner['user_id'];
                /*$block_chk = $this->check_block($receiver, $user_id);
                if ($block_chk == false) {
                    $this->send_notification($receiver, $user_id, 'unlike', '', '', $post_id_val);
                }*/

                //check notification status
                $notify_chk = $this->check_notification_set($receiver, 'post');
                //save notification
                
                if ($notify_chk == true && $post_owner['user_id'] != $user_id) {
                    $this->save_notification($receiver , 'unlike' , $user_id , $post_id_val);
                    $this->send_notification($receiver , $user_id , 'unlike' , '' , '' , $post_id_val);
                    
                }

                //send notification end for post
                $data = array(
                    'status' => 1,
                    'message' => 'unlike added'
                );
            } else {
                $data = array(
                    'status' => 1,
                    'message' => 'Failed'
                );
            }
        } else {
            //remove unlike
            $unlikeremove_data = array('video_id' => $video_id, 'user_id' => $user_id);
            $this->common_model->delete($table = 'ws_video_unlikes', $unlikeremove_data);

            //count no. of unlikes of image
            $unlike_query = "SELECT * FROM ws_video_unlikes WHERE video_id = '$video_id'";
            $unlike_result = $this->common_model->getQuery($unlike_query);
            if (!empty($unlike_result)) {
                $unlike_count = count($unlike_result);
            } else {
                $unlike_count = 0;
            }
            //update no. of unlikes in images table

            $imageunlike_data = array('unlikes' => $unlike_count);
            $this->common_model->updateWhere('ws_videos', $where_data = array('video_id' => $video_id), $imageunlike_data);

            //chk like(less like count by 1 if previously liked)

            $chk_like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $video_id, 'user_id' => $user_id), $multi_record = false, $order = '');
            if ($chk_like) {
                $likechkremove_data = array('video_id' => $video_id, 'user_id' => $user_id);
                $this->common_model->delete($table = 'ws_video_likes', $likechkremove_data);

                $likechk_query = "SELECT count(*) as count FROM ws_video_likes WHERE video_id = '$video_id'";
                $likechk_result = $this->common_model->getQuery($likechk_query);

                $imagelikechk_data = array('likes' => $likechk_result[0]['count']);
                $this->common_model->updateWhere('ws_videos', $where_data = array('video_id' => $video_id), $imagelikechk_data);
            }
            $data = array(
                'status' => 0,
                'message' => 'Already unliked'
            );
        }

        //videos data
        $vid = $this->db->get_where('ws_videos', array('post_id' => $post_id))->result_array();

        if (count($vid) > 0) {
            $data['detail'] = $this->db->get_where('ws_videos', array('post_id' => $post_id))->result_array();
            foreach ($data['detail'] as &$video_likes) {
               
                $likes_count = (int) $video_likes['likes'];

                $unlikes_count = (int) $video_likes['unlikes'];
                $vid_id = (int) $video_likes['video_id'];
                //likes status
                if ($likes_count > 0) {
                    $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                    $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                    $video_likes['like_status'] = $like_status;

                    //likes array

                    $this->db->select('l.user_id,u.fullname,u.profile_pic');
                    $this->db->from('ws_video_likes l');
                    $this->db->join('ws_users u', 'l.user_id = u.id');
                    $this->db->where('l.video_id', $vid_id);

                    $video_likes['likes_detail'] = $this->db->get()->result_array();

                    if ($video_likes['likes_detail']) {
                        foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                            $fr_id = $vidlikedetail_friend['user_id'];
                            $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                            $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                            if ($vidlike_frnd) {
                                $vidlikedetail_friend['is_friend'] = 1;
                            } else {
                                $vidlikedetail_friend['is_friend'] = 0;
                            }
                        }
                    }
                } else {
                    $video_likes['likes_detail'] = array();
                    $video_likes['like_status'] = 'not liked';
                }

                //unlikes array
                if ($unlikes_count > 0) {
                
                $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                $video_likes['unlike_status'] = $unlike_status;

                $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                $this->db->from('ws_video_unlikes ul');
                $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                $this->db->where('ul.video_id', $vid_id);

                $video_likes['unlikes_detail'] = $this->db->get()->result_array();

            } else {
                $video_likes['unlikes_detail'] = array();
                $video_likes['unlike_status'] = 'not disliked';
            }
            }
        } else {
            $data['detail'] = array();
        }
        $this->response($data, 200);
    }

    /**
     * ************************************************************************************************
     * Function Name : add_comment_post                                                              *
     * Functionality : comment on post                                                               *    
     * @author       : pratibha sinha                                                                *
     * @param        : int    user_id                                                                *
     * @param        : int    task_id                                                                *
     * @param        : int    comment                                                                *
     * revision 0    : author changes_made                                                           *
     * ************************************************************************************************
     * */
    /* Add comment */

    function add_comment_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');
        $task_id = $this->input->post('task_id');
        $this->check_empty($task_id, 'Please add task id');

        $comment = $this->input->post('comment');
        $this->check_empty($comment, 'Please add comment');

        $added_at = date('Y-m-d H:i:s', time());

        $mention_indexes = $this->input->post('mention_indexes');
        $mention_users = $this->input->post('mention_users');

        $datetime1 = date('Y-m-d H:i:s', time());
        $post_data = array(
            'post_id' => $task_id,
            'user_id' => $user_id,
            'status' => '1',
            'comment' => $comment,
            'added_at' => $added_at,
            'mention_indexes' => ($mention_indexes != '' ? $mention_indexes : ''),
            'mention_users' => (!empty($mention_users) ? $mention_users : '')
        );

        if ($this->db->insert('ws_comments', $post_data)) {

            //last single comment
            $comment_insert_id = $this->db->insert_id();
            $comment_data = $this->common_model->findWhere($table = 'ws_comments', array('comment_id' => $comment_insert_id), $multi_record = false, $order = '');

            $commentuser_name = array();

            $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $comment_data['user_id']), $multi_record = false, $order = '');
            $sender_name = $comment_sender['fullname'];
            $profile_pic = $comment_sender['profile_pic'];
            
            $new_time = $this->time_elapsed_string($datetime1, $comment_data['added_at']);
            if (!empty($comment_data['mention_users'])) {
                $mention_datausers = explode(',', $comment_data['mention_users']);
               //echo '<pre>';print_r($mention_datausers);
               foreach ($mention_datausers as $key => $value) {
                        //echo 'qq'.$value;
                        $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                        $commentuser_name[] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';

                        //notification
                        //$receiver = $value;
                        $this->save_notification($value, 'mentioned_comment', $user_id, $task_id);

                        $this->send_notification($value, $user_id, 'mentioned_comment', '', '', $task_id);
                    }
            }

            //last 2 comment data
            $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $task_id), 2)->result_array();

            if (count($last_comment) > 0) {
                $commentList = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $task_id), 2)->result_array();
                sort($commentList);
                
                foreach ($commentList as &$commentDetail) {
                    $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                    $commentDetail['sender_name'] = $comment_sender['fullname'];
                    $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                    $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);

                    $commentDetail['commentuser_name'] = array();
                    if (!empty($commentDetail['mention_users'])) {
                        foreach (explode(',', $commentDetail['mention_users']) as $key => $value) {
                            $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                            $commentDetail['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                        }
                    }
                }
            } else {
                $commentList = array();
            }

            $totalComment = $this->db->get_where('ws_comments' , array('post_id' => $task_id))->result_array();
            $commentCount = count($totalComment);
            $comment_array[] = array(
                'comment_id' => $comment_insert_id,
                'mention_indexes' => $comment_data['mention_indexes'],
                'mention_users' => $comment_data['mention_users'],
                'mention_username' => $commentuser_name,
                'user_id' => $comment_data['user_id'],
                'post_id' => $comment_data['post_id'],
                'status' => $comment_data['status'],
                'comment' => $comment_data['comment'],
                'added_at' => $comment_data['added_at'],
                'sender_name' => $sender_name,
                'profile_pic' => $profile_pic,
                'new_time' => $new_time
            );

            $data = array(
                'status' => 1,
                'message' => 'Comment has been added',
                'commentCount' => $commentCount,
                'comment' => $comment_array,
                'commentList' => $commentList
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Comment could not be added'
            );
        }
        
        //notification send to post creator

        $post_data = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $task_id), $multi_record = false, $order = '');
        $receiver = $post_data['user_id'];

        if($post_data){
            $notify_chk = $this->check_notification_set($receiver, 'comment');
            if ($receiver != $user_id) {
                $this->save_notification($receiver, 'comment', $user_id, $task_id);
                if ($notify_chk == true) {
                    $this->send_notification($receiver, $user_id, 'comment', '', '', $task_id);
                }
            }
        }
        

        //send notification end for post
        $this->response($data, 200);
    }

    function add_comment_old_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');
        $task_id = $this->input->post('task_id');
        $this->check_empty($task_id, 'Please add task id');

        $comment = $this->input->post('comment');
        $this->check_empty($comment, 'Please add comment');

        $added_at = date('Y-m-d H:i:s', time());

        if ($this->db->insert('ws_comments', array('post_id' => $task_id, 'user_id' => $user_id, 'status' => '1', 'comment' => $comment, 'added_at' => $added_at))) {

            $comment_insert_id = $this->db->insert_id();
            $comment_data = $this->common_model->findWhere($table = 'ws_comments', array('comment_id' => $comment_insert_id), $multi_record = false, $order = '');
            $comment_array[] = array('comment_id' => $comment_insert_id, 'user_id' => $comment_data['user_id'], 'post_id' => $comment_data['post_id'], 'comment' => $comment_data['comment'], 'added_at' => $comment_data['added_at']);

            $data = array(
                'status' => 1,
                'message' => 'Comment has been added',
                'comment' => $comment_array
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Comment could not be added'
            );
        }
        //send notification start for post
        $post_data = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $task_id), $multi_record = false, $order = '');
        $receiver = $post_data['user_id'];

        $notify_chk = $this->check_notification_set($receiver, 'comment');
        if ($receiver != $user_id) {
            $this->save_notification($receiver, 'comment', $user_id, $task_id);
            if ($notify_chk == true) {
                $this->send_notification($receiver, $user_id, 'comment', '', '', $task_id);
            }
        }
        //send notification end for post
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : get_friendlist                                                              *
     * Functionality : List of all friends including block status                                  *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function get_friendlist_post() {
        error_reporting(0);
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');
        $frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' or friend_id = '$user_id') AND status = 1";
        $frnd_data = $this->common_model->getQuery($frnd_query);
        
        if ($frnd_data) {
            foreach ($frnd_data as $frnd) {
                if ($frnd['user_id'] == $user_id) {
                    $id = $frnd['friend_id'];
                } else {
                    $id = $frnd['user_id'];
                }
                $frndexist_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $id, 'activated' => 1), $multi_record = false, $order = '');

                $blockfriend_query = $this->common_model->findWhere($table = 'ws_block', array('user_id' => $user_id, 'friend_id' => $id), $multi_record = false, $order = '');

                if ($blockfriend_query) {
                    $block_status = 'block';
                } else {
                    $block_status = 'unblock';
                }

                $Data[] = array(
                    'friend_id' => $id,
                    'friend_name' => $frndexist_data['fullname'],
                    'friend_img_url' => (!empty($frndexist_data['profile_pic']) ? $base_url . $frndexist_data['profile_pic'] : ''),
                    'friend_block_status' => $block_status
                );
                $data = array(
                    'status' => 1,
                    'data' => $Data
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'No Friends'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : block_friend                                                                *
     * Functionality : Block friend                                                                *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * @param      : int    friend_id                                                              *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function block_friend_post() {
        error_reporting(0);
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');

        $friend_query = "Select * from ws_friend_list where (user_id = '$user_id' OR friend_id = '$user_id') AND (user_id = '$friend_id' OR friend_id = '$friend_id')";
        $frnd_data = $this->common_model->getQuery($friend_query);

        if ($frnd_data) {
            $blockfriend_query = $this->common_model->findWhere($table = 'ws_block', array('user_id' => $user_id, 'friend_id' => $friend_id), $multi_record = false, $order = '');
            if ($blockfriend_query) {
                $data = array(
                    'status' => 1,
                    'message' => 'Already Blocked'
                );
            } else {
                $post_data = array(
                    'user_id' => $user_id,
                    'friend_id' => $friend_id,
                    'created' => date('Y-m-d h:i')
                );
                if ($last_id = $this->common_model->add('ws_block', $post_data)) {
                    $data = array(
                        'status' => 1,
                        'message' => 'Friend Blocked Successfully'
                    );
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'Unable to block'
                    );
                }
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'No Friends'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : unblock_friend                                                              *
     * Functionality : Unblock friend                                                              *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * * @param      : int    friend_id                                                            *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function unblock_friend_post() {
        error_reporting(0);
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');

        $friend_query = "Select * from ws_friend_list where (user_id = '$user_id' OR friend_id = '$user_id') AND (user_id = '$friend_id' OR friend_id = '$friend_id')";
        $frnd_data = $this->common_model->getQuery($friend_query);

        if ($frnd_data) {
            $post_data = array(
                'user_id' => $user_id,
                'friend_id' => $friend_id
            );
            if ($this->common_model->delete($table = 'ws_block', $post_data)) {
                $data = array(
                    'status' => 1,
                    'message' => 'Friend Unblocked Successfully'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Unable to block'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'No Friends'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : follow_celebrity                                                            *
     * Functionality : follow celebrity                                                            *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * @param        : int    celebrity_id                                                         *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function follow_celebrity_post() {
        error_reporting(0);
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $celebrity_id = $this->input->post('celebrity_id');
        $this->check_empty($celebrity_id, 'Please add celebrity_id');
        // user exist
        $user_query = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');

        $celebrityuser_query = $this->common_model->findWhere($table = 'ws_users', array('id' => $celebrity_id, 'activated' => 1), $multi_record = false, $order = '');
        if ($user_query) {    // celebrity user exist
            if ($celebrityuser_query) {    //celebrity user is celebrity or not
                $celebrity_query = $this->common_model->findWhere($table = 'ws_users', array('id' => $celebrity_id, 'celebrity' => 1, 'activated' => 1), $multi_record = false, $order = '');
                if ($celebrity_query) {
                    $post_data = array('user_id' => $user_id, 'celebrity_id' => $celebrity_id);
                    //check already follower
                    $alreadyfollower_query = $this->common_model->findWhere($table = 'ws_followers_celebrity', $post_data, $multi_record = false, $order = '');
                    if ($alreadyfollower_query) {
                        $data = array(
                            'status' => 0,
                            'message' => 'Already follower'
                        );
                    } else {
                        $last_id = $this->common_model->add('ws_followers_celebrity', $post_data);
                        if ($last_id) {
                            $data = array(
                                'status' => 1,
                                'message' => 'Celebrity added to your followers list'
                            );
                        } else {
                            $data = array(
                                'status' => 0,
                                'message' => 'Error'
                            );
                        }
                    }
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'Celebrity Id is Wrong'
                    );
                }
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Celebrity Id does not exist'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'UserId does not exist'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : Unfollow_celebrity                                                          *
     * Functionality : Unfollow celebrity                                                          *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * @param        : int    celebrity_id                                                         *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function unfollow_celebrity_post() {
        error_reporting(0);
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $celebrity_id = $this->input->post('celebrity_id');
        $this->check_empty($celebrity_id, 'Please add celebrity_id');

        $where_data = array('user_id' => $user_id, 'celebrity_id' => $celebrity_id);
        //check already follower or not
        $alreadyfollower_query = $this->common_model->findWhere($table = 'ws_followers_celebrity', $where_data, $multi_record = false, $order = '');
        if ($alreadyfollower_query) {
            $post_data = array(
                'user_id' => $user_id,
                'celebrity_id' => $celebrity_id
            );
            if ($this->common_model->delete($table = 'ws_followers_celebrity', $post_data)) {
                $data = array(
                    'status' => 1,
                    'message' => 'Celebrity removed from your followers list!'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Error'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Celebrity id does not exist in follower list'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : get_celebrity_list                                                          *
     * Functionality : list of celebrities followed by user                                        *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function get_celebrity_list_post() {
        error_reporting(0);
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $query = "select * from ws_followers_celebrity where user_id = '$user_id'";
        $celebrity_data = $this->common_model->getQuery($query);

        // check follower list of userid exist or not
        if ($celebrity_data) {
            foreach ($celebrity_data as $celebrity) {

                $celebrityData = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $celebrity['celebrity_id'], 'activated' => 1), $multi_record = false, $order = '');
                $Data[] = array(
                    'celebrity_name' => $celebrityData['fullname'],
                    'celebrity_img_url' => (!empty($celebrityData['profile_pic']) ? $base_url . $celebrityData['profile_pic'] : ''),
                    'celebrity_id' => $celebrityData['id'],
                );
                $data = array(
                    'status' => 1,
                    'data' => $Data
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'No celebrity in your follower list'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : get_allcelebrity_list                                                       *
     * Functionality : list of celebrities                                                         *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function get_allcelebrity_list_post() {
        error_reporting(0);
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $userData = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');

        if ($userData) {
            $query = "SELECT id,profile_pic,fullname FROM ws_users where (id != '$user_id' AND celebrity = '1' AND activated = 1)";
            $celebrity_data = $this->common_model->getQuery($query);
            if ($celebrity_data) {
                foreach ($celebrity_data as $celebrity) {

                    $Data[] = array(
                        'celebrity_name' => (!empty($celebrity['fullname']) ? $celebrity['fullname'] : ''),
                        'celebrity_img_url' => (!empty($celebrity['profile_pic']) ? $base_url . $celebrity['profile_pic'] : ''),
                        'celebrity_id' => (!empty($celebrity['id']) ? $celebrity['id'] : ''),
                    );
                    $data = array(
                        'status' => 1,
                        'data' => $Data
                    );
                }
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'No celebrity'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'user_id does not exist'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : sound_notification_post                                                     *
     * Functionality : set sound notification                                                      *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * @param        : int    notification_type                                                    *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    //set sound notification 
    public function sound_notification_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $notification_type = $this->input->post('notification_type');
        $this->check_empty($notification_type, 'Please add notification_type');

        $notification_array = explode(",", $notification_type);

        $sound_detail = $this->common_model->findWhere($table = 'ws_sound_notification', $where_data = array('user_id' => $user_id), $multi_record = false, $order = '');

        if (!empty($sound_detail)) {
            $data = array(
                'status' => 0,
                'message' => 'Already set'
            );
        } else {
            $post_all = 1;
            $post_friend = 1;
            $like_all = 1;
            $like_friend = 1;
            $comment_all = 1;
            $comment_friend = 1;
            $tag_all = 1;
            $tag_friend = 1;
            $all_sound_all = 1;
            $all_sound_friend = 1;

            foreach ($notification_array as $notification_type_val) {
                if ($notification_type_val == 'post_all') {
                    $post_all = 0;
                    $post_friend = 0;
                } elseif ($notification_type_val == 'post_friend') {
                    $post_friend = 0;
                } elseif ($notification_type_val == 'like_all') {
                    $like_all = 0;
                    $like_friend = 0;
                } elseif ($notification_type_val == 'like_friend') {
                    $like_friend = 0;
                } elseif ($notification_type_val == 'comment_all') {
                    $comment_all = 0;
                    $comment_friend = 0;
                } elseif ($notification_type_val == 'comment_friend') {
                    $comment_friend = 0;
                } elseif ($notification_type_val == 'tag_all') {
                    $tag_all = 0;
                    $tag_friend = 0;
                } elseif ($notification_type_val == 'tag_friend') {
                    $tag_friend = 0;
                } elseif ($notification_type_val == 'all_sound_all') {
                    $all_sound_all = 0;
                    $post_all = 0;
                    $like_all = 0;
                    $comment_all = 0;
                    $tag_all = 0;
                    $all_sound_friend = 0;
                    $post_friend = 0;
                    $like_friend = 0;
                    $comment_friend = 0;
                    $tag_friend = 0;
                } elseif ($notification_type_val == 'all_sound_friend') {
                    $all_sound_friend = 0;
                    $post_friend = 0;
                    $like_friend = 0;
                    $comment_friend = 0;
                    $tag_friend = 0;
                }
            }
            $post_data = array(
                'user_id' => $user_id,
                'post_all' => $post_all,
                'post_friend' => $post_friend,
                'like_all' => $like_all,
                'like_friend' => $like_friend,
                'comment_all' => $comment_all,
                'comment_friend' => $comment_friend,
                'tag_all' => $tag_all,
                'tag_friend' => $tag_friend,
                'all_sound_all' => $all_sound_all,
                'all_sound_friend' => $all_sound_friend
            );
            $last_id = $this->common_model->add('ws_sound_notification', $post_data);
            if ($last_id) {
                $data = array(
                    'status' => 1,
                    'message' => 'Success'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Error'
                );
            }
        }
        $this->response($data, 200);
    }

    public function following_list_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $base_url = $this->baseurl;
        $user_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id), $multi_record = false, $order = '');
        if ($user_data) {
            $this->db->where('friend_id', $user_id);
            $following = $this->db->get('ws_follow')->result_array();
            $DATA = array();
            if ($following) {
                foreach ($following as $list) {
                    $following_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['user_id']), $multi_record = false, $order = '');

                    //chk follow
                    $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $list['user_id']), $multi_record = false, $order = '');

                    $DATA[] = array(
                        'following_id' => $list['user_id'],
                        'following_name' => (!empty($following_data['fullname']) ? $following_data['fullname'] : ''),
                        'following_pic' => (!empty($following_data['profile_pic']) ? $base_url . $following_data['profile_pic'] : ''),
                        'unique_name' => (!empty($following_data['unique_name']) ? $following_data['unique_name'] : ''),
                        'phone' => (!empty($following_data['phone']) ? $following_data['phone'] : ''),
                        'email' => (!empty($following_data['email']) ? $following_data['email'] : ''),
                        'follow' => (!empty($chk_follow_data) ? '1' : '0'),
                    );
                }
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            } else {
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exist'
            );
        }
        $this->response($data, 200);
    }

    public function following_new_list_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please add group_id');

        $base_url = $this->baseurl;
        $user_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id), $multi_record = false, $order = '');
        if ($user_data) {
            $this->db->where('friend_id', $user_id);
            $following = $this->db->get('ws_follow')->result_array();
            //echo $this->db->last_query();die;
            $DATA = array();
            if ($following) {
                foreach ($following as $list) {

                    //chk alraedy group member
                    $groupmember_data = $this->common_model->findWhere($table = 'ws_group_members', array('member_id' => $list['user_id'] , 'group_id' => $group_id , 'status' => 1), $multi_record = false, $order = '');
                    //chk alraedy group member
                    
                    if(empty($groupmember_data))
                    {
                        $following_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['user_id']), $multi_record = false, $order = '');

                        //chk follow
                        $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $list['user_id']), $multi_record = false, $order = '');

                        $DATA[] = array(
                            'following_id' => $list['user_id'],
                            'following_name' => (!empty($following_data['fullname']) ? $following_data['fullname'] : ''),
                            'following_pic' => (!empty($following_data['profile_pic']) ? $base_url . $following_data['profile_pic'] : ''),
                            'unique_name' => (!empty($following_data['unique_name']) ? $following_data['unique_name'] : ''),
                            'phone' => (!empty($following_data['phone']) ? $following_data['phone'] : ''),
                            'email' => (!empty($following_data['email']) ? $following_data['email'] : ''),
                            'follow' => (!empty($chk_follow_data) ? '1' : '0'),
                        );
                    }
                }
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            } else {
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exist'
            );
        }
        $this->response($data, 200);
    }

    public function follower_following_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $base_url = $this->baseurl;

        $user_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id), $multi_record = false, $order = '');
        if ($user_data) {
            $follower = $this->db->get_where('ws_follow', array('user_id' => $user_id))->result_array();
            $FOLLOWERDATA = array();
            if ($follower) {
                foreach ($follower as $list) {
                    $follower_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['friend_id']), $multi_record = false, $order = '');

                    //chk follow
                    $FOLLOWERDATA[] = array(
                        'id' => $list['friend_id'],
                        'name' => (!empty($follower_data['fullname']) ? $follower_data['fullname'] : ''),
                        'profile_pic' => (!empty($follower_data['profile_pic']) ? $base_url . $follower_data['profile_pic'] : ''),
                        'unique_name' => (!empty($follower_data['unique_name']) ? $follower_data['unique_name'] : ''),
                        'phone' => (!empty($follower_data['phone']) ? $follower_data['phone'] : ''),
                        'email' => (!empty($follower_data['email']) ? $follower_data['email'] : ''),
                    );
                }
            }
            //following data

            $following = $this->db->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
            $FOLLOWINGDATA = array();
            if ($following) {
                foreach ($following as $list) {
                    $following_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['user_id']), $multi_record = false, $order = '');

                    //chk follow
                    $FOLLOWINGDATA[] = array(
                        'id' => $list['user_id'],
                        'name' => (!empty($following_data['fullname']) ? $following_data['fullname'] : ''),
                        'profile_pic' => (!empty($following_data['profile_pic']) ? $base_url . $following_data['profile_pic'] : ''),
                        'unique_name' => (!empty($following_data['unique_name']) ? $following_data['unique_name'] : ''),
                        'phone' => (!empty($following_data['phone']) ? $following_data['phone'] : ''),
                        'email' => (!empty($following_data['email']) ? $following_data['email'] : ''),
                    );
                }
            }
            $combine_array = array();
            $combine_follower_following = array();
            $combine_follower_following = array_merge((array) $FOLLOWERDATA, (array) $FOLLOWINGDATA);
            $combine_array = array_unique($combine_follower_following, SORT_REGULAR);
            $Detail = array();
            foreach ($combine_array as $key => $value) {
                array_push($Detail, $value);
            }
            $data = array(
                'status' => 1,
                'data' => $Detail
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exist'
            );
        }
        $this->response($data, 200);
    }

    public function get_tag_users_post()
    {
        //error_reporting('E_ALL');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $chk_user = $this->db->get_where('ws_users' , array('id' => $user_id))->row_array();
        if($chk_user)
        {
             
            $chk_post = $this->db->get_where('ws_posts' , array('post_id' => $post_id ,'share_with' => 'group'))->row_array();
            if($chk_post)
            {
                $group_id = $chk_post['group_id'];
                $members = $this->db->get_where('ws_group_members', array('group_id' => $group_id , 'member_id !=' => $user_id))->result_array();
                $MEMBERSDATA = array();
                if($members)
                {
                    foreach ($members as $list) {
                        $member_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['member_id']), $multi_record = false, $order = '');

                        //chk follow
                        $MEMBERSDATA[] = array(
                            'id' => $list['member_id'],
                            'name' => (!empty($member_data['fullname']) ? $member_data['fullname'] : ''),
                            'profile_pic' => (!empty($member_data['profile_pic']) ? $base_url . $member_data['profile_pic'] : ''),
                            'unique_name' => (!empty($member_data['unique_name']) ? $member_data['unique_name'] : ''),
                            'phone' => (!empty($member_data['phone']) ? $member_data['phone'] : ''),
                            'email' => (!empty($member_data['email']) ? $member_data['email'] : ''),
                        );
                    }
                    $data = array(
                        'status' => 1,
                        'data' => $MEMBERSDATA
                    );
                }else{
                    $data = array(
                        'status' => 0,
                        'message' => 'group members does not exist'
                    );
                }
            }
            else
            {
                $follower = $this->db->get_where('ws_follow', array('user_id' => $user_id))->result_array();
                $FOLLOWERDATA = array();
                if ($follower) {
                    foreach ($follower as $list) {
                        $follower_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['friend_id']), $multi_record = false, $order = '');

                        //chk follow
                        $FOLLOWERDATA[] = array(
                            'id' => $list['friend_id'],
                            'name' => (!empty($follower_data['fullname']) ? $follower_data['fullname'] : ''),
                            'profile_pic' => (!empty($follower_data['profile_pic']) ? $base_url . $follower_data['profile_pic'] : ''),
                            'unique_name' => (!empty($follower_data['unique_name']) ? $follower_data['unique_name'] : ''),
                            'phone' => (!empty($follower_data['phone']) ? $follower_data['phone'] : ''),
                            'email' => (!empty($follower_data['email']) ? $follower_data['email'] : ''),
                        );
                    }
                }
                //following data

                $following = $this->db->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
                $FOLLOWINGDATA = array();
                if ($following) {
                    foreach ($following as $list) {
                        $following_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['user_id']), $multi_record = false, $order = '');

                        //chk follow
                        $FOLLOWINGDATA[] = array(
                            'id' => $list['user_id'],
                            'name' => (!empty($following_data['fullname']) ? $following_data['fullname'] : ''),
                            'profile_pic' => (!empty($following_data['profile_pic']) ? $base_url . $following_data['profile_pic'] : ''),
                            'unique_name' => (!empty($following_data['unique_name']) ? $following_data['unique_name'] : ''),
                            'phone' => (!empty($following_data['phone']) ? $following_data['phone'] : ''),
                            'email' => (!empty($following_data['email']) ? $following_data['email'] : ''),
                        );
                    }
                }
                $combine_array = array();
                $combine_follower_following = array();
                $combine_follower_following = array_merge((array) $FOLLOWERDATA, (array) $FOLLOWINGDATA);
                $combine_array = array_unique($combine_follower_following, SORT_REGULAR);
                $Detail = array();
                foreach ($combine_array as $key => $value) {
                    array_push($Detail, $value);
                }
                $data = array(
                    'status' => 1,
                    'data' => $Detail
                );
            }  
        }
        else
        {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exist'
            );
        }
        $this->response($data, 200);    
    }

    public function follower_list_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $base_url = $this->baseurl;

        $user_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id), $multi_record = false, $order = '');
        if ($user_data) {
            $this->db->where('user_id', $user_id);
            $follower = $this->db->get('ws_follow')->result_array();
            $DATA = array();
            if ($follower) {
                foreach ($follower as $list) {
                    $follower_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['friend_id']), $multi_record = false, $order = '');

                    //chk follow
                    $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $list['friend_id']), $multi_record = false, $order = '');

                    $DATA[] = array(
                        'follower_id' => $list['friend_id'],
                        'follower_name' => (!empty($follower_data['fullname']) ? $follower_data['fullname'] : ''),
                        'follower_pic' => (!empty($follower_data['profile_pic']) ? $base_url . $follower_data['profile_pic'] : ''),
                        'unique_name' => (!empty($follower_data['unique_name']) ? $follower_data['unique_name'] : ''),
                        'phone' => (!empty($follower_data['phone']) ? $follower_data['phone'] : ''),
                        'email' => (!empty($follower_data['email']) ? $follower_data['email'] : ''),
                         'follow' => (!empty($chk_follow_data) ? '1' : '0')
                    );
                }
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            } else {
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exist'
            );
        }
        $this->response($data, 200);
    }

    public function search_members_post() {
        $base_url = $this->baseurl;
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please add group_id');

        $this->db->where('group_id', $group_id);
        $group_members = $this->db->get('ws_group_members')->result_array();
        $GROUP_DATA = array();
        if ($group_members) {
            foreach ($group_members as $key => $members_val) {
                $GROUP_DATA[] = $members_val['member_id'];
            }
        }
        
        $this->db->where('user_id', $user_id);
        $followers = $this->db->get('ws_follow')->result_array();
        $FOLLOWER_DATA = array();
        if ($followers) {
            foreach ($followers as $follower_list) {
                if (!in_array($follower_list['friend_id'], $GROUP_DATA)) {
                    $follower_val = $this->common_model->findWhere($table = 'ws_users', array('id' => $follower_list['friend_id']), $multi_record = false, $order = '');

                    $FOLLOWER_DATA[] = array(
                        'id' => $follower_list['friend_id'],
                        'fullname' => (!empty($follower_val['fullname']) ? $follower_val['fullname'] : ''),
                        'unique_name' => (!empty($follower_val['unique_name']) ? $follower_val['unique_name'] : ''),
                        'email' => (!empty($follower_val['email']) ? $follower_val['email'] : ''),
                        'phone' => (!empty($follower_val['phone']) ? $follower_val['phone'] : ''),
                        'profile_pic' => (!empty($follower_val['profile_pic']) ? $base_url . $follower_val['profile_pic'] : '')
                    );
                }
            }
        }
        
        $this->db->where('id != ', $user_id);
        $users = $this->db->get('ws_users')->result_array();
        $USER_DATA = array();
        if ($users) {
            foreach ($users as $users_list) {
                if (!in_array($users_list['id'], $GROUP_DATA)) {
                    $user_val = $this->common_model->findWhere($table = 'ws_users', array('id' => $users_list['id']), $multi_record = false, $order = '');
                    $USER_DATA[] = array(
                        'id' => $users_list['id'],
                        'fullname' => (!empty($user_val['fullname']) ? $user_val['fullname'] : ''),
                        'unique_name' => (!empty($user_val['unique_name']) ? $user_val['unique_name'] : ''),
                        'email' => (!empty($user_val['email']) ? $user_val['email'] : ''),
                        'phone' => (!empty($user_val['phone']) ? $user_val['phone'] : ''),
                        'profile_pic' => (!empty($user_val['profile_pic']) ? $base_url . $user_val['profile_pic'] : '')
                    );
                }
            }
        }
        $data = array(
            'status' => 1,
            'Follower' => $FOLLOWER_DATA,
            'App User' => $USER_DATA
        );
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : update_sound_notification_post                                              *
     * Functionality : update sound notification                                                   *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id                                                              *
     * @param        : int    notification_type                                                    *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    //update sound notification 
    public function update_sound_notification_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $sound_setting_data = $this->common_model->findWhere($table = 'ws_sound_notification', array('user_id' => $user_id), $multi_record = false, $order = '');

        if ($sound_setting_data) {
            $notification_type = $this->input->post('notification_type');
            $this->check_empty($notification_type, 'Please add notification_type');

            $post_all = 1;
            $post_friend = 1;
            $like_all = 1;
            $like_friend = 1;
            $comment_all = 1;
            $comment_friend = 1;
            $tag_all = 1;
            $tag_friend = 1;
            $all_sound_all = 1;
            $all_sound_friend = 1;

            $notification_array = explode(",", $notification_type);

            foreach ($notification_array as $notification_type_val) {
                if ($notification_type_val == 'post_all') {
                    $post_all = 0;
                    $post_friend = 0;
                } elseif ($notification_type_val == 'post_friend') {
                    $post_friend = 0;
                } elseif ($notification_type_val == 'like_all') {
                    $like_all = 0;
                    $like_friend = 0;
                } elseif ($notification_type_val == 'like_friend') {
                    $like_friend = 0;
                } elseif ($notification_type_val == 'comment_all') {
                    $comment_all = 0;
                    $comment_friend = 0;
                } elseif ($notification_type_val == 'comment_friend') {
                    $comment_friend = 0;
                } elseif ($notification_type_val == 'tag_all') {
                    $tag_all = 0;
                    $tag_friend = 0;
                } elseif ($notification_type_val == 'tag_friend') {
                    $tag_friend = 0;
                } elseif ($notification_type_val == 'all_sound_all') {
                    $all_sound_all = 0;
                    $post_all = 0;
                    $like_all = 0;
                    $comment_all = 0;
                    $tag_all = 0;
                    $all_sound_friend = 0;
                    $post_friend = 0;
                    $like_friend = 0;
                    $comment_friend = 0;
                    $tag_friend = 0;
                } elseif ($notification_type_val == 'all_sound_friend') {
                    $all_sound_friend = 0;
                    $post_friend = 0;
                    $like_friend = 0;
                    $comment_friend = 0;
                    $tag_friend = 0;
                }
            }
            $post_data = array(
                'user_id' => $user_id,
                'post_all' => $post_all,
                'post_friend' => $post_friend,
                'like_all' => $like_all,
                'like_friend' => $like_friend,
                'comment_all' => $comment_all,
                'comment_friend' => $comment_friend,
                'tag_all' => $tag_all,
                'tag_friend' => $tag_friend,
                'all_sound_all' => $all_sound_all,
                'all_sound_friend' => $all_sound_friend
            );
            if ($this->common_model->updateWhere($table = 'ws_sound_notification', $where_data = array('user_id' => $user_id), $post_data)) {
                $data = array(
                    'status' => 1,
                    'message' => 'Success'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'Error'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'sound setting of user_id does not exist'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : delete_post_post                                                            *
     * Functionality : delete_post_post                                                            *    
     * @author       : pratibha sinha                                                              *
     * @param        : int   user_id                                                               *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function delete_post_post() {
        $relative_path = '/var/www/html/bestest_test/';

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please enter post_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $post_exist = $this->common_model->findWhere($table = 'ws_posts', $where_data = array('post_id' => $post_id, 'user_id' => $user_id), $multi_record = false, $order = '');

        if (!empty($post_exist)) {
            //delete posts
            $this->common_model->delete($table = 'ws_posts', $where_data = array('post_id' => $post_id));

            //delete video from server
            $videos_detail = $this->common_model->findWhere($table = 'ws_videos', $where_data = array('post_id' => $post_id), $multi_record = false, $order = '');
            foreach ($videos_detail as $videos) {
                $video_path = $relative_path . $videos['video_name'];
                //delete image files
                unlink($video_path);

                //delete likes
                $this->common_model->delete($table = 'ws_video_likes', $where_data = array('video_id' => $videos['video_id']));

                //delete unlikes
                $this->common_model->delete($table = 'ws_video_unlikes', $where_data = array('video_id' => $videos['video_id']));
            }
            //delete videos
            $this->common_model->delete($table = 'ws_videos', $where_data = array('post_id' => $post_id));

            $images_detail = $this->db->get_where('ws_images', array('post_id' => $post_id))->result_array();

            //delete image files from server,likes,dislikes
            foreach ($images_detail as $images) {
                $image_path = $relative_path . 'uploads/post_images/' . $images['image_name'];
                //delete image files
                unlink($image_path);

                //delete likes
                $this->common_model->delete($table = 'ws_likes', $where_data = array('image_id' => $images['image_id']));

                //delete unlikes
                $this->common_model->delete($table = 'ws_unlikes', $where_data = array('image_id' => $images['image_id']));
            }
            //delete images
            $this->common_model->delete($table = 'ws_images', $where_data = array('post_id' => $post_id));


            //delete text
            $text_detail = $this->db->get_where('ws_text', array('post_id' => $post_id))->result_array();

            //delete text files from server,likes,dislikes
            foreach ($text_detail as $texts) {
                //delete likes
                $this->common_model->delete($table = 'ws_text_likes', $where_data = array('text_id' => $texts['id']));

                //delete unlikes
                $this->common_model->delete($table = 'ws_text_unlikes', $where_data = array('text_id' => $texts['id']));
            }
            //delete text
            $this->common_model->delete($table = 'ws_text', $where_data = array('post_id' => $post_id));

            //delete tags
            $this->common_model->delete($table = 'ws_tags', $where_data = array('post_id' => $post_id));

            //delete comments
            $this->common_model->delete($table = 'ws_comments', $where_data = array('post_id' => $post_id));

            //delete notifications
            $this->common_model->delete($table = 'ws_notifications', $where_data = array('post_id' => $post_id));

            $data = array(
                'status' => 1,
                'message' => 'success'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Either post_id or user_id is wrong'
            );
        }
        $this->response($data, 200);
    }

    public function delete_posttest_post() {
        $relative_path = '/var/www/html/bestest_test/';

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please enter post_id');

        //$user_id = $this->input->post('user_id');
        //$this->check_empty($user_id, 'Please enter user_id');

        $post_exist = $this->common_model->findWhere($table = 'ws_posts', $where_data = array('post_id' => $post_id), $multi_record = false, $order = '');

        if (!empty($post_exist)) {
            //delete posts
            $this->common_model->delete($table = 'ws_posts', $where_data = array('post_id' => $post_id));

            //delete video from server
            $videos_detail = $this->common_model->findWhere($table = 'ws_videos', $where_data = array('post_id' => $post_id), $multi_record = false, $order = '');
            foreach ($videos_detail as $videos) {
                $video_path = $relative_path . $videos['video_name'];
                //delete image files
                unlink($video_path);

                //delete likes
                $this->common_model->delete($table = 'ws_video_likes', $where_data = array('video_id' => $videos['video_id']));

                //delete unlikes
                $this->common_model->delete($table = 'ws_video_unlikes', $where_data = array('video_id' => $videos['video_id']));
            }
            //delete videos
            $this->common_model->delete($table = 'ws_videos', $where_data = array('post_id' => $post_id));

            $images_detail = $this->db->get_where('ws_images', array('post_id' => $post_id))->result_array();

            //delete image files from server,likes,dislikes
            foreach ($images_detail as $images) {
                $image_path = $relative_path . 'uploads/post_images/' . $images['image_name'];
                //delete image files
                unlink($image_path);

                //delete likes
                $this->common_model->delete($table = 'ws_likes', $where_data = array('image_id' => $images['image_id']));

                //delete unlikes
                $this->common_model->delete($table = 'ws_unlikes', $where_data = array('image_id' => $images['image_id']));
            }
            //delete images
            $this->common_model->delete($table = 'ws_images', $where_data = array('post_id' => $post_id));


            //delete text
            $text_detail = $this->db->get_where('ws_text', array('post_id' => $post_id))->result_array();

            //delete text files from server,likes,dislikes
            foreach ($text_detail as $texts) {
                //delete likes
                $this->common_model->delete($table = 'ws_text_likes', $where_data = array('text_id' => $texts['id']));

                //delete unlikes
                $this->common_model->delete($table = 'ws_text_unlikes', $where_data = array('text_id' => $texts['id']));
            }
            //delete text
            $this->common_model->delete($table = 'ws_text', $where_data = array('post_id' => $post_id));

            //delete tags
            $this->common_model->delete($table = 'ws_tags', $where_data = array('post_id' => $post_id));

            //delete comments
            $this->common_model->delete($table = 'ws_comments', $where_data = array('post_id' => $post_id));

            //delete notifications
            $this->common_model->delete($table = 'ws_notifications', $where_data = array('post_id' => $post_id));

            $data = array(
                'status' => 1,
                'message' => 'success'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Either post_id or user_id is wrong'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : get_sound_notification_post                                                 *
     * Functionality : get_sound_notification_post                                                 *    
     * @author       : pratibha sinha                                                              *
     * @param        : int   user_id                                                    *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function get_sound_notification_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $sound_data = $this->common_model->findWhere($table = 'ws_sound_notification', array('user_id' => $user_id), $multi_record = false, $order = '');
        if (!empty($sound_data)) {
            $data = array(
                'status' => 1,
                'data' => $sound_data
            );
        } else {
            $sound_data = json_decode('{}');
            $data = array(
                'status' => 0,
                'data' => $sound_data
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : notification_type                                                           *
     * Functionality : get type                                                                    *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    notification_type                                                    *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function notification_type($notification_type) {
        switch ($notification_type) {
            case 'post':
                $type = 'post';
                break;
            case 'tag':
                $type = 'tag';
                break;
            case 'comment':
                $type = 'comment';
                break;
            case 'group':
                $type = 'group';
                break;    
            case 'simple_chat':
                $type = 'simple_chat';
                break;
            case 'group_chat':
                $type = 'group_chat';
                break;
            case 'like':
                $type = 'like';
                break;
            case 'unlike':
                $type = 'unlike';
                break;
            case 'follow':
                $type = 'follow';
                break;
            case 'imgtaguser':
                $type = 'imgtaguser';
                break;
            case 'mentioned_comment':
                $type = 'mentioned_comment';
                break;
            case 'reveal':
                $type = 'reveal';
                break;    
            default:
                $type = 'all';
                break;
        }
        return $type;
    }

    /**
     * **********************************************************************************************
     * Function Name : notification_alert_msg                                                      *
     * Functionality : get notification message                                                    *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    notification_type , sender                                           *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function notification_alert_msg($notification_type, $sender, $POST_ID = '',$groupALL = '' , $receiver) {
        //echo 'qq'.$groupALL;die;
        if (!empty($POST_ID)) {
            $where_data = array('post_id' => $POST_ID, 'status' => 1);
            $post_detail = $this->common_model->findWhere($table = 'ws_posts', $where_data, $multi_record = false, $order = '');
            
            if($post_detail['type'] == 'image' || $post_detail['type'] == 'video')
            {
                $post_question = $post_detail['question'];
                $count_question = str_word_count($post_question);
                if($count_question > 5)
                {
                    $pieces_question = explode(" ", $post_question);
                    $val_question = implode(" ", array_splice($pieces_question, 0, 5));
                    $extract_question = $val_question.' ...';
                }
                else
                {
                    $extract_question = $post_question;
                }
                $title_val =  "'".$extract_question."'";
            }
            else
            {
                $post_title = $post_detail['title'];
                $count_title = str_word_count($post_title);
                if($count_title >= 5)
                {
                    $pieces_title = explode(" ", $post_title);
                    $val_title = implode(" ", array_splice($pieces_title, 0, 5));
                    $extract_title = $val_title.' ...';
                }
                else
                {
                    $extract_title = $post_title;
                }
                $title_val =  "'".$extract_title."'";
            }    
        }
         //group detail
         if ($groupALL != '') {
            $query = "SELECT GROUP_CONCAT(group_name SEPARATOR ', ') as group_name from ws_groups where id in ($groupALL)";
            $result = $this->db->query($query)->row_array();
            $group_name = $result['group_name'];
         }

        switch ($notification_type) {
            case 'post':
                $message = $sender . ' added a poll ' . $title_val;
                break;
            case 'tag':
                $message = $sender . ' tagged you in his poll ' . $title_val;
                break;
            case 'comment':
                $message = $sender . ' commented on your poll ' . $title_val;
                break;
            case 'group':
                $message = $sender . ' added a poll ' . $title_val . 'on '. $group_name;
                break;    
            case 'simple_chat':
                $message = 'You have a message from ' . $sender;
                break;
            case 'group_chat':
                $message = 'You have one group chat from ' . $sender;
                break;
            case 'like':
            case 'unlike':
                if($post_detail['poll_type'] == 'private'){
                    $message = 'Someone voted on your poll ' . $title_val;
                }else{
                    $message = $sender . ' voted on your poll ' . $title_val;
                }
                break;
            case 'follow':
                $message = $sender . ' is following you';
                break;
            case 'imgtaguser':
                $message = $sender . ' tagged you in an image';
                break;
            case 'mentioned_comment':
                $message = $sender . ' mentioned you in a comment';
                break;
            case 'reveal':
                $message = $sender . ' has revealed the poll results. Check now.';
                break;    
            default:
                $message = '';
                break;
        }
        return $message;
    }

    public function like_comment_msg_post()
    {
        $type = 'like';
        $POST_ID = 1265;
        $receiver = 28;
        $result = $this->db->order_by('id','DESC')->get_where('ws_notifications' , array('receiver_id' => $receiver,'notification_type' => $type , 'post_id' => $POST_ID))->result_array();
        $count = count($result);
        
        if($count == 1)
        {
            $sender1 = $result[0]['sender_id'];
            $sender_details = $this->db->get_where('ws_users' , array('id' => $sender1))->row_array();
            $sender1_name = $sender_details['fullname'];
            $sender = $sender1_name;
        }
        elseif($count == 2)
        {
            $sender1 = $result[0]['sender_id'];
            $sender1_details = $this->db->get_where('ws_users' , array('id' => $sender1))->row_array();
            $sender1_name = $sender1_details['fullname'];

            $sender2 = $result[1]['sender_id'];
            $sender2_details = $this->db->get_where('ws_users' , array('id' => $sender2))->row_array();
            $sender2_name = $sender2_details['fullname'];

            $sender = $sender1_name.' and '.$sender2_name;
        }
        else
        {
            $sender1 = $result[0]['sender_id'];
            $sender1_details = $this->db->get_where('ws_users' , array('id' => $sender1))->row_array();
            $sender1_name = $sender1_details['fullname'];

            $sender2 = $result[1]['sender_id'];
            $sender2_details = $this->db->get_where('ws_users' , array('id' => $sender2))->row_array();
            $sender2_name = $sender2_details['fullname'];

            $remaining = $count - 2;
            $other_val = ($remaining > 1) ? 'others' : 'other';    
            $sender = $sender1_name.','.$sender2_name.' and '.$remaining.' '.$other_val.' ';
        }
        echo $sender;  
    }

    /**
     * **********************************************************************************************
     * Function Name : sound_status_check                                                          *
     * Functionality : check sound status of receiver                                              *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    receiver , type ,is_friend                                           *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function sound_status_check($receiver, $type, $is_friend) {
        if ($is_friend == 'friend') {
            if ($type == "post") {
                $recent_col_name = "post_friend";
            }
            if ($type == "like") {
                $recent_col_name = "like_friend";
            }
            if ($type == "tag") {
                $recent_col_name = "tag_friend";
            }
            if ($type == "comment") {
                $recent_col_name = "comment_friend";
            }
            if ($type == "all") {
                $recent_col_name = "all_sound_friend";
            }
            $query = "select $recent_col_name from ws_sound_notification where user_id = '$receiver' limit 1";
            $receiver_status = $this->common_model->getQuery($query);

            if ($receiver_status) {
                return $receiver_status[0][$recent_col_name];
            }
        } else {
            if ($type == "post") {
                $recent_col_name = "post_all";
            }
            if ($type == "like") {
                $recent_col_name = "like_all";
            }
            if ($type == "tag") {
                $recent_col_name = "tag_all";
            }
            if ($type == "comment") {
                $recent_col_name = "comment_all";
            }
            if ($type == "all") {
                $recent_col_name = "all_sound_all";
            }

            $query = "select $recent_col_name from ws_sound_notification where user_id = '$receiver' limit 1";
            $receiver_status = $this->common_model->getQuery($query);
            if ($receiver_status) {
                return $receiver_status[0][$recent_col_name];
            }
        }
    }

    public function notification_friend_setting_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $post = $this->input->post('post');
        $tag = $this->input->post('tag');
        $comment = $this->input->post('comment');

        $notification_exist_data = $this->common_model->findWhere($table = 'ws_notification_friend_setting', array('user_id' => $user_id), $multi_record = false, $order = '');

        if (empty($notification_exist_data)) {
            if ($post != '') {
                $post_val = $post;
            } else {
                $post_val = '';
            }

            if ($tag != '') {
                $tag_val = $tag;
            } else {
                $tag_val = '';
            }

            if ($comment != '') {
                $comment_val = $comment;
            } else {
                $comment_val = '';
            }

            $user_exist_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');
            if ($user_exist_data) {
                $post_data = array(
                    'user_id' => $user_id,
                    'post' => $post_val,
                    'tag' => $tag_val,
                    'comment' => $comment_val,
                );
                $this->common_model->add('ws_notification_friend_setting', $post_data);

                $data = array(
                    'status' => 1,
                    'message' => 'sucess'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'user_id does not exist'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'already set notification'
            );
        }
        $this->response($data, 200);
    }

    public function update_notification_friend_setting_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $post = $this->input->post('post');
        $tag = $this->input->post('tag');
        $comment = $this->input->post('comment');

        $notification_exist_data = $this->common_model->findWhere($table = 'ws_notification_friend_setting', array('user_id' => $user_id), $multi_record = false, $order = '');

        if ($post != '') {
            $post_val = $post;
        } else {
            $post_val = $notification_exist_data['post'];
        }

        if ($tag != '') {
            $tag_val = $tag;
        } else {
            $tag_val = $notification_exist_data['tag'];
        }

        if ($comment != '') {
            $comment_val = $comment;
        } else {
            $comment_val = $notification_exist_data['comment'];
        }
        
        if ($notification_exist_data) {
            $post_data = array(
                'user_id' => $user_id,
                'post' => $post_val,
                'tag' => $tag_val,
                'comment' => $comment_val,
            );
            
            if ($this->common_model->updateWhere($table = 'ws_notification_friend_setting', $where_data = array('user_id' => $user_id), $post_data)) {
                $data = array(
                    'status' => 1,
                    'message' => 'sucess'
                );
            } else {
                $data = array(
                    'status' => 1,
                    'message' => 'Failed'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'user_id does not exist'
            );
        }
        $this->response($data, 200);
    }

    public function check_notification_setting($user_id, $type) {
        
        $notification_exist_data = $this->common_model->findWhere($table = 'ws_notification_friend_setting', array('user_id' => $user_id), $multi_record = false, $order = '');
        if ($notification_exist_data[$type] == 1) {
            //echo 'yes';die;
            return '1';
        } else {
            //echo 'no';die;
            return '0';
        }
    }

    public function test_setting_post() {
        $val = $this->check_notification_set(28, 'vote');
        var_dump($val);
    }

    /**
     * **********************************************************************************************
     * Function Name : select_notification_frnds_post                                              *
     * Functionality : select friends for notification                                             *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id , friend_id ,type                                            *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    //notification frnds select and delete combine new

    public function select_notification_frnds_post() {

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $friend_id = $this->input->post('friend_id');
        
        $type = $this->input->post('type');
        $this->check_empty($type, 'Please add type');

        $member_array = explode(",", $friend_id);

        // delete old selected frnds

        $delete_post_data = array(
            'type' => $type,
            'user_id' => $user_id,
            'type' => $type
        );

        $this->common_model->delete($table = 'ws_notification_frnds', $delete_post_data);

        if ($friend_id != '' && !empty($member_array)) {
            //echo '1';die;
            foreach ($member_array as $id) {
                $id_val = (int) $id;

                if ($user_id != $id_val) {
                    $post_data = array(
                        'type' => $type,
                        'user_id' => $user_id,
                        'friend_id' => $id_val,
                        'added_at' => date('Y-m-d h:i')
                    );
                    $notification_member = $this->common_model->add('ws_notification_frnds', $post_data);
                    if ($notification_member) {
                        $data = array(
                            'status' => 1,
                            'message' => 'Success'
                        );
                    } else {
                        $data = array(
                            'status' => 0,
                            'message' => 'Unable to select friend'
                        );
                    }
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'user_id and friend_id cannot be same'
                    );
                }
            }
        } else {
            $data = array(
                'status' => 1,
                'message' => 'Success'
            );
        }
        $this->response($data, 200);
    }

    public function report_abuse_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $reason = $this->input->post('reason');
        //$this->check_empty($reason, 'Please add reason');

        $post_detail = $this->common_model->findWhere($table = 'ws_posts', array('post_id' => $post_id, 'status' => 1), $multi_record = false, $order = '');
        $post_creator_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $post_detail['user_id'], 'activated' => 1), $multi_record = false, $order = '');
        $user_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');

        $post_data = array(
            'user_id' => $user_id,
            'post_id' => $post_id,
            'post_creator' => $post_creator_detail['fullname'],
            'reason' => (!empty($reason) ? $reason : ''),
            'added_at' => date('Y-m-d h:i')
        );
        $report_id = $this->common_model->add('ws_report_abuse', $post_data);
        if ($report_id) {
            $admin = 'contact@mybestestapp.com';
            $config['useragent'] = "CodeIgniter";
            $config['mailpath'] = "/usr/bin/sendmail"; // or "/usr/sbin/sendmail"
            $config['protocol'] = "smtp";
            $config['smtp_host'] = "localhost";
            $config['smtp_port'] = "25";
            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            $config['newline'] = "\r\n";
            $config['wordwrap'] = TRUE;

            $this->load->library('email');

            $this->email->initialize($config);

            $this->email->from($user_detail['email'], $user_detail['fullname']);
            $this->email->to($admin);
            $this->email->subject('Bestest:Report Abuse');

            $message = 'Dear Admin' . ',<br><br>
                           Following post has been reported by ' . $user_detail['fullname'] . '<br><br>' .
                    'POST TITLE: ' . $post_detail['question'] . '<br><br>' .
                    'POST CREATOR: ' . $post_creator_detail['fullname'] . '<br><br>' .
                    'ADDED AT: ' . $post_detail['added_at'];

            $this->email->message($message);

            /* if the mail is sent */
            if ($this->email->send()) {

               $data = array(
                    'status' => 1,
                    'report_id' => $report_id,
                    'message' => 'Success'
                );
                /* if the email is not sent */
            } else {
                //echo $this->email->print_debugger();die;
                $data = array(
                    'status' => 0,
                    'message' => 'Email not sent'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Failed'
            );
        }
        $this->response($data, 200);
    }

    /**
     * **********************************************************************************************
     * Function Name : get_notification_frnds_post                                                 *
     * Functionality : list selected notification friends                                          *    
     * @author       : pratibha sinha                                                              *
     * @param        : int    user_id , type                                                       *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function get_notification_frnds_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $type = $this->input->post('type');
        $this->check_empty($type, 'Please add type');

        $notification_frnds_query = "select * from ws_notification_frnds where (user_id = '$user_id' AND type = '$type')";
        $notification_data = $this->common_model->getQuery($notification_frnds_query);

        if ($notification_data) {
            $data = array(
                'status' => 1,
                'data' => $notification_data
            );
        } else {
            $notification_data = array();
            $data = array(
                'status' => 0,
                'data' => $notification_data
            );
        }
        $this->response($data, 200);
    }

    //notification frnds select old

    public function notification_selected_frnds_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');

        $type = $this->input->post('type');
        $this->check_empty($type, 'Please add type');

        $member_array = explode(",", $friend_id);
        //echo '<pre>';var_dump($member_array);

        if ($member_array) {
            foreach ($member_array as $id) {
                $id_val = (int) $id;

                if ($user_id != $id_val) {
                    $member_check = "select * from ws_notification_frnds where (type = '$type' AND friend_id = '$id_val' AND user_id = '$user_id') ";
                    $frnd_data = $this->common_model->getQuery($member_check);
                    if (empty($frnd_data)) {
                        $post_data = array(
                            'type' => $type,
                            'user_id' => $user_id,
                            'friend_id' => $id_val,
                            'added_at' => date('Y-m-d h:i')
                        );
                        $notification_member = $this->common_model->add('ws_notification_frnds', $post_data);
                        if ($notification_member) {
                            $data = array(
                                'status' => 1,
                                'message' => 'Success'
                            );
                        } else {
                            $data = array(
                                'status' => 0,
                                'message' => 'Unable to select friend'
                            );
                        }
                    } else {
                        $data = array(
                            'status' => 0,
                            'message' => 'Already selected'
                        );
                    }
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'user_id and friend_id cannot be same'
                    );
                }
            }
        }
        $this->response($data, 200);
    }

    //delete notification frnds select old
    public function delete_notification_selected_frnds_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');

        $type = $this->input->post('type');
        $this->check_empty($type, 'Please add type');

        $member_array = explode(",", $friend_id);
        
        if ($member_array) {
            foreach ($member_array as $id) {
                $id_val = (int) $id;

                if ($user_id != $id_val) {
                    //var_dump($id_val);die;
                    $member_check = "select * from ws_notification_frnds where (type = '$type' AND friend_id = '$id_val' AND user_id = '$user_id') ";
                    $frnd_data = $this->common_model->getQuery($member_check);
                    if (!empty($frnd_data)) {
                        $post_data = array(
                            'type' => $type,
                            'user_id' => $user_id,
                            'friend_id' => $id_val
                        );

                        if ($this->common_model->delete($table = 'ws_notification_frnds', $post_data)) {
                            //if ($notification_member) {
                            $data = array(
                                'status' => 1,
                                'message' => 'Success'
                            );
                        } else {
                            $data = array(
                                'status' => 0,
                                'message' => 'Unable to delete friend'
                            );
                        }
                    } else {
                        $data = array(
                            'status' => 0,
                            'message' => 'not selected'
                        );
                    }
                } else {
                    $data = array(
                        'status' => 0,
                        'message' => 'user_id and friend_id cannot be same'
                    );
                }
            }
        }
        $this->response($data, 200);
    }

    function date_compare($a, $b) {
        $t1 = strtotime($a['added_at']);
        $t2 = strtotime($b['added_at']);
        return $t2 - $t1;
    }

    public function get_all_posts_post() {

        $limit = 10;

        $offset = $this->input->post('offset');
        $this->check_integer_empty($offset, 'Please add offset');

        $base_url = $this->baseurl;
        //$post_base_url = $this->baseurl . 'uploads/post_images/';
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');

        $datetime1 = date('Y-m-d H:i:s', time());
        //$datetime1 = $this->input->post('datetime1');
        //$this->check_empty($datetime1, 'Please add datetime1');

        $deviceType = $this->input->post('deviceType');
        //$this->check_empty($deviceType, 'Please add deviceType');

        //check on last updated

        $userChk = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');

        $last_used = array(
                        'last_used_app' => date('Y-m-d h:i') ,
                        'deviceType' => (!empty($deviceType) ? $deviceType : $userChk['deviceType'])
                          );
        $this->common_model->updateWhere('ws_users', $where_data = array('id' => $user_id), $last_used);

        //notification
        $notification = $this->db->order_by('added_at', 'desc')->get_where('ws_notifications', array('receiver_id' => $user_id, 'status' => 0))->result_array();
        $notification_count = count($notification);

        //my post start
        $result = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $user_id, 'status' => '1'))->result_array();
        //my post end
        //friends post start
        $this->db->select('friend_id');
        $friends1 = $this->db->get_where('ws_friend_list', array('user_id' => $user_id, 'status' => '1'))->result_array();
        $this->db->select('user_id as friend_id');
        $friends2 = $this->db->get_where('ws_friend_list', array('friend_id' => $user_id, 'status' => '1'))->result_array();
        $friends2 = array_values($friends2);

        $friend = array_merge($friends1, $friends2);
        $friendDetail = array();
        foreach ($friend as $fr) {
            if (count($this->db->get_where('ws_posts', array('user_id' => $fr['friend_id'], 'status' => 1))->result_array()) > 0) {
                $friends_posts[] = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $fr['friend_id'], 'status' => 1))->result_array();

                foreach ($friends_posts as $key => $value) {
                    foreach ($value as $inner_value) {
                        array_push($friendDetail, $inner_value);
                    }
                }
            }
        }
        //friends post end 
        //celeb post start
        $celeb_ids = $this->db->get_where('ws_followers_celebrity', array('user_id' => $user_id))->result_array();
        $celebDetail = array();
        $celeb_data = array();
        foreach ($celeb_ids as $fr) {
            if (count($this->db->get_where('ws_posts', array('user_id' => $fr['celebrity_id'], 'status' => 1))->result_array()) > 0) {
                $celeb_data[] = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $fr['celebrity_id'], 'status' => 1))->result_array();
                foreach ($celeb_data as $key => $value) {
                    foreach ($value as $inner_value) {
                        array_push($celebDetail, $inner_value);
                    }
                }
            }
        }
        //celeb post end
        //group post start
        $group_ids = $this->db->get_where('ws_group_members', array('member_id' => $user_id))->result_array();
        $gp_array = array();
        foreach ($group_ids as $gp_ids) {
            array_push($gp_array, $gp_ids['group_id']);
        }

        $GROUPDETAIL = array();
        $group_data = array();
        if (!empty($gp_array)) {
            foreach ($gp_array as $gp) {
                //$sql[] = 'group_id LIKE "' . $gp . '" ';
                    if (count($this->db->query('SELECT * FROM ws_posts WHERE FIND_IN_SET('.$gp.',group_id) > 0  AND status = 1 ORDER BY `post_id` DESC')->result_array()) > 0) {
                    $group_data[] = $this->db->query('SELECT * FROM ws_posts WHERE FIND_IN_SET('.$gp.',group_id) > 0 AND status = 1 ORDER BY `post_id` DESC')->result_array();
                    //echo $this->db->last_query();die;
                    //echo '<pre>';print_r($group_data);die;
                    foreach ($group_data as $key => $value) {
                        foreach ($value as $inner_value) {
                            array_push($GROUPDETAIL, $inner_value);
                        }
                    }
                }
            }
        }

        //group post end
        //follower post start
        $follower_ids = $this->db->get_where('ws_follow', array('user_id' => $user_id))->result_array();
        $FOLLOWERDETAIL = array();
        $follower_data = array();
        foreach ($follower_ids as $fl) {
            if (count($this->db->get_where('ws_posts', array('user_id' => $fl['friend_id'], 'status' => 1))->result_array()) > 0) {
                
                $follower_data[] = $this->db->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $fl['friend_id'], 'status' => 1))->result_array();
                foreach ($follower_data as $key => $value) {
                    foreach ($value as $inner_value) {
                        array_push($FOLLOWERDETAIL, $inner_value);
                    }
                }
            }
        }
        
        $final_follower = array();
        foreach ($FOLLOWERDETAIL as $inner_value) {
            if ($inner_value['share_with'] == 'friend' && in_array($user_id, explode(",", $inner_value['friend_id']))) {
                //echo 'qqqq';die;
                $final_follower['friend'][] = $inner_value;
            }
            if ($inner_value['share_with'] == 'public') {
                //echo 'qqqq';die;
                $final_follower['remainnig'][] = $inner_value;
            }
        }

        $FOLLOWERchkDETAIL = array();
        foreach ($final_follower as $key => $value) {
            foreach ($value as $inner_value) {
                array_push($FOLLOWERchkDETAIL, $inner_value);
            }
        }
        //group post end

        $combine_result_friend = array_merge((array) $result, (array) $friendDetail);
        $combine_celeb_group = array_merge((array) $celebDetail, (array) $GROUPDETAIL);
        $combine_celeb_group_follower = array_merge((array) $combine_celeb_group, (array) $FOLLOWERchkDETAIL);
        $combine_array = array_unique(array_merge($combine_result_friend, $combine_celeb_group_follower), SORT_REGULAR);
        usort($combine_array, array($this, 'date_compare'));

        $offset = (int)$offset;
        $combine_array_result = array_slice($combine_array , $offset, $limit);
        
        if (empty($combine_array_result)) {
            $combine_array_result = array();
        } else {
            /* provide the number of likes , last comment and images for a post */
            
            foreach ($combine_array_result as &$post) {
                $postID = $post['post_id'];
                $poll_link = $base_url.'web/?id='.$postID;
                $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
                
                $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
                $commentCount = $this->common_model->getQuery($comment_post);

                //echo '<pre>htgtyh';print_r($commentCount);
                $post['comment_count'] = $commentCount[0]['count'];

                //post creator
                $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                $post['post_creator'] = $post_sender['fullname'];
                $post['creator_pic'] = (!empty($post_sender['profile_pic']) ) ? $base_url . $post_sender['profile_pic'] : '';
                $post['creator_unique_name'] = (!empty($post_sender['unique_name']) ) ? $post_sender['unique_name'] : '';
                $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);
                
                //group_name 
                $sharegroup_name = array();
                if ($post['group_id'] != 0) {
                    foreach (explode(',', $post['group_id']) as $key => $value) {
                        $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                        $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                    }
                } else {
                    $sharegroup_name = '';
                }
                $post['group_name'] = $sharegroup_name;
                //frnd name
                $sharefriend_name = array();
                if ($post['friend_id'] != 0) {
                    foreach (explode(',', $post['friend_id']) as $key => $value) {
                        $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value, 'activated' => 1), $multi_record = false, $order = '');
                        $sharefriend_name[] = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                    }
                } else {
                    $sharefriend_name = '';
                }
                $post['friend_name'] = $sharefriend_name;

                //text data

                $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                $totalPostTextLikes =0;
                foreach($text as $tx)
                {
                    $totalPostTextLikes +=  $tx['likes'];
                }
                $total_textprop = 0;
                $loop_textindex = 0;
                if (count($text) > 0) {
                    $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();

                    //check like of user id
                    //echo '<pre>';print_r($post['images']);die;
                    foreach ($post['text'] as &$text_likes) {
                        $loop_textindex++;
                        $likes_count = (int) $text_likes['likes'];

                        $unlikes_count = (int) $text_likes['unlikes'];
                        $text_id = (int) $text_likes['id'];
                        
                        
                        //likes status
                        if ($likes_count > 0) {

                            $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                            if(count($text) == $loop_textindex && $loop_textindex > 1){
                            $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                            }else{
                            $prop = round ( $likesproportion);
                            $total_textprop += $prop;
                            $text_likes['likes_proportion'] = $prop.'%';
                            }
                            
                            $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                            $text_likes['like_status'] = $like_status;
                            
                            //likes array

                            $this->db->select('l.user_id,u.fullname,u.profile_pic');
                            $this->db->from('ws_text_likes l');
                            $this->db->join('ws_users u', 'l.user_id = u.id');
                            $this->db->where('l.text_id', $text_id);

                            $text_likes['likes_detail'] = $this->db->get()->result_array();

                        } else {
                            $text_likes['likes_detail'] = array();
                            $text_likes['like_status'] = 'not liked';
                            $text_likes['likes_proportion'] = '0%';
                        }

                        //unlikes array
                        if ($unlikes_count > 0) {
                            $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                            $text_likes['unlike_status'] = $unlike_status;

                            //unlikes array

                            $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                            $this->db->from('ws_text_unlikes ul');
                            $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                            $this->db->where('ul.text_id', $text_id);

                            $text_likes['unlikes_detail'] = $this->db->get()->result_array();
                        } else {
                            $text_likes['unlikes_detail'] = array();
                            $text_likes['unlike_status'] = 'not disliked';
                        }
                    }
                } else {
                    $post['text'] = array();
                }

                //images data

                $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                $totalPostImageLikes =0;
                $totalPostImageUnlikes =0;

                foreach($img as $im)
                {
                    $totalPostImageLikes +=  $im['likes'];
                    $totalPostImageUnlikes +=  $im['unlikes'];
                }
                $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;

                /*Custom function to repair like proportion*/
							foreach($img as $key=>$images_likes)
							{
								$likes_count = (int) $images_likes['likes'];
								if ($likes_count > 0) {
									$per_votes[$key]['image_id'] = $images_likes['image_id'];
									$per_votes[$key]['likes_proportion'] = (int)round(( $likes_count / $totalPostImageVoteCount ) * 100);
									$total_per_votes += $per_votes[$key]['likes_proportion'];
								}
							}
				print_r($img);
				print_r($per_votes);

                $total_imgprop = 0;
                $loop_imgindex = 0;
                if (count($img) > 0) {
                    $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                    
                    //check like of user id
                    foreach ($post['images'] as &$images_likes) {
                        $loop_imgindex++;
                        $likes_count = (int) $images_likes['likes'];
                        $unlikes_count = (int) $images_likes['unlikes'];
                        $img_id = (int) $images_likes['image_id'];


                        //likes status
                        if ($likes_count > 0) {

	                        // Handle percentage here
	                        /*$per_votes['image_id'] = $img_id;
	                        $per_votes['likes_proportion'] = (int)round(( $likes_count / $totalPostImageVoteCount ) * 100);
	                        $total_per_votes += $per_votes['likes_proportion'];*/
	                        // print_r($per_votes);

                            $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                            if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                            $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                            }else{
                            $prop = round ( $likesproportion);
                            $total_imgprop += $prop;
                            $images_likes['likes_proportion'] = $prop.'%';
                            }
                            
                            $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                            $images_likes['like_status'] = $like_status;
                            

                            //likes array

                            $this->db->select('l.user_id,u.fullname,u.profile_pic');
                            $this->db->from('ws_likes l');
                            $this->db->join('ws_users u', 'l.user_id = u.id');
                            $this->db->where('l.image_id', $img_id);

                            $images_likes['likes_detail'] = $this->db->get()->result_array();

                            

                        } else {
                            $images_likes['likes_detail'] = array();
                            $images_likes['like_status'] = 'not liked';
                            $images_likes['likes_proportion'] = '0%';
                        }

                        //unlikes array

                        $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                        if(count($img) == 1){
                            $prop = round ( $unlikesproportion);
                            $total_imgprop += $prop;
                            $images_likes['unlikes_proportion'] = $prop.'%';
                        }

                        if ($unlikes_count > 0) {
                            $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                            $images_likes['unlike_status'] = $unlike_status;
                            
                            //unlikes array

                            $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                            $this->db->from('ws_unlikes ul');
                            $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                            $this->db->where('ul.image_id', $img_id);

                            $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                        } else {
                            $images_likes['unlikes_detail'] = array();
                            $images_likes['unlike_status'] = 'not disliked';
                        }
                    }

					echo "sum".$total_per_votes;
					echo "<pre>Final votes";print_r($inter_array = $this->repairArray($per_votes, (100-$total_per_votes)));
					foreach($inter_array as $index=>$val){
						if($inter_array[$index]["image_id"] == $img[$index]["image_id"]){
							$final_array[] = array_merge($inter_array[$index],$img[$index]);
						}
					}
					echo "<pre>";print_r($final_array);

                    die;
                } else {
                    $post['images'] = array();
                }
                //videos data
                $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

                if (count($vid) > 0) {
                    $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                    foreach ($post['video'] as &$video_likes) {
                        
                        $likes_count = (int) $video_likes['likes'];
                        $unlikes_count = (int) $video_likes['unlikes'];
                        $vid_id = (int) $video_likes['video_id'];
                       
                        //likes status
                        if ($likes_count > 0) {
                            
                            $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                            $video_likes['like_status'] = $like_status;

                            //likes array
                            $this->db->select('l.user_id,u.fullname,u.profile_pic');
                            $this->db->from('ws_video_likes l');
                            $this->db->join('ws_users u', 'l.user_id = u.id');
                            $this->db->where('l.video_id', $vid_id);

                            $video_likes['likes_detail'] = $this->db->get()->result_array();

                            if ($video_likes['likes_detail']) {
                                foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                    $fr_id = $vidlikedetail_friend['user_id'];
                                    $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                    $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                    if ($vidlike_frnd) {
                                        $vidlikedetail_friend['is_friend'] = 1;
                                    } else {
                                        $vidlikedetail_friend['is_friend'] = 0;
                                    }
                                }
                            }
                        } else {
                            $video_likes['likes_detail'] = array();
                            $video_likes['like_status'] = 'not liked';
                        }

                        //unlikes array
                        if ($unlikes_count > 0) {
                            $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                            $video_likes['unlike_status'] = $unlike_status;

                            $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                            $this->db->from('ws_video_unlikes ul');
                            $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                            $this->db->where('ul.video_id', $vid_id);

                            $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                        } else {
                            $video_likes['unlikes_detail'] = array();
                            $video_likes['unlike_status'] = 'not disliked';
                        }
                    }
                } else {
                    $post['video'] = array();
                }

                //last comment data
                $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();


                if (count($last_comment) > 0) {
                    $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();
                    sort($post['last_comment']);
                    
                    foreach ($post['last_comment'] as &$commentDetail) {
                        $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                        $commentDetail['sender_name'] = $comment_sender['fullname'];
                        $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                        $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);

                        $commentDetail['commentuser_name'] = array();
                        if (!empty($commentDetail['mention_users'])) {
                            foreach (explode(',', $commentDetail['mention_users']) as $key => $value) {
                                $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                                $commentDetail['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                            }
                        }
                    }
                } else {
                    $post['last_comment'] = array();
                }

                //tag detail start
                $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
                if (count($tag) > 0) {
                    $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                    //check like of user id
                    foreach ($post['tagged_data'] as &$tags) {
                        $user_id_val = (int) $tags['user_id'];
                        if ($user_id_val > 0) {
                            $tag_frnd_id = $user_id_val;
                            $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                            $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                            $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                            $tags['tag_frnd'] = $tag_frnd_name;
                        } else {
                            $tags['tag_frnd'] = '';
                        }
                    }
                } else {
                    $post['tagged_data'] = array();
                }

                $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                if (count($tagwrd) > 0) {
                    $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                } else {
                    $post['taggedword_data'] = array();
                }
                //tag detail end
            }
        }
        $data = array(
            'status' => 1,
            'base_url' => $base_url,
            'post_images_url' => $post_base_url,
            'notification_count' => $notification_count,
            'data' => $combine_array_result
        );
        $this->response($data, 200);
    }
    // Handle and repair Array
    private function repairArray($array, $extra){
		$tmp = assc_array_count_values($array, 'likes_proportion');
		$max = 0;
	    foreach( $array as $k => $v )
	    {
	        $max = max( array( $max, $v['likes_proportion'] ) );
	    }
		$cnt = $tmp[$max];
		if($cnt > 1 && $cnt < count($array)){
			foreach ($array as $key => $value) {
				if($max == $value['likes_proportion']){
					$array[$key+$cnt]['likes_proportion'] += $extra;
					break;
				}
			}
		}else{
			foreach ($array as $key => $value) {
				if($max == $value['likes_proportion']){
					$array[$key]['likes_proportion'] += $extra;
					break;
				}
			}
		}
		// print_r($array);die;
		return $array;
	}
	function assc_array_count_values( $array, $key ) {
		foreach( $array as $row ) {
			$new_array[] = $row[$key];
		}
	return array_count_values( $new_array );
	}
	// End here
    public function group_post_details_post() {
        $base_url = $this->baseurl;
        //$post_base_url = $this->baseurl . 'uploads/post_images/';
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';
        $datetime1 = date('Y-m-d H:i:s', time());
        //$datetime1 = $this->input->post('datetime1');
        //$this->check_empty($datetime1, 'Please add datetime1');
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user id');
        //group post start
        $group_ids = $this->db->get_where('ws_group_members', array('member_id' => $user_id))->result_array();

        $gp_array = array();
        foreach ($group_ids as $gp_ids) {
            array_push($gp_array, $gp_ids['group_id']);
        }

        $GROUPDETAIL = array();
        $group_data = array();

        if (!empty($gp_array)) {
            foreach ($gp_array as $gp) {
                //$sql[] = 'group_id LIKE "' . $gp . '" ';
                    if (count($this->db->query('SELECT * FROM ws_posts WHERE FIND_IN_SET('.$gp.',group_id) > 0  AND status = 1 ORDER BY `post_id` DESC')->result_array()) > 0) {
                    $group_data[] = $this->db->query('SELECT * FROM ws_posts WHERE FIND_IN_SET('.$gp.',group_id) > 0 AND status = 1 ORDER BY `post_id` DESC')->result_array();
                    //echo $this->db->last_query();die;
                    //echo '<pre>';print_r($group_data);die;
                    foreach ($group_data as $key => $value) {
                        foreach ($value as $inner_value) {
                            array_push($GROUPDETAIL, $inner_value);
                        }
                    }
                }
            }
        }

        $combine_array = array_unique($GROUPDETAIL, SORT_REGULAR);
        usort($combine_array, array($this, 'date_compare'));
        foreach ($combine_array as &$post) {
            $postID = $post['post_id'];
            $poll_link = $base_url.'web/?id='.$postID;
            $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
            $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
            $commentCount = $this->common_model->getQuery($comment_post);

            $post['comment_count'] = $commentCount[0]['count'];

            //post creator
            $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
            $post['post_creator'] = $post_sender['fullname'];
            $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);
            //group_name 
            $sharegroup_name = array();
            if ($post['group_id'] != 0) {
                foreach (explode(',', $post['group_id']) as $key => $value) {
                    $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                    $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                }
            } else {
                $sharegroup_name = '';
            }
            $post['group_name'] = $sharegroup_name;
            //frnd name
            $sharefriend_name = array();
            if ($post['friend_id'] != 0) {
                foreach (explode(',', $post['friend_id']) as $key => $value) {
                    $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value, 'activated' => 1), $multi_record = false, $order = '');
                    $sharefriend_name[] = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                }
            } else {
                $sharefriend_name = '';
            }
            $post['friend_name'] = $sharefriend_name;

            //text data

            $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
            $totalPostTextLikes =0;
            foreach($text as $tx)
            {
                $totalPostTextLikes +=  $tx['likes'];
            }
            $total_textprop = 0;
            $loop_textindex = 0;
            if (count($text) > 0) {
                $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();

                //check like of user id
                foreach ($post['text'] as &$text_likes) {
                    $loop_textindex++;
                    $likes_count = (int) $text_likes['likes'];
                    $unlikes_count = (int) $text_likes['unlikes'];
                    $text_id = (int) $text_likes['id'];

                    
                    //likes status
                    if ($likes_count > 0) {

                        $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                        if(count($text) == $loop_textindex && $loop_textindex > 1){
                        $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                        }else{
                        $prop = round ( $likesproportion);
                        $total_textprop += $prop;
                        $text_likes['likes_proportion'] = $prop.'%';
                        }
                        
                        $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                        $text_likes['like_status'] = $like_status;
                        

                        //likes array

                        $this->db->select('l.user_id,u.fullname,u.profile_pic');
                        $this->db->from('ws_text_likes l');
                        $this->db->join('ws_users u', 'l.user_id = u.id');
                        $this->db->where('l.text_id', $text_id);

                        $text_likes['likes_detail'] = $this->db->get()->result_array();
                    } else {
                        $text_likes['likes_detail'] = array();
                        $text_likes['like_status'] = 'not liked';
                        $text_likes['likes_proportion'] = '0%';
                    }

                    //unlikes array
                    if ($unlikes_count > 0) {
                        $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                        $text_likes['unlike_status'] = $unlike_status;

                        //unlikes array

                        $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                        $this->db->from('ws_text_unlikes ul');
                        $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                        $this->db->where('ul.text_id', $text_id);

                        $text_likes['unlikes_detail'] = $this->db->get()->result_array();
                    } else {
                        $text_likes['unlikes_detail'] = array();
                        $text_likes['unlike_status'] = 'not disliked';
                    }
                } // -- FOR- EACH($post['images'] as &$images_likes) CLOSD
            } //  if( count($img ) > 0 ) CLOSD
            else {
                $post['text'] = array();
            }

            //images data
            $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
            $totalPostImageLikes =0;
            $totalPostImageUnlikes =0;
            foreach($img as $im)
            {
                $totalPostImageLikes +=  $im['likes'];
                $totalPostImageUnlikes +=  $im['unlikes'];
            }
            $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
            $total_imgprop = 0;
            $loop_imgindex = 0;
            if (count($img) > 0) {
                $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();

                //check like of user id
                foreach ($post['images'] as &$images_likes) {
                    $loop_imgindex++;
                    $likes_count = (int) $images_likes['likes'];

                    $unlikes_count = (int) $images_likes['unlikes'];
                    $img_id = (int) $images_likes['image_id'];

                    
                    //likes status
                    if ($likes_count > 0) {

                        $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                        if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                        $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                        }else{
                        $prop = round ( $likesproportion);
                        $total_imgprop += $prop;
                        $images_likes['likes_proportion'] = $prop.'%';
                        }
                        
                        $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                        $images_likes['like_status'] = $like_status;
                        

                        //likes array

                        $this->db->select('l.user_id,u.fullname,u.profile_pic');
                        $this->db->from('ws_likes l');
                        $this->db->join('ws_users u', 'l.user_id = u.id');
                        $this->db->where('l.image_id', $img_id);

                        $images_likes['likes_detail'] = $this->db->get()->result_array();
                    } else {
                        $images_likes['likes_detail'] = array();
                        $images_likes['like_status'] = 'not liked';
                        $images_likes['likes_proportion'] = '0%';
                    }

                    //unlikes array
                    $images_likes['unlikes_proportion'] = (  (round ( $unlikesproportion , 0) ) ).'%';
                    if(count($img) == 1){
                        $prop = round ( $unlikesproportion);
                        $total_imgprop += $prop;
                        $images_likes['unlikes_proportion'] = $prop.'%';
                    }
                    if ($unlikes_count > 0) {
                        $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                        $images_likes['unlike_status'] = $unlike_status;
                        

                        //unlikes array
                        $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                        $this->db->from('ws_unlikes ul');
                        $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                        $this->db->where('ul.image_id', $img_id);

                        $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                    } else {
                        $images_likes['unlikes_detail'] = array();
                        $images_likes['unlike_status'] = 'not disliked';
                    }
                } // -- FOR- EACH($post['images'] as &$images_likes) CLOSD
            } //  if( count($img ) > 0 ) CLOSD
            else {
                $post['images'] = array();
            }

            //videos data
            $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

            if (count($vid) > 0) {
                $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                foreach ($post['video'] as &$video_likes) {

                    $likes_count = (int) $video_likes['likes'];
                    $unlikes_count = (int) $video_likes['unlikes'];
                    $vid_id = (int) $video_likes['video_id'];
                    //likes status
                    if ($likes_count > 0) {

                        $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                        $video_likes['like_status'] = $like_status;

                        //likes array

                        $this->db->select('l.user_id,u.fullname,u.profile_pic');
                        $this->db->from('ws_video_likes l');
                        $this->db->join('ws_users u', 'l.user_id = u.id');
                        $this->db->where('l.video_id', $vid_id);

                        $video_likes['likes_detail'] = $this->db->get()->result_array();

                        if ($video_likes['likes_detail']) {
                            foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                $fr_id = $vidlikedetail_friend['user_id'];
                                $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                if ($vidlike_frnd) {
                                    $vidlikedetail_friend['is_friend'] = 1;
                                } else {
                                    $vidlikedetail_friend['is_friend'] = 0;
                                }
                            }
                        }
                    }       // --  if($likes_count > 0) CLOSED 
                    else {
                        $video_likes['likes_detail'] = array();
                        $video_likes['like_status'] = 'not liked';
                    }

                    //unlikes array
                    if ($unlikes_count > 0) {
                        $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                        $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                        $video_likes['unlike_status'] = $unlike_status;

                        $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                        $this->db->from('ws_video_unlikes ul');
                        $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                        $this->db->where('ul.video_id', $vid_id);

                        $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                    } else {
                        $video_likes['unlikes_detail'] = array();
                        $video_likes['unlike_status'] = 'not disliked';
                    }
                } // -- forEach closed 
            } else {
                $post['video'] = array();
            }

            //last comment data
            $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();

            if (count($last_comment) > 0) {
                //$post['last_comment'] = array();
                $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();

                foreach ($post['last_comment'] as &$commentDetail) {
                    $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                    $commentDetail['sender_name'] = $comment_sender['fullname'];
                    $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                    $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);

                    $commentDetail['commentuser_name'] = array();
                    if (!empty($commentDetail['mention_users'])) {
                        foreach (explode(',', $commentDetail['mention_users']) as $key => $value) {
                            $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                            $commentDetail['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                        }
                    }
                }
            } else {
                $post['last_comment'] = array();
            }

            //tag detail start
            $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
            if (count($tag) > 0) {
                $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                //check like of user id
                foreach ($post['tagged_data'] as &$tags) {
                    $user_id_val = (int) $tags['user_id'];
                    if ($user_id_val > 0) {
                        $tag_frnd_id = $user_id_val;
                        //echo 'yguhu'.$img_id;die;
                        $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                        $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                        $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                        $tags['tag_frnd'] = $tag_frnd_name;
                    } else {
                        $tags['tag_frnd'] = '';
                    }
                }
            } else {
                $post['tagged_data'] = array();
            }

            $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
            if (count($tagwrd) > 0) {
                $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
            } else {
                $post['taggedword_data'] = array();
            }
            //tag detail end
        }  //-- for EACH ($combine_array as &$post)  CLOSED 
        $data = array(
            'status' => 1,
            'base_url' => $base_url,
            'post_images_url' => $post_base_url,
            'data' => $combine_array
        );
        $this->response($data, 200);
    }

//-- group_post_details_post()  CLOSED

    public function test_notification_post($receiver, $type, $sender, $post_id = '') {
        //echo '9876';die;
        $post_data = array('receiver_id' => $receiver, 'notification_type' => $type, 'sender_id' => $sender, 'post_id' => $post_id);
        if ($this->db->insert('ws_notifications', $post_data)) {
            return true;
        } else {
            return false;
        }
    }

    public function check_notification($receiver, $type, $sender, $post_id = '') {
        $where_data = array('receiver_id' => $receiver, 'notification_type' => $type, 'sender_id' => $sender, 'post_id' => $post_id);
        $save_detail = $this->common_model->findWhere($table = 'ws_notifications', $where_data, $multi_record = false, $order = '');
        if ($save_detail) {
            return true;
        } else {
            return false;
        }
    }

    function save_notification($receiver, $type, $sender, $post_id = '') {
        $post_data = array('receiver_id' => $receiver, 'notification_type' => $type, 'sender_id' => $sender, 'post_id' => $post_id);
        //echo '<pre>';print_r($post_data);
        $this->db->insert('ws_notifications' , $post_data);
        /*if ($this->db->insert('ws_notifications', $post_data)) {
            return true;
        } else {
            return false;
        }*/
    }

    //like and comment notification
    function save_multiple_notification($receiver, $type, $sender, $post_id = '')
    {
        $where_data = array('receiver_id' => $receiver,'post_id' => $post_id);
        $save_detail = $this->common_model->findWhere($table = 'ws_notifications', $where_data, $multi_record = false, $order = '');
        if($save_detail)
        {
            //update
            $post_data = array('status' => 1, 'sender_id' => $sender_id, 'added_at' => date('Y-m-d h:i'));
            $this->common_model->updateWhere($table = 'ws_notifications', $where_data , $post_data);
            echo $this->db->last_query();die;
        }
        else
        {
            //insert
            $post_data = array('receiver_id' => $receiver, 'notification_type' => $type, 'sender_id' => $sender, 'post_id' => $post_id);
            $this->db->insert('ws_notifications', $post_data);
        }    
    }

    public function notification_list_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please provide user_id');

        $user_exists = $this->db->get_where('ws_users', array('id' => $user_id, 'activated' => 1))->row_array();

        if (!empty($user_exists)) {
            $notification_list = $this->db->order_by('added_at', 'DESC')->get_where('ws_notifications', array('receiver_id' => $user_id))->result_array();
            if ($notification_list) {
                foreach ($notification_list as &$list) {
                    $base_url = $this->baseurl;

                    //post title
                    $post_detail = $this->common_model->findWhere($table = 'ws_posts', $where_data = array('post_id' => $list['post_id']), $multi_record = false, $order = '');
                    $post_type = $post_detail['type'];

                    $post_question = $post_detail['question'];
                    $count_question = str_word_count($post_question);
                    if($count_question > 5)
                    {
                        $pieces_question = explode(" ", $post_question);
                        $val_question = implode(" ", array_splice($pieces_question, 0, 5));
                        $extract_question = $val_question.' ...';
                    }
                    else
                    {
                        $extract_question = $post_question;
                    } 
                    
                    $post_title = $post_detail['title'];
                    $count_title = str_word_count($post_title);
                    if($count_title >= 5)
                    {
                        $pieces_title = explode(" ", $post_title);
                        $val_title = implode(" ", array_splice($pieces_title, 0, 5));
                        $extract_title = $val_title.' ...';
                    }
                    else
                    {
                        $extract_title = $post_title;
                    }    
                    
                    //chk follow
                    $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $list['sender_id']), $multi_record = false, $order = '');
                    $follow = 0;
                    if ($chk_follow_data) {
                        $follow = 1;
                    }
                    $sender_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['sender_id'], 'activated' => 1), $multi_record = false, $order = '');
                    $list['sender_name'] = (!empty($sender_detail['fullname']) ) ? $sender_detail['fullname'] : '';
                    $list['sender_pic'] = (!empty($sender_detail['profile_pic']) ) ? $base_url . $sender_detail['profile_pic'] : '';
                    $list['follow'] = $follow;
                    $list['post_question'] = (!empty($extract_question) ) ? $extract_question : '';
                    $list['post_title'] = (!empty($extract_title) ) ? $extract_title : '';
                    $list['post_type'] = (!empty($post_type) ) ? $post_type : '';
                    $list['poll_type'] = (!empty($post_detail['poll_type']) ) ? $post_detail['poll_type'] : '';
                    $list['group_id'] = (!empty($post_detail['group_id']) ) ? $post_detail['group_id'] : '';

                    //group
                    $groupALL = $post_detail['group_id'];
                    if($list['notification_type'] == 'group' && $groupALL != '')
                    {
                        $query = "SELECT GROUP_CONCAT(group_name SEPARATOR ', ') as group_name from ws_groups where id in ($groupALL)";
                        $result = $this->db->query($query)->row_array();
                        $group_name = $result['group_name'];
                        $list['post_title'] = $extract_title.' - '.$group_name.' ...';
                        $list['post_question'] = $extract_question.' - '.$group_name.' ...';
                    }
                    //group
                }
                $this->common_model->updateWhere($table = 'ws_notifications', $where_data = array('receiver_id' => $user_id), $post_data = array('status' => 1));
            }
            $this->response(array('status' => 1, 'data' => $notification_list), 200);
        } else {
            $this->response(array('status' => 0, 'message' => 'User does not exist'), 200);
        }
    }

    public function add_follower_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $base_url = $this->baseurl;

        //notification count
        $notification = $this->db->order_by('added_at', 'desc')->get_where('ws_notifications', array('receiver_id' => $user_id, 'status' => 0))->result_array();
        $notification_count = count($notification);

        //user check
        $chk_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');
        if ($chk_user_data) {
            $user_query = "SELECT id,fullname,email,unique_name,profile_pic,phone  FROM ws_users where (id != '$user_id' AND activated = 1)";
            $query = $this->db->query($user_query);
            $user_data = $query->result_array();
            $user_detail = array();
            if ($user_data) {
                
                foreach ($user_data as $detailuser_data) {
                    //chk follow
                    $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $detailuser_data['id']), $multi_record = false, $order = '');
                    $follow = 0;
                    if ($chk_follow_data) {
                        $follow = 1;
                    }
                    $user_detail[] = array(
                        'user_id' => $detailuser_data['id'],
                        'fullname' => (!empty($detailuser_data['fullname']) ? $detailuser_data['fullname'] : ''),
                        'email' => (!empty($detailuser_data['email']) ? $detailuser_data['email'] : ''),
                        'phone' => (!empty($detailuser_data['phone']) ? $detailuser_data['phone'] : ''),
                        'unique_name' => (!empty($detailuser_data['unique_name']) ? $detailuser_data['unique_name'] : ''),
                        'profile_pic' => (!empty($detailuser_data['profile_pic']) ? $base_url . $detailuser_data['profile_pic'] : ''),
                        'follow_status' => $follow
                    );
                }
                $data = array(
                    'status' => 1,
                    'notification_count' => $notification_count,
                    'data' => $user_detail
                );
            } else {
                $data = array(
                    'status' => 0,
                    'data' => $user_detail
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'user id does not exist'
            );
        }
        $this->response($data, 200);
    }

    public function search_users_post()
    {
        $limit = 10;

        $index = $this->input->post('index');
        
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $lastuser_id = $this->input->post('lastuser_id');
        $this->check_empty($lastuser_id, 'Please enter lastuser_id');

        $matchString = $this->input->post('matchString');
        $this->check_empty($matchString, 'Please enter matchString');

        $base_url = $this->baseurl;

        //notification count
        $notification = $this->db->order_by('added_at', 'desc')->get_where('ws_notifications', array('receiver_id' => $user_id, 'status' => 0))->result_array();
        $notification_count = count($notification);

        //user check
        $chk_user_data = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $user_id, 'activated' => 1), $multi_record = false, $order = '');
        if ($chk_user_data) {
           //$matchString = addslashes(addslashes($matchString));
            //$matchString = str_replace('/', '///', $matchString);
            //$matchString = str_replace('\\','\\\\\\\\',$matchString);
            $user_query = "SELECT id,fullname,unique_name,profile_pic FROM ws_users where (lower(fullname) LIKE lower('% $matchString%') OR lower(fullname) LIKE lower('$matchString%') OR lower(unique_name) LIKE lower('$matchString%')) AND id != '$user_id' AND activated = 1 AND `id` > '$lastuser_id' ORDER BY id ASC LIMIT $limit";
            $query = $this->db->query($user_query);
            //echo $this->db->last_query();die;
            $user_data = $query->result_array();
            $user_detail = array();
            if ($user_data) {
                
                $last_id = 0;
                foreach ($user_data as $detailuser_data) {
                    if($detailuser_data['id'] > $last_id)
                    {
                        $last_id = $detailuser_data['id'];
                    }

                    //chk follow
                    $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $detailuser_data['id']), $multi_record = false, $order = '');
                    $follow = 0;
                    if ($chk_follow_data) {
                        $follow = 1;
                    }
                    $user_detail[] = array(
                        'user_id' => $detailuser_data['id'],
                        'fullname' => (!empty($detailuser_data['fullname']) ? $detailuser_data['fullname'] : ''),
                       // 'email' => (!empty($detailuser_data['email']) ? $detailuser_data['email'] : ''),
                       // 'phone' => (!empty($detailuser_data['phone']) ? $detailuser_data['phone'] : ''),
                        'unique_name' => (!empty($detailuser_data['unique_name']) ? $detailuser_data['unique_name'] : ''),
                        'profile_pic' => (!empty($detailuser_data['profile_pic']) ? $base_url . $detailuser_data['profile_pic'] : ''),
                        'follow_status' => $follow
                    );
                }
                //echo '<pre>';print_r($user_detail);
                //usort($combine_array, array($this, 'date_compare'));
                //usort($user_detail, array($this,'compareByName'));
                //echo '<pre>now';print_r($user_detail);die;
                $data = array(
                    'status' => 1,
                    'notification_count' => $notification_count,
                    'data' => $user_detail,
                    'lastuser_id' => $last_id,
                    'index' => $index
                );
            } else {
                $data = array(
                    'status' => 1,
                    'data' => $user_detail,
                    'index' => $index,
                    'notification_count' => $notification_count,
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'user id does not exist'
            );
        }
        $this->response($data, 200);
    }

    function compareByName($a, $b) {
      return strcmp($a["fullname"], $b["fullname"]);
    }

    

   

    public function pollReactionUsers_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $poll_type = $this->input->post('poll_type');
        $this->check_empty($poll_type, 'Please enter poll_type');

        $poll_option_id = $this->input->post('poll_option_id');
        $this->check_empty($poll_option_id, 'Please enter poll_option_id');

        $poll_reaction_type = $this->input->post('poll_reaction_type');
        $this->check_empty($poll_reaction_type, 'Please enter poll_reaction_type');

        $base_url = $this->baseurl;

        if($poll_type == 'text')
        {
            //likes
            $this->db->select('l.user_id,u.unique_name,u.fullname,u.profile_pic');
            $this->db->from('ws_text_likes l');
            $this->db->join('ws_users u', 'l.user_id = u.id');
            $this->db->where('l.text_id', $poll_option_id);
            $likes_detail = $this->db->get()->result_array();

            //like count
            $likes_count = count($likes_detail);
            //like status
            $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $poll_option_id, 'user_id' => $user_id), $multi_record = false, $order = '');
            $like_status = (!empty($like) ) ? 1 : 0;
            if($likes_count > 0)
            {
                $followTrue = array();
                $followFalse = array();
                $followMe = array();
                $combineArray = array();

                foreach($likes_detail as &$like)
                {
                    //chk follow
                    $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $like['user_id']), $multi_record = false, $order = '');
                    $like['follow_status'] = (!empty($chk_follow_data) ) ? 1 : 0;
                    $like['profile_pic'] = (!empty($like['profile_pic']) ? $base_url . $like['profile_pic'] : '');

                    if($user_id == $like['user_id'])
                    {
                        $followMe[] = array(
                                'user_id' => $like['user_id'],
                                'unique_name' => $like['unique_name'],
                                'fullname' => $like['fullname'],
                                'profile_pic' => $like['profile_pic'],
                                'follow_status' => 0
                                );
                    }
                    elseif(!empty($chk_follow_data))
                    {
                        $followTrue[] = array(
                                'user_id' => $like['user_id'],
                                'unique_name' => $like['unique_name'],
                                'fullname' => $like['fullname'],
                                'profile_pic' => $like['profile_pic'],
                                'follow_status' => 1
                                );
                    }
                    else{
                        $followFalse[] = array(
                                'user_id' => $like['user_id'],
                                'unique_name' => $like['unique_name'],
                                'fullname' => $like['fullname'],
                                'profile_pic' => $like['profile_pic'],
                                'follow_status' => 0
                                );
                    }
                }
                $combineArray = array_merge($followMe,$followTrue,$followFalse);
                $data = array(
                    'status' => 1,
                    'count' => $likes_count,
                    'data' => $combineArray,
                    'my_status' => $like_status
                );
            }
            else{
                $likes_detail = array();
                $data = array(
                    'status' => 1,
                    'count' => $likes_count,
                    'data' => $combineArray,
                    'my_status' => $like_status
                );
            }
            $this->response($data, 200);
        }elseif($poll_type == 'image')
        {
            if($poll_reaction_type == 'like')
            {
                //likes
                $this->db->select('l.user_id,u.unique_name,u.fullname,u.profile_pic');
                $this->db->from('ws_likes l');
                $this->db->join('ws_users u', 'l.user_id = u.id');
                $this->db->where('l.image_id', $poll_option_id);
                $likes_detail = $this->db->get()->result_array();

                //like count
                $likes_count = count($likes_detail);

                //like status
                $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $poll_option_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $like_status = (!empty($like) ) ? 1 : 0;
                if($likes_count > 0)
                {
                    $followTrue = array();
                    $followFalse = array();
                    $followMe = array();
                    $combineArray = array();
                    foreach($likes_detail as &$like)
                    {
                        //chk follow
                        $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $like['user_id']), $multi_record = false, $order = '');
                        $like['follow_status'] = (!empty($chk_follow_data) ) ? 1 : 0;
                        $like['profile_pic'] = (!empty($like['profile_pic']) ? $base_url . $like['profile_pic'] : '');
                        
                        if($user_id == $like['user_id'])
                        {
                            $followMe[] = array(
                                    'user_id' => $like['user_id'],
                                    'unique_name' => $like['unique_name'],
                                    'fullname' => $like['fullname'],
                                    'profile_pic' => $like['profile_pic'],
                                    'follow_status' => 0
                                    );
                        }
                        elseif(!empty($chk_follow_data))
                        {
                            $followTrue[] = array(
                                    'user_id' => $like['user_id'],
                                    'unique_name' => $like['unique_name'],
                                    'fullname' => $like['fullname'],
                                    'profile_pic' => $like['profile_pic'],
                                    'follow_status' => 1
                                    );
                        }
                        else{
                            $followFalse[] = array(
                                    'user_id' => $like['user_id'],
                                    'unique_name' => $like['unique_name'],
                                    'fullname' => $like['fullname'],
                                    'profile_pic' => $like['profile_pic'],
                                    'follow_status' => 0
                                    );
                        }
                    }
                    $combineArray = array_merge($followMe,$followTrue,$followFalse);
                    $data = array(
                        'status' => 1,
                        'count' => $likes_count,
                       // 'data' => $likes_detail,
                       // 'followMe' => $followMe,
                       // 'followTrue' => $followTrue,
                       // 'followFalse' => $followFalse,
                        'data' => $combineArray,
                        'my_status' => $like_status
                    );
                }else{
                    $likes_detail = array();
                    $data = array(
                        'status' => 1,
                        'count' => $likes_count,
                        'data' => $combineArray,
                        'my_status' => $like_status
                    );
                }
                $this->response($data, 200);
            }
            else{
                //unlikes
                $this->db->select('ul.user_id,unu.unique_name,unu.fullname,unu.profile_pic');
                $this->db->from('ws_unlikes ul');
                $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                $this->db->where('ul.image_id', $poll_option_id);
                $unlikes_detail = $this->db->get()->result_array();

                //unlike count
                $unlikes_count = count($unlikes_detail);

                //unlike status
                $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $poll_option_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                $unlike_status = (!empty($unlike) ) ? 1 : 0;
                if($unlikes_count > 0)
                {
                    $followTrue = array();
                    $followFalse = array();
                    $followMe = array();
                    $combineArray = array();
                    foreach($unlikes_detail as &$unlike)
                    {
                        //chk follow
                        $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $unlike['user_id']), $multi_record = false, $order = '');
                        $unlike['follow_status'] = (!empty($chk_follow_data) ) ? 1 : 0;
                        $unlike['profile_pic'] = (!empty($unlike['profile_pic']) ? $base_url . $unlike['profile_pic'] : '');

                        if($user_id == $unlike['user_id'])
                        {
                            $followMe[] = array(
                                    'user_id' => $unlike['user_id'],
                                    'unique_name' => $unlike['unique_name'],
                                    'fullname' => $unlike['fullname'],
                                    'profile_pic' => $unlike['profile_pic'],
                                    'follow_status' => 0
                                    );
                        }
                        elseif(!empty($chk_follow_data))
                        {
                            $followTrue[] = array(
                                    'user_id' => $unlike['user_id'],
                                    'unique_name' => $unlike['unique_name'],
                                    'fullname' => $unlike['fullname'],
                                    'profile_pic' => $unlike['profile_pic'],
                                    'follow_status' => 1
                                    );
                        }
                        else{
                            $followFalse[] = array(
                                    'user_id' => $unlike['user_id'],
                                    'unique_name' => $unlike['unique_name'],
                                    'fullname' => $unlike['fullname'],
                                    'profile_pic' => $unlike['profile_pic'],
                                    'follow_status' => 0
                                    );
                        }
                    }
                    $combineArray = array_merge($followMe,$followTrue,$followFalse);
                    $data = array(
                        'status' => 1,
                        'count' => $unlikes_count,
                        'data' => $combineArray,
                        'my_status' => $unlike_status
                    );
                }else{
                    $unlikes_detail = array();
                    $data = array(
                        'status' => 1,
                        'count' => $unlikes_count,
                        'data' => $combineArray,
                        'my_status' => $unlike_status
                    );
                }
                $this->response($data, 200);
            }
        }
    }

    public function repost_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please enter user_id');

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please enter post_id');

        $where_data = array('post_id' => $post_id, 'user_id' => $user_id);
        $chk_post_data = $this->common_model->findWhere($table = 'ws_posts', $where_data, $multi_record = false, $order = '');
        if ($chk_post_data) {
            $added_at = date('Y-m-d H:i:s', time());
            $post_data = array('added_at' => $added_at, 'repost_status' => 1);
            if ($this->common_model->updateWhere($table = 'ws_posts', $where_data, $post_data)) {
                $datetime1 = date('Y-m-d H:i:s', time());
                $new_time = $this->time_elapsed_string($datetime1, $post['added_at']);
                $data = array(
                    'status' => 1,
                    'message' => 'success',
                    'new_time' => $new_time
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'error'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'either post_id or user_id is wrong'
            );
        }
        $this->response($data, 200);
    }

    public function edit_comment_post() {
        $comment_id = $this->input->post('comment_id');
        $this->check_empty($comment_id, 'Please add comment_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $comment = $this->input->post('comment');
        $this->check_empty($comment, 'Please add comment');

        $mention_indexes = $this->input->post('mention_indexes');
        $mention_users = $this->input->post('mention_users');

        $where_data = array('comment_id' => $comment_id, 'user_id' => $user_id);
        $chk_comment_data = $this->common_model->findWhere($table = 'ws_comments', $where_data, $multi_record = false, $order = '');
        if ($chk_comment_data) {
            $post_data = array(
                'comment' => $comment,
                'mention_indexes' => (!empty($mention_indexes) ? $mention_indexes : ''),
                'mention_users' => (!empty($mention_users) ? $mention_users : '')
            );
            if ($this->common_model->updateWhere($table = 'ws_comments', $where_data, $post_data)) {
                $data = array(
                    'status' => 1,
                    'message' => 'success'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'error'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'either comment_id or user_id is wrong'
            );
        }
        $this->response($data, 200);
    }

    public function update_post_post() {
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');


        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $taguser_id = $this->input->post('taguser_id');
        
        $tagword = $this->input->post('tagword');
        
        if (empty($taguser_id) && empty($tagword)) {
            $data = array(
                'status' => 0,
                'message' => 'Please add taguser_id or tagword'
            );
        }

        $chk_post_owner = $this->common_model->findWhere($table = 'ws_posts', $where_data = array('post_id' => $post_id, 'user_id' => $user_id), $multi_record = false, $order = '');
        if ($chk_post_owner) {
            //post user tags
            //delete all previous tags
            $this->common_model->delete($table = 'ws_tags', $where_data = array('post_id' => $post_id));
            if (!empty($taguser_id)) {
                $tagmember_array = explode(",", $taguser_id);
                //echo '<pre>';var_dump($member_array);

                if ($tagmember_array) {
                    foreach ($tagmember_array as $id) {
                        $id_val = (int) $id;
                        $member_check = "select * from ws_tags where (post_id = '$post_id' AND user_id = '$id_val') ";
                        $taguser_data = $this->common_model->getQuery($member_check);
                        if (empty($taguser_data)) {
                            $post_data = array(
                                'post_id' => $post_id,
                                'user_id' => $id_val,
                            );
                            $tag_member = $this->common_model->add('ws_tags', $post_data);
                            if ($tag_member) {

                                //send notification start
                                $receiver = $id_val;
                                $notify_chk = $this->check_notification_set($receiver, 'tag');
                                $already_chk = $this->check_notification($receiver, 'tag', $user_id, $post_id);
                                if ($already_chk == false && $notify_chk == true) {
                                    $this->save_notification($receiver, 'tag', $user_id, $post_id);
                                    $this->send_notification($receiver, $user_id, 'tag', '', '', $post_id);

                                    
                                }
                                //send notification end
                                $data = array(
                                    'status' => 1,
                                    'message' => 'Success'
                                );
                            } else {
                                $data = array(
                                    'status' => 0,
                                    'message' => 'Unable to tag'
                                );
                            }
                        }
                    }
                }
            }//end post user tag
            //delete all previous tags
            $this->common_model->delete($table = 'ws_words', $where_data = array('post_id' => $post_id));
            if (!empty($tagword)) {
                $tagword_array = explode(",", $tagword);
                if ($tagword_array) {

                    foreach ($tagword_array as $word) {
                        $word_val = $word;
                        $word_check = "select * from ws_words where (post_id = '$post_id' AND word = '$word_val') ";
                        $tagword_data = $this->common_model->getQuery($word_check);
                        if (empty($tagword_data)) {
                            $post_data = array(
                                'post_id' => $post_id,
                                'word' => $word_val,
                            );
                            $tag_word = $this->common_model->add('ws_words', $post_data);
                            if ($tag_word) {
                                $data = array(
                                    'status' => 1,
                                    'message' => 'Success'
                                );
                            } else {
                                $data = array(
                                    'status' => 0,
                                    'message' => 'Unable to tag'
                                );
                            }
                        }
                    }
                }
            }//end tag word 
            $data = array(
                'status' => 1,
                'message' => 'tagged successfully'
            );
        } else {
            $data = array(
                'status' => 0,
                'message' => 'Only owner can update post'
            );
        }
        $this->response($data, 200);
    }

    public function delete_comment_post() {
        $comment_id = $this->input->post('comment_id');
        $this->check_empty($comment_id, 'Please add comment_id');

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $where_data = array('comment_id' => $comment_id, 'user_id' => $user_id);
        $chk_comment_data = $this->common_model->findWhere($table = 'ws_comments', $where_data, $multi_record = false, $order = '');
        if ($chk_comment_data) {
            if ($this->common_model->delete($table = 'ws_comments', $where_data)) {
                $data = array(
                    'status' => 1,
                    'message' => 'success'
                );
            } else {
                $data = array(
                    'status' => 0,
                    'message' => 'error'
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'either comment_id or user_id is wrong'
            );
        }
        $this->response($data, 200);
    }

    public function follower_group_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        //group count
        $groups = $this->db->get_where('ws_group_members', array('member_id' => $user_id, 'status' => 1))->result_array();
        $group_count = count($groups);

        //follower count
        $follower = $this->db->order_by('created', 'desc')->get_where('ws_follow', array('friend_id' => $user_id))->result_array();
        $follower_count = count($follower);

        $data = array(
            'status' => 1,
            'follower' => $follower_count,
            'group' => $group_count
        );
        $this->response($data, 200);
    }

    public function hashing_post() {
        $match = $this->input->post('match');
        $this->check_empty($match, 'Please add match');
        
        $posts_query = "(SELECT post_id,type,question,title,added_at FROM ws_posts WHERE (question REGEXP '#[[:<:]]" . $match . "[[:>:]]') OR  (title REGEXP '#[[:<:]]" . $match . "[[:>:]]') ) UNION (SELECT p.post_id,p.type,p.question,p.title,p.added_at FROM ws_comments c join ws_posts p on p.post_id = c.post_id WHERE (c.comment REGEXP '#[[:<:]]" . $match . "[[:>:]]') )  order by added_at desc";

        $post_data = $this->common_model->getQuery($posts_query);

        if ($post_data) {
            $data = array('status' => 1, 'data' => $post_data);
        } else {
            $post_data = array();
            $data = array('status' => 1, 'data' => $post_data);
        }
        $this->response($data, 200);
    }

    public function get_grouppost_post() {
        $limit = 10;

        $offset = $this->input->post('offset');
        $this->check_integer_empty($offset, 'Please add offset');

        $base_url = $this->baseurl;
        //$post_base_url = $this->baseurl . 'uploads/post_images/';
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $group_id = $this->input->post('group_id');
        $this->check_empty($group_id, 'Please add group_id');

        $datetime1 = date('Y-m-d H:i:s', time());

        //individual group post

        $result = $this->db->order_by('added_at', 'DESC')->get_where('ws_posts', array('group_id RLIKE' => '(^|,)' . $group_id . '(,|$)', 'status' => 1) , $limit , $offset)->result_array();
        $result1 = $this->db->order_by('added_at', 'DESC')->get_where('ws_posts', array('group_id RLIKE' => '(^|,)' . $group_id . '(,|$)', 'status' => 1))->result_array();
        $post_count = count($result1);
        $groupValue = $this->common_model->findWhere($table = 'ws_groups', array('id' => $group_id, 'status' => 1), $multi_record = false, $order = '');
        $group_members = $this->db->get_where('ws_group_members', array('group_id' => $group_id, 'status' => 1))->result_array();
        $group_memberscount = count($group_members);


        if (empty($result)) {
            $result = array();
        } else {
            /* provide the number of likes , last comment and images for a post */
            foreach ($result as &$post) {

                //comment count
                $postID = $post['post_id'];
                $poll_link = $base_url.'web/?id='.$postID;
                $post['poll_link'] = '<iframe src="'.$poll_link.'" height="200" width="300"></iframe>';
                $comment_post = "select count(*) as count from ws_comments where post_id = '$postID'";
                $commentCount = $this->common_model->getQuery($comment_post);
                $post['comment_count'] = $commentCount[0]['count'];
                //post creator
                $post_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $post['user_id'], 'activated' => 1), $multi_record = false, $order = '');
                $post['post_creator'] = (!empty($post_sender['fullname']) ) ? $post_sender['fullname'] : '';
                $post['creator_pic'] = (!empty($post_sender['profile_pic']) ) ? $base_url . $post_sender['profile_pic'] : '';
                $post['creator_unique_name'] = (!empty($post_sender['unique_name']) ) ? $post_sender['unique_name'] : '';
                $post['new_time'] = $this->time_elapsed_string($datetime1, $post['added_at']);
                //group_name 
                $sharegroup_name = array();
                if ($post['group_id'] != 0) {
                    foreach (explode(',', $post['group_id']) as $key => $value) {
                        $sharegroup_detail = $this->common_model->findWhere($table = 'ws_groups', array('id' => $value), $multi_record = false, $order = '');
                        $sharegroup_name[] = (!empty($sharegroup_detail) ) ? $sharegroup_detail['group_name'] : '';
                    }
                } else {
                    $sharegroup_name = '';
                }
                $post['group_name'] = $sharegroup_name;
                //frnd name
                $sharefriend_name = array();
                if ($post['friend_id'] != 0) {
                    foreach (explode(',', $post['friend_id']) as $key => $value) {
                        $sharefriend_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value, 'activated' => 1), $multi_record = false, $order = '');
                        $sharefriend_name[] = (!empty($sharefriend_detail) ) ? $sharefriend_detail['fullname'] : '';
                    }
                } else {
                    $sharefriend_name = '';
                }
                $post['friend_name'] = $sharefriend_name;

                //text data
                $text = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();
                $totalPostTextLikes =0;
                foreach($text as $tx)
                {
                    $totalPostTextLikes +=  $tx['likes'];
                }
                $total_textprop = 0;
                $loop_textindex = 0;
                if (count($text) > 0) {
                    $post['text'] = $this->db->get_where('ws_text', array('post_id' => $post['post_id']))->result_array();

                    //check like of user id
                    foreach ($post['text'] as &$text_likes) {
                        $loop_textindex++;
                        $likes_count = (int) $text_likes['likes'];

                        $unlikes_count = (int) $text_likes['unlikes'];
                        $text_id = (int) $text_likes['id'];

                        
                        //likes status
                        if ($likes_count > 0) {
                            $likesproportion = ( $likes_count / $totalPostTextLikes ) * 100;
                            if(count($text) == $loop_textindex && $loop_textindex > 1){
                            $text_likes['likes_proportion'] = (100 - $total_textprop).'%';
                            }else{
                            $prop = round ( $likesproportion);
                            $total_textprop += $prop;
                            $text_likes['likes_proportion'] = $prop.'%';
                            }

                            $like = $this->common_model->findWhere($table = 'ws_text_likes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                            $text_likes['like_status'] = $like_status;
                            

                            //likes array

                            $this->db->select('l.user_id,u.fullname,u.profile_pic');
                            $this->db->from('ws_text_likes l');
                            $this->db->join('ws_users u', 'l.user_id = u.id');
                            $this->db->where('l.text_id', $text_id);

                            $text_likes['likes_detail'] = $this->db->get()->result_array();
                            if ($text_likes['likes_detail']) {
                                foreach ($text_likes['likes_detail'] as &$detail_friend) {
                                    $fr_id = $detail_friend['user_id'];
                                    $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                    $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                    if ($like_frnd) {
                                        $detail_friend['is_friend'] = 1;
                                    } else {
                                        $detail_friend['is_friend'] = 0;
                                    }
                                }
                            }
                        } else {
                            $text_likes['likes_detail'] = array();
                            $text_likes['like_status'] = 'not liked';
                            $text_likes['likes_proportion'] = '0%';
                        }

                        //unlikes array
                        if ($unlikes_count > 0) {

                            $unlike = $this->common_model->findWhere($table = 'ws_text_unlikes', array('text_id' => $text_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                            $text_likes['unlike_status'] = $unlike_status;
                            //unlikes array

                            $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                            $this->db->from('ws_text_unlikes ul');
                            $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                            $this->db->where('ul.text_id', $text_id);

                            $text_likes['unlikes_detail'] = $this->db->get()->result_array();
                            if ($text_likes['unlikes_detail']) {
                                foreach ($text_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                    $fr_id = $unlikedetail_friend['user_id'];
                                    $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                    $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                    if ($unlike_frnd) {
                                        $unlikedetail_friend['is_friend'] = 1;
                                    } else {
                                        $unlikedetail_friend['is_friend'] = 0;
                                    }
                                }
                            }
                        } else {
                            $text_likes['unlikes_detail'] = array();
                            $text_likes['unlike_status'] = 'not disliked';
                        }
                    }
                } else {
                    $post['text'] = array();
                }

                //images data

                $img = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();
                $totalPostImageLikes =0;
                $totalPostImageUnlikes =0;
                foreach($img as $im)
                {
                    $totalPostImageLikes +=  $im['likes'];
                    $totalPostImageUnlikes +=  $im['unlikes'];
                }
                $totalPostImageVoteCount =  $totalPostImageLikes + $totalPostImageUnlikes;
                $total_imgprop = 0;
                $loop_imgindex = 0;
                if (count($img) > 0) {
                    $post['images'] = $this->db->get_where('ws_images', array('post_id' => $post['post_id']))->result_array();

                    //check like of user id
                    //echo '<pre>';print_r($post['images']);die;
                    foreach ($post['images'] as &$images_likes) {
                        $loop_imgindex++;
                        $likes_count = (int) $images_likes['likes'];

                        $unlikes_count = (int) $images_likes['unlikes'];
                        $img_id = (int) $images_likes['image_id'];

                        
                        //likes status
                        if ($likes_count > 0) {

                            $likesproportion = ( $likes_count / $totalPostImageVoteCount ) * 100;
                            if(count($img) == $loop_imgindex && $loop_imgindex > 1){
                            $images_likes['likes_proportion'] = (100 - $total_imgprop).'%';
                            }else{
                            $prop = round ( $likesproportion);
                            $total_imgprop += $prop;
                            $images_likes['likes_proportion'] = $prop.'%';
                            }

                            $like = $this->common_model->findWhere($table = 'ws_likes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                            $images_likes['like_status'] = $like_status;
                            

                            //likes array
                            $this->db->select('l.user_id,u.fullname,u.profile_pic');
                            $this->db->from('ws_likes l');
                            $this->db->join('ws_users u', 'l.user_id = u.id');
                            $this->db->where('l.image_id', $img_id);

                            $images_likes['likes_detail'] = $this->db->get()->result_array();
                            if ($images_likes['likes_detail']) {
                                foreach ($images_likes['likes_detail'] as &$detail_friend) {
                                    $fr_id = $detail_friend['user_id'];
                                    $chk_frnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                    $like_frnd = $this->common_model->getQuery($chk_frnd_query);
                                    if ($like_frnd) {
                                        $detail_friend['is_friend'] = 1;
                                    } else {
                                        $detail_friend['is_friend'] = 0;
                                    }
                                }
                            }
                        } else {
                            $images_likes['likes_detail'] = array();
                            $images_likes['like_status'] = 'not liked';
                            $images_likes['likes_proportion'] = '0%';
                        }

                        //unlikes array
                        $unlikesproportion = ( $unlikes_count / $totalPostImageVoteCount ) * 100;
                        if(count($img) == 1){
                            $prop = round ( $unlikesproportion);
                            $total_imgprop += $prop;
                            $images_likes['unlikes_proportion'] = $prop.'%';
                        }
                        if ($unlikes_count > 0) {

                            $unlike = $this->common_model->findWhere($table = 'ws_unlikes', array('image_id' => $img_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                            $images_likes['unlike_status'] = $unlike_status;
                            
                            //unlikes array

                            $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                            $this->db->from('ws_unlikes ul');
                            $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                            $this->db->where('ul.image_id', $img_id);

                            $images_likes['unlikes_detail'] = $this->db->get()->result_array();
                            if ($images_likes['unlikes_detail']) {
                                foreach ($images_likes['unlikes_detail'] as &$unlikedetail_friend) {
                                    $fr_id = $unlikedetail_friend['user_id'];
                                    $chk_unfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                    $unlike_frnd = $this->common_model->getQuery($chk_unfrnd_query);
                                    if ($unlike_frnd) {
                                        $unlikedetail_friend['is_friend'] = 1;
                                    } else {
                                        $unlikedetail_friend['is_friend'] = 0;
                                    }
                                }
                            }
                        } else {
                            $images_likes['unlikes_detail'] = array();
                            $images_likes['unlike_status'] = 'not disliked';
                        }
                    }
                } else {
                    $post['images'] = array();
                }
                //videos data
                $vid = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();

                if (count($vid) > 0) {
                    $post['video'] = $this->db->get_where('ws_videos', array('post_id' => $post['post_id']))->result_array();
                    foreach ($post['video'] as &$video_likes) {
                       
                        $likes_count = (int) $video_likes['likes'];

                        $unlikes_count = (int) $video_likes['unlikes'];
                        $vid_id = (int) $video_likes['video_id'];
                        //likes status
                        if ($likes_count > 0) {
                            $like = $this->common_model->findWhere($table = 'ws_video_likes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $like_status = (!empty($like) ) ? 'liked' : 'not liked';
                            $video_likes['like_status'] = $like_status;

                            //likes array

                            $this->db->select('l.user_id,u.fullname,u.profile_pic');
                            $this->db->from('ws_video_likes l');
                            $this->db->join('ws_users u', 'l.user_id = u.id');
                            $this->db->where('l.video_id', $vid_id);

                            $video_likes['likes_detail'] = $this->db->get()->result_array();

                            if ($video_likes['likes_detail']) {
                                foreach ($video_likes['likes_detail'] as &$vidlikedetail_friend) {
                                    $fr_id = $vidlikedetail_friend['user_id'];
                                    $chk_vidfrnd_query = "Select * From ws_friend_list where (user_id = '$user_id' AND friend_id = '$fr_id') OR (user_id = '$fr_id' AND friend_id = '$user_id') AND status = 1";
                                    $vidlike_frnd = $this->common_model->getQuery($chk_vidfrnd_query);
                                    if ($vidlike_frnd) {
                                        $vidlikedetail_friend['is_friend'] = 1;
                                    } else {
                                        $vidlikedetail_friend['is_friend'] = 0;
                                    }
                                }
                            }
                        } else {
                            $video_likes['likes_detail'] = array();
                            $video_likes['like_status'] = 'not liked';
                        }

                        //unlikes array
                        if ($unlikes_count > 0) {
                            $unlike = $this->common_model->findWhere($table = 'ws_video_unlikes', array('video_id' => $vid_id, 'user_id' => $user_id), $multi_record = false, $order = '');
                            $unlike_status = (!empty($unlike) ) ? 'disliked' : 'not disliked';
                            $video_likes['unlike_status'] = $unlike_status;

                            $this->db->select('ul.user_id,unu.fullname,unu.profile_pic');
                            $this->db->from('ws_video_unlikes ul');
                            $this->db->join('ws_users unu', 'ul.user_id = unu.id');
                            $this->db->where('ul.video_id', $vid_id);

                            $video_likes['unlikes_detail'] = $this->db->get()->result_array();

                        } else {
                            $video_likes['unlikes_detail'] = array();
                            $video_likes['unlike_status'] = 'not disliked';
                        }
                    }
                } else {
                    $post['video'] = array();
                }

                //last comment data
                $last_comment = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();


                if (count($last_comment) > 0) {
                    $post['last_comment'] = $this->db->order_by('added_at', 'DESC')->get_where('ws_comments', array('post_id' => $post['post_id']), 2)->result_array();
                    sort($post['last_comment']);
                        
                        foreach ($post['last_comment'] as &$commentDetail) {
                        $comment_sender = $this->common_model->findWhere($table = 'ws_users', array('id' => $commentDetail['user_id']), $multi_record = false, $order = '');
                        $commentDetail['sender_name'] = $comment_sender['fullname'];
                        $commentDetail['profile_pic'] = $comment_sender['profile_pic'];
                        $commentDetail['new_time'] = $this->time_elapsed_string($datetime1, $commentDetail['added_at']);

                        $commentDetail['commentuser_name'] = array();
                        if (!empty($commentDetail['mention_users'])) {
                            foreach (explode(',', $commentDetail['mention_users']) as $key => $value) {
                                $comment_detail = $this->common_model->findWhere($table = 'ws_users', array('id' => $value), $multi_record = false, $order = '');
                                $commentDetail['commentuser_name'][] = (!empty($comment_detail) ) ? $comment_detail['fullname'] : '';
                            }
                        }
                    }
                } else {
                    $post['last_comment'] = array();
                }

                //tag detail start
                $tag = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();
                if (count($tag) > 0) {
                    $post['tagged_data'] = $this->db->get_where('ws_tags', array('post_id' => $post['post_id']))->result_array();

                    //check like of user id
                    foreach ($post['tagged_data'] as &$tags) {
                        $user_id_val = (int) $tags['user_id'];
                        if ($user_id_val > 0) {
                            $tag_frnd_id = $user_id_val;
                            //echo 'yguhu'.$img_id;die;
                            $tag_frnd = $this->common_model->findWhere($table = 'ws_users', array('id' => $tag_frnd_id, 'activated' => 1), $multi_record = false, $order = '');
                            $tag_frnd_name = (!empty($tag_frnd) ) ? $tag_frnd['fullname'] : '';
                            $tags['profile_pic'] = (!empty($tag_frnd['profile_pic']) ) ? $base_url . $tag_frnd['profile_pic'] : '';
                            $tags['tag_frnd'] = $tag_frnd_name;
                        } else {
                            $tags['tag_frnd'] = '';
                        }
                    }
                } else {
                    $post['tagged_data'] = array();
                }

                $tagwrd = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                if (count($tagwrd) > 0) {
                    $post['taggedword_data'] = $this->db->get_where('ws_words', array('post_id' => $post['post_id']))->result_array();
                } else {
                    $post['taggedword_data'] = array();
                }
            }
        }
            //update group read status
            $this->common_model->delete($table = 'ws_group_read_status', $delete_data = array('group_id' => $group_id, 'user_id' => $user_id));
           //update group read status
        $data = array(
            'status' => 1,
            'base_url' => $base_url,
            'post_images_url' => $post_base_url,
            'post_count' => $post_count,
            'group_name' => $groupValue['group_name'],
            'group_owner_id' => $groupValue['group_owner'],
            'group_icon' => (!empty($groupValue['profile_pic']) ? $groupValue['profile_pic'] : ''),
            'group_memberscount' => $group_memberscount,
            'data' => $result
        );

        $this->response($data, 200);
        //individual group post
    }

    public function recent_posts_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $posts = $this->db->select('post_id , title , question , type')->order_by('added_at', 'desc')->get_where('ws_posts', array('user_id' => $user_id))->result_array();
        if($posts)
        {
            $data = array(
                        'status' => 1,
                        'data' => $posts
                    );
        }
        else
        {
            $data = array(
                        'status' => 1,
                        'message' => 'posts not found'
                    );
        }    
        $this->response($data, 200);
    }

    public function edit_post_caption_post()
    {
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $caption = $this->input->post('caption');
        $this->check_empty($caption, 'Please add caption');

        $caption = str_replace('\\"', '"', $caption);
        $post_exist = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();

        if($post_exist)
        {
            $type = $post_exist['type'];
            if($type == 'image')
            {
                $column = 'question';
            }
            else
            {
                $column = 'title';
            }    
            $this->db->where('post_id', $post_id)->update('ws_posts' ,array($column => $caption));
            $data = array(
                        'status' => 1,
                        'message' => 'caption updated successfully',
                        'caption' => $caption
                    );
        }
        else
        {
            $data = array(
                        'status' => 0,
                        'message' => 'post_id does not exist'
                    );
        }
        $this->response($data, 200);    
    }

    public function share_img_post()
    {
        $base_url = $this->baseurl;
        $this->load->helper(array('file'));
        $this->load->library('upload');

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $image1 = (isset($_FILES['collage_img'])) ? $_FILES['collage_img'] : $this->check_empty($collage_img, 'Please add collage_img');
        $post_exist = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
        if($post_exist)
        {
            if (isset($_FILES['collage_img']['name']))
            {
                //provide config values
                $file_name = $_FILES['collage_img']['name'];
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);

                $config['upload_path'] = './uploads/post_images';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '500000';
                $config['max_width'] = '52400';
                $config['max_height'] = '57680';
                $config['file_name'] = 'collage_img' . rand() . '.' . $ext;

                $this->upload->initialize($config);

                //if the profile pic could not be uploaded
                if (!$this->upload->do_upload('collage_img')) {
                    //print_r($this->upload->display_errors());
                    $this->session->set_flashdata('errors', $this->upload->display_errors());
                } else {
                    $collage_path = "uploads/post_images/" . $config['file_name'];
                }
            }   
            if($this->db->where('post_id', $post_id)->update('ws_posts' ,array('collage_img' => $collage_path)))
            {
                $postdetail = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
                $url = $base_url.$postdetail['collage_img'];
                $post_type = $postdetail['type'];
                $new_url = $this->get_tiny_url($base_url.'share3.php?poll_id='.$post_id.'&poll_type='.$post_type.'&key='.time().'&fbrefresh=1111112&amp');
                $ios_new_url = $this->get_tiny_url($base_url.'ios_share.php?poll_id='.$post_id.'&poll_type='.$post_type.'&key='.time().'&fbrefresh=1111112&amp');
                
                $post_creator = $postdetail['user_id'];
                $post_creator_detail = $this->db->get_where('ws_users', array('id' => $post_creator))->row_array();
                $fullname = $post_creator_detail['fullname'];
                $unique_name = $post_creator_detail['unique_name'];
                $invite_msg = $fullname .'@'.$unique_name.' has posted a poll on Bestest app.';
                $data = array(
                            'status' => 1,
                            'message' => 'success',
                            //'link' => $base_url.'share3.php?pid='.$post_id.'&fbrefresh=1111112&amp',
                            'old_url' => $base_url.'share3.php?pid='.$post_id.'&key='.time().'&fbrefresh=1111112&amp',
                            'path' => $base_url.$postdetail['collage_img'],
                            'link' => $new_url.'?poll_id='.$post_id.'&poll_type='.$post_type,
                            'branch_link' => $ios_new_url.'?poll_id='.$post_id.'&poll_type='.$post_type,
                            'invite_msg' => $invite_msg,
                            'invite_subject' => $fullname.' wants your opinion'
                        );
            }
            else
            {
                $data = array(
                            'status' => 0,
                            'message' => 'error'
                        );
            }    
        }
        else
        {
            $data = array(
                        'status' => 0,
                        'message' => 'post_id does not exist'
                    );
        }
        /* if the image file exists as input parameter */
        $this->response($data, 200); 
    }

    public function sharePollold_post()
    {
        $base_url = $this->baseurl;
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/share_poll_image/';
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $image = (isset($_FILES['image'])) ? $_FILES['image'] : $this->check_empty($image, 'Please add image');
        //$image1 = (isset($_FILES['image1'])) ? $_FILES['image1'] : '';
        $post_exist = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
        if($post_exist)
        {
            $config['upload_path'] = './uploads/post_images/'; //The path where the image will be save
            $config['allowed_types'] = 'gif|jpg|png|jpeg'; //Images extensions accepted
            $config['max_size'] = '100000'; //The max size of the image in kb's
            //$config['max_height'] = 768;
            $config['file_name'] = 'collage_img' .rand();
            $this->load->library('upload', $config); //Load the upload CI library

            if ($image) {
                if ($this->upload->do_upload('image')) {
                    $file_info = $this->upload->data('image');
                    $file_name = $file_info['file_name'];
                    $full_path = $file_info['full_path'];
                    $this->aws_collage_upload($file_name , $full_path);
                    $collage_path = "uploads/post_images/" . $file_name;
                } else {
                    //echo '1';print_r($this->upload->display_errors());
                    $data = array('status' => 0, 'message' => 'image could not be saved');
                    $this->response($data, 200);
                }
            }
            
            if($this->db->where('post_id', $post_id)->update('ws_posts' ,array('collage_img' => $collage_path)))
            {
                $postdetail = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
                $imgUrl = $base_url.$postdetail['collage_img'];
                $cdnUrl = $post_base_url.$file_name ;
                $data = array(
                            'status' => 1,
                            'message' => 'success',
                            'shareImgUrl' => $cdnUrl
                            //'img_link' => $imgUrl
                        );
            }
            else
            {
                $data = array(
                            'status' => 0,
                            'message' => 'error'
                        );
            }   
        }
        else
        {
            $data = array(
                        'status' => 0,
                        'message' => 'post_id does not exist'
                    );
        }
        /* if the image file exists as input parameter */
        $this->response($data, 200); 
    }

    function convertImagePoll($count , $postImages = array())
    {
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';
        $outputfile_name = 'collagenew_img' .rand().'.png';
        $file_path = '/var/www/html/bestest_test/uploads/post_images/';
        $filefinal = $file_path.$outputfile_name;
        //$chk = exec("convert /var/www/html/bestest_test/uploads/post_images/new_img_123314973952223.jpg -geometry 600x600  /var/www/html/bestest_test/uploads/post_images/new_img_123314973952222.jpg -geometry 600x600 +append -geometry 1200x600  $filefinal");
        //die;
        if($count == 1){
            $image1 = $post_base_url.$postImages[0]['image_name'];
            $please_img = $file_path.'please.png';
            //$collage_img = exec("convert $image1  -crop 1200x600+0+0 +repage  $filefinal");
            $collage_img = exec("convert $please_img -resize 600x600^ -crop 600x600+0+0  $image1  -resize 600x600^ -crop 600x600+0+0 +append -geometry 1200x600\>  $filefinal");
            $this->aws_collage_upload($outputfile_name , $filefinal);
        }elseif($count == 2) {
            $image1 = $post_base_url.$postImages[0]['image_name'];
            $image2 = $post_base_url.$postImages[1]['image_name'];
            $collage_img = exec("convert $image1 -gravity northwest -background  white -splice 3X0 -resize 600x600^ -crop 600x600+0+0  $image2 -gravity northwest -resize 600x600^ -crop 600x600+0+0   +append -geometry 1200x600  $filefinal");
            $this->aws_collage_upload($outputfile_name , $filefinal);
        }elseif($count == 3 || $count == 4){
            $image1 = $post_base_url.$postImages[0]['image_name'];
            $image2 = $post_base_url.$postImages[1]['image_name'];
            $seemore = $file_path.'seemoretest.png';
            $filefinal1=$file_path.'compose1'.rand().'.png';
           // $filefinal2=$file_path.'compsetest'.rand().'.png';
            
          // $collage_img = exec("convert $image1   $image2   +append -resize 1200X1200\! $filefinal1");
           $collage_img = exec("convert $image1 -gravity northwest -background  white  -splice 3X0 -resize 600x600^ -crop 600x600+0+0   -background white -splice 5X0   $image2 -gravity northwest  -resize 600x600^ -crop 600x600+0+0   +append -geometry 1200x600  $filefinal1");
           $collage_img = exec("composite -gravity south $seemore   $filefinal1  -geometry 1200X600 $filefinal");
          // $collage_img = exec("convert    $filefinal2   -resize 1200X^> $filefinal");
            $this->aws_collage_upload($outputfile_name , $filefinal);
        }
        return $outputfile_name;
    }

    function convertTextPoll($postImage)
    {
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';
        $outputfile_name = 'collagenew_img' .rand().'.png';
        $file_path = '/var/www/html/bestest_test/uploads/post_images/';
       // $filefinal = $file_path.$outputfile_name;
        $filefinal = $file_path.'label_size'.rand().'.jpg';
        $seemoretest=$file_path.'seemoretest1.png';
        $filefinal1=$file_path.'textpoll'.rand().'.png';

        $image1 = $post_base_url.$postImage;
        $please_img = $file_path.'please.png';
        //$collage_img = exec("convert $image1  -crop 1200x600+0+0 +repage  $filefinal");
        //convert -gravity  north -crop 1200X600+0+0 
        $collage_img = exec("convert  $image1  -crop 1200X600+0+0 +repage -geometry 1200X\! $filefinal1");
        $collage_img = exec("composite  -gravity south  $seemoretest $filefinal1  $filefinal");
        $this->aws_collage_upload($outputfile_name , $filefinal);
        return $outputfile_name;
    }

    function convertTitleTextPoll($title , $count)
    {
        //echo $title;
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/post_images/';
        $outputfile_name = 'labelsizenew' .rand().'.png';
        $file_path = '/var/www/html/bestest_test/uploads/post_images/';
       $filefinal = $file_path.$outputfile_name;
        $filefinal1 = $file_path.'label_size1'.rand().'.jpg';
        $clickimage=$file_path.'clicknew.png';
        $fontimage=$file_path.'league-gothic.regular.ttf';
        //echo $count;
        $titlenew = str_replace("'"," ", $title);

        //$titlenew = mb_convert_encoding($titlenew1, "UTF-8");
        //$titlenew = json_decode('"'.$titlenew1.'"');
        //$titlenew =  mb_convert_encoding($titlenew1, 'UTF-8', 'HTML-ENTITIES');
        //$titlenew = utf8_encode($titlenew1);
        $polling_bestest="Polling\x20\x20 on Bestest";
        if($count > 0 && $count <= 70){
            $titlenew = $titlenew;
            $collage_img = exec("convert -quality 100 -density 90 -background '#2c8fd2' -font $fontimage -fill white -size 960X484+20+20 -kerning 2  -interword-spacing 8 -interline-spacing  5 -pointsize 50 -gravity center caption:'$titlenew' -bordercolor '#2c8fd2' -border 80X20 $filefinal1");
             $collage_img=exec("composite -gravity south $clickimage -bordercolor '#2c8fd2' -border 35X34 $filefinal1 $filefinal");
          
            //echo $count.'x';die;
        }elseif($count >70 && $count <= 150){
            $titlenew = $titlenew;
            $collage_img = exec("convert -quality 100 -density 90 -background '#2c8fd2' -font $fontimage -fill white -size 960X510+20+20 -kerning 2  -interword-spacing 8 -interline-spacing  6 -pointsize 45 -gravity center caption:'$titlenew' -bordercolor '#2c8fd2' -border 80X20 $filefinal1");
             $collage_img=exec("composite -gravity south $clickimage -bordercolor '#2c8fd2' -border 25X25 $filefinal1 $filefinal");
            //echo $count.'y';die;
        }else{
            $titlenew = $titlenew;
            $collage_img =exec("convert -quality 100 -density 90 -background '#2c8fd2' -font $fontimage -fill white -size 960X520+20+20 -interword-spacing 8 -interline-spacing  6 -kerning 2  -pointsize 38 -gravity center caption:'$titlenew' -bordercolor '#2c8fd2' -border 80X20 $filefinal1");
             $collage_img=exec("composite -gravity south $clickimage -bordercolor '#2c8fd2' -border 30X20 $filefinal1 $filefinal");
        }
        $this->aws_collage_upload($outputfile_name , $filefinal);
        return $outputfile_name;
    }

    public function sharePoll_post()
    {
        $base_url = $this->baseurl;
        $post_base_url = 'http://d1lvl2bc2ytvwe.cloudfront.net/developmentcdn/images/share_poll_image/';
        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');
        $image = (isset($_FILES['image'])) ? $_FILES['image'] : '';
        $post_exist = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
        if($post_exist)
        {
            if($post_exist['type'] == 'text'){   //text
                /*$this->check_empty($image, 'Please add image');

                $config['upload_path'] = './uploads/post_images/'; //The path where the image will be save
                $config['allowed_types'] = 'gif|jpg|png|jpeg'; //Images extensions accepted
                $config['max_size'] = '100000'; //The max size of the image in kb's
                //$config['max_height'] = 768;
                $config['file_name'] = 'collagetext_img' .rand();
                $this->load->library('upload', $config); //Load the upload CI library

                if ($image) {
                    if ($this->upload->do_upload('image')) {
                        $file_info = $this->upload->data('image');
                        $file_name = $file_info['file_name'];
                        $full_path = $file_info['full_path'];
                        $this->aws_upload($file_name , $full_path);
                        $textImgPath = "uploads/post_images/" . $file_name;
                        $convertedImage = $this->convertTextPoll($file_name);
                        if($convertedImage != ''){
                            $collage_path = 'uploads/post_images/'.$convertedImage;
                            $cdnUrl = $post_base_url.$convertedImage ;
                        }else{
                            $data = array(
                                            'status' => 0,
                                            'message' => 'image error'
                                        );
                        }
                    } else {
                        //echo '1';print_r($this->upload->display_errors());
                        $data = array('status' => 0, 'message' => 'image could not be saved');
                        $this->response($data, 200);
                    }
                }else{*/
                    $count = strlen($post_exist['title']);
                    $title = json_decode('"'.$post_exist['title'].'"');
                    $convertedImage = $this->convertTitleTextPoll($title ,$count);
                    if($convertedImage != ''){
                        $collage_path = 'uploads/post_images/'.$convertedImage;
                        $cdnUrl = $post_base_url.$convertedImage ;
                    }else{
                        $data = array(
                                        'status' => 0,
                                        'message' => 'image error'
                                    );
                    }
              //  }
            }else{                               //image
                $postImages = $this->db->get_where('ws_images' , array('post_id' => $post_id))->result_array();
                $countImages = count($postImages);
                if($countImages > 0){
                    $convertedImage = $this->convertImagePoll($countImages , $postImages);
                    if($convertedImage != ''){
                        $collage_path = 'uploads/post_images/'.$convertedImage;
                        $cdnUrl = $post_base_url.$convertedImage ;
                    }else{
                        $data = array(
                                        'status' => 0,
                                        'message' => 'image error'
                                    );
                    }
                }else{
                    $data = array(
                            'status' => 0,
                            'message' => 'No images exist'
                        );
                }
            }
            if($this->db->where('post_id', $post_id)->update('ws_posts' ,array('collage_img' => $collage_path)))
            {
                $postdetail = $this->db->get_where('ws_posts', array('post_id' => $post_id))->row_array();
                $Description = ($postdetail['type'] == 'text') ? json_decode('"'.$postdetail['title'].'"') : json_decode('"'.$postdetail['question'].'"');
                $imgUrl = $base_url.$postdetail['collage_img'];
                $user_exist = $this->db->get_where('ws_users', array('id' => $postdetail['user_id']))->row_array();
                $pollTitle = ($postdetail['type'] == 'text') ? 'A good question...vote now!' : json_decode('"'.$user_exist['fullname'].'"').' wants to know:';
                $pollDescription = ($postdetail['type'] == 'text') ? 'Simple, social polling on the Bestest app' : $Description.' CLICK TO VOTE';
                $data = array(
                            'status' => 1,
                            'message' => 'success',
                            'shareImgUrl' => $cdnUrl,
                            'pollTitle' => $pollTitle,
                            'pollDescription' => $pollDescription,
                            //'shareImgUrl' => $base_url.'uploads/post_images/label_size.jpg'
                            //'img_link' => $imgUrl
                        );
            }
            else
            {
                $data = array(
                            'status' => 0,
                            'message' => 'error'
                        );
            }
        }
        else
        {
            $data = array(
                        'status' => 0,
                        'message' => 'post_id does not exist'
                    );
        }    
        $this->response($data, 200); 
    }

    public function aws_collage_upload($file_name , $full_path)
    {
        //$data = array('upload_data' => $this->upload->data());
        //echo $file_name;
        //echo 'hhjh';
        //echo $full_path;die;
        $this->load->library('s3');
        // CONSTRUCT URI
        $uri = "developmentcdn/images/share_poll_image/".$file_name;
        $bucketName = "developmentcdn";

        // DISPLAY DATA
        //echo "<pre>";
       // print_r($data);

        // PUT with custom headers:
        $put = S3::putObject(
            S3::inputFile($full_path),
            $bucketName,
            $uri,
            S3::ACL_PUBLIC_READ,
            array(),
            array( // Custom $requestHeaders
                "Cache-Control" => "max-age=315360000",
                "Expires" => gmdate("D, d M Y H:i:s T", strtotime("+5 years"))
            )
        );
        //var_dump($put);die;
        //$img_baseurl = 'http://d1lvl2bc2ytvwe.cloudfront.net/';
        //echo $img_baseurl.$uri; 
        //return $uri;
    }

    //gets the data from a URL  
    public function get_tiny_url($url)  {  
        $ch = curl_init();  
        $timeout = 5;  
        curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url);  
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);  
        $data = curl_exec($ch);  
        curl_close($ch);  
        return $data;  
    }

    /**
     * **********************************************************************************************
     * Function Name : send_notification                                                           *
     * Functionality : send notification                                                           *    
     * @author       : pratibha sinha                                                              *
     * @param        : int receiver, sender,notification_type,chat_msg,group_id,post_id,msg_id     *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    function send_notification_old($receiver, $sender, $notification_type, $chat_msg = '', $group_id = '', $post_id = '', $msg_id = '', $follow_id = '' , $groupALL = '') {
        $post_type = '';
        $message = '';
        $type = $this->notification_type($notification_type);
        //group_detail
        if (!empty($group_id)) {
            $groupData = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_id), $multi_record = false, $order = '');
        }
        $simple_chatmsg = '';

        if (!empty($chat_msg)) {
            $simple_chatmsg = $chat_msg;
        }

        if (!empty($post_id)) {
            $POST_ID = $post_id;
            $postdata = $this->common_model->findWhere($table = 'ws_posts', $where_data = array('post_id' => $POST_ID), $multi_record = false, $order = '');
            $post_type = $postdata['type'];
        }

        $MSG_ID = '';
        if (!empty($msg_id)) {
            $MSG_ID = $msg_id;
        }

        $FOLLOW_ID = '';
        if (!empty($follow_id)) {
            $FOLLOW_ID = $follow_id;
        }

        $senderData = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $sender, 'activated' => 1), $multi_record = false, $order = '');
        $message = $this->notification_alert_msg($type, $senderData['fullname'], $POST_ID , $groupALL , $receiver);

        $receiver_friends_query = "select * from ws_friend_list where (user_id = '$receiver' AND friend_id = '$sender') OR (user_id = '$sender' AND friend_id = '$receiver')";
        $receiver_frnds_data = $this->common_model->getQuery($receiver_friends_query);

        if ($receiver_frnds_data) {
            if ($type = "simple_chat" || $type = "group_chat" || $type = "unlike" || $type = "follow" || $type = "imgtaguser") {
                $sound_data = 1;
            } else {
                $sound_data = $this->sound_status_check($receiver, $type, 'friend');
            }
        } else {
            if ($type = "simple_chat" || $type = "group_chat" || $type = "unlike" || $type = "follow" || $type = "imgtaguser") {
                $sound_data = 1;
            } else {
                $sound_data = $this->sound_status_check($receiver, $type, 'all');
            }
        }

        if ($sound_data == 0) {
            $sound = '';
        } else {
            $sound = 'default';
        }

        $app_id = '7rq6bHYTci2BeqswUOUA8BoPyxJ2FuAcZFLvHosW';
        $rest_key = '3DjNDFFWJfK0XkJV4TJkb6N1eUe7hUycTgkaUSzx';
        $master_key = '6I8j3gUvoONmUiG5GXaG6pUitIclB03yUaEym9Q6';

        // Load library in your controller or model file where you want to use Parse.
        $this->load->library('parse-php-sdk/src/Parse/ParseClient');
        $this->parseclient->initialize($app_id, $rest_key, $master_key);

        // Import corresponding file where you'll be using the classes.
        $this->load->library('parse-php-sdk/src/Parse/ParsePush');

        //group_detail
        $base_url = $this->baseurl;
        if (!empty($group_id)) {
            $bestest_data = array(
                'id' => $group_id,
                'name' => $groupData['group_name'],
                'pic' => $senderData['profile_pic'],
                'notification_type' => $notification_type,
                'chatmsg' => $simple_chatmsg,
                'post_id_val' => $POST_ID,
                'post_type' => $post_type,
                'msg_id_val' => $MSG_ID
            );
        } else {
            $bestest_data = array(
                'id' => $sender,
                'name' => $senderData['fullname'],
                'pic' => $senderData['profile_pic'],
                'notification_type' => $notification_type,
                'chatmsg' => $simple_chatmsg,
                'post_id_val' => $POST_ID,
                'post_type' => $post_type,
                'msg_id_val' => $MSG_ID
            );
        }

        //print_r($bestest_data);die;
        $channel = "bestest" . $receiver;

        $data = array(
            "bestest_data" => $bestest_data,
            "alert" => $message,
            "badge" => "Increment",
            "content-available" => "1",
            "sound" => $sound
        );

        // Push to Channels
        $final = $this->parsepush->send(array(
            "channels" => [$channel],
            "data" => $data
        ));
    }

    public function test_not_post()
    {
        $receiver_id = $this->input->post('receiver_id');
        $this->check_empty($receiver_id, 'Please add receiver_id');

        $post_id = $this->input->post('post_id');
        $this->check_empty($post_id, 'Please add post_id');

        $this->send_notification($receiver_id, '118', 'like', '', '', $post_id);
    }

    function send_notification($receiver, $sender, $notification_type, $chat_msg = '', $group_id = '', $post_id = '', $msg_id = '', $follow_id = '' , $groupALL = '') {
        
        //find device type and device token
        $Recv_token_type = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $receiver, 'activated' => 1), $multi_record = false, $order = '');
        $deviceType = $Recv_token_type['deviceType'];
        $deviceToken = $Recv_token_type['deviceToken'];

        if($deviceToken != '')
        {
            $post_type = '';
            $message = '';
            $type = $this->notification_type($notification_type);
            //group_detail
            if (!empty($group_id)) {
                $groupData = $this->common_model->findWhere($table = 'ws_groups', $where_data = array('id' => $group_id), $multi_record = false, $order = '');
            }
            $simple_chatmsg = '';

            if (!empty($chat_msg)) {
                $simple_chatmsg = $chat_msg;
            }

            if (!empty($post_id)) {
                $POST_ID = $post_id;
                $postdata = $this->common_model->findWhere($table = 'ws_posts', $where_data = array('post_id' => $POST_ID), $multi_record = false, $order = '');
                $post_type = $postdata['type'];
            }

            $MSG_ID = '';
            if (!empty($msg_id)) {
                $MSG_ID = $msg_id;
            }

            $FOLLOW_ID = '';
            if (!empty($follow_id)) {
                $FOLLOW_ID = $follow_id;
            }

            $senderData = $this->common_model->findWhere($table = 'ws_users', $where_data = array('id' => $sender, 'activated' => 1), $multi_record = false, $order = '');
            $message = $this->notification_alert_msg($type, $senderData['fullname'], $POST_ID , $groupALL , $receiver);

            /*$receiver_friends_query = "select * from ws_friend_list where (user_id = '$receiver' AND friend_id = '$sender') OR (user_id = '$sender' AND friend_id = '$receiver')";
            $receiver_frnds_data = $this->common_model->getQuery($receiver_friends_query);

            if ($receiver_frnds_data) {
                if ($type = "simple_chat" || $type = "group_chat" || $type = "unlike" || $type = "follow" || $type = "imgtaguser") {
                    $sound_data = 1;
                } else {
                    $sound_data = $this->sound_status_check($receiver, $type, 'friend');
                }
            } else {
                if ($type = "simple_chat" || $type = "group_chat" || $type = "unlike" || $type = "follow" || $type = "imgtaguser") {
                    $sound_data = 1;
                } else {
                    $sound_data = $this->sound_status_check($receiver, $type, 'all');
                }
            }

            if ($sound_data == 0) {
                $sound = '';
            } else {
                $sound = 'default';
            }*/

            $sound = 'default';
            //group_detail
            $base_url = $this->baseurl;

            $notification = $this->db->order_by('added_at', 'desc')->get_where('ws_notifications', array('receiver_id' => $receiver, 'status' => 0))->result_array();
            $notification_count = count($notification);
            if (!empty($group_id)) {
                $bestest_data = array(
                    'id' => $group_id,
                    'name' => $groupData['group_name'],
                    'pic' => $senderData['profile_pic'],
                    'notification_type' => $notification_type,
                    'chatmsg' => $simple_chatmsg,
                    'post_id_val' => $POST_ID,
                    'post_type' => $post_type,
                    'msg_id_val' => $MSG_ID,
                    'notification_count' => $notification_count
                );
            } else {
                $bestest_data = array(
                    'id' => $sender,
                    'name' => $senderData['fullname'],
                    'pic' => $senderData['profile_pic'],
                    'notification_type' => $notification_type,
                    'chatmsg' => $simple_chatmsg,
                    'post_id_val' => $POST_ID,
                    'post_type' => $post_type,
                    'msg_id_val' => $MSG_ID,
                    'notification_count' => $notification_count
                );
            }
            $data = array(
                "bestest_data" => $bestest_data,
                "alert" => $message,
                "badge" => "Increment",
                "content-available" => "1",
                "sound" => $sound
            );

            //print_r($senderData);die;
            $this->sendRegistryNotification($message , $deviceToken , $deviceType , $sound , $bestest_data);
        }
    }
    

    function sendRegistryNotification($message, $deviceToken, $deviceType, $sound , $bestest_data = array())
    {              
            $projectLocation = explode('index.php',$_SERVER['PHP_SELF']);
            $projectFolder = $_SERVER['DOCUMENT_ROOT'].$projectLocation[0];
            $deviceType = strtolower($deviceType);
            switch ($deviceType) {
                case 'ios':
                    $tHost = 'gateway.sandbox.push.apple.com';
                    //$tHost = 'gateway.push.apple.com';
                    $tPort = 2195;
                    // Provide the Certificate and Key Data.
                    $tCert = $projectFolder.'uploads/ck.pem';
                    $tPassphrase = 'welcome';
                    // Provide the Device Identifier (Ensure that the Identifier does not have spaces in it).
                    // Replace this token with the token of the iOS device that is to receive the notification.
                    $tToken = $deviceToken;
                    // The message that is to appear on the dialog.
                    //header('Content-type: text/html; charset=utf-8');
                    //echo $message;
                    $utf8 = '"'.$message.'"';
                    //echo $utf8;
                    //$utf8string = json_decode('"\ud83d\ude32\ud83d\ude32"');
                    $utf8string = json_decode($utf8);
                    
                    $tAlert = $utf8string;
                    //echo $tAlert;
                    // The Badge Number for the Application Icon (integer >=0).
                    $tBadge = 1;
                    // Audible Notification Option.
                    $tSound = $sound;
                    // The content that is returned by the LiveCode "pushNotificationReceived" message.
                    $tPayload = '{msg":"You have new notification"}';
                    // Create the message content that is to be sent to the device.

                    //badge change
                    $query = "SELECT badgecount FROM ws_pushnotifications WHERE device_token = '{$deviceToken}'";
                    $query = $this->db->query($query);
                    $row = $query->row_array();
                    $updatequery = "update ws_pushnotifications set badgecount=badgecount+1 WHERE device_token ='{$deviceToken}'";
                    $updatequery = $this->db->query($updatequery);
                    //badge change

                    $tBody['aps'] = array (
                            'alert' => $tAlert,
                            'badge' => $tBadge,
                            'badge' => $row["badgecount"]+1,
                            'sound' => $tSound,
                            'bestest_data' => $bestest_data
                        );
                    $tBody ['payload'] = $tPayload;

                    // Encode the body to JSON.
                    $tBody = json_encode ($tBody);
                    // Create the Socket Stream.
                    $tContext = stream_context_create ();
                    stream_context_set_option ($tContext, 'ssl', 'local_cert', $tCert);
                    // Remove this line if you would like to enter the Private Key Passphrase manually.
                    stream_context_set_option ($tContext, 'ssl', 'passphrase', $tPassphrase);
                    // Open the Connection to the APNS Server.
                    $tSocket = stream_socket_client ('ssl://'.$tHost.':'.$tPort, $error, $errstr, 30, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $tContext);
                    // Check if we were able to open a socket.
                    if (!$tSocket)
                    exit ("APNS Connection Failed: $error $errstr" . PHP_EOL);
                    // Build the Binary Notification.
                    $tMsg = chr (0) . chr (0) . chr (32) . pack ('H*', $tToken) . pack ('n', strlen ($tBody)) . $tBody;
                    // Send the Notification to the Server.
                    //echo $tMsg;die;
                    $tResult = fwrite ($tSocket, $tMsg, strlen ($tMsg));
                    //$errorResponse = @fread($tSocket, 6);
                    //echo $tResult;die;
                    if ($tResult != ''){
                        /*$res = json_decode($tBody);
                        //check
                        $k = print_r( $res, true );

                        $myFile = "/var/www/html/bestest_test/testFile.txt";

                        $fh = fopen($myFile, 'a') or die("can't open file");

                        $stringData = $k.'<br/>------------------------<br/>';

                        fwrite($fh, $stringData);

                        print $stringData;*/

                        //check

                        //echo 'Delivered Message to APNS' . PHP_EOL;
                    }else{
                        echo 'Could not Deliver Message to APNS' . PHP_EOL;
                    }    
                    // Close the Connection to the Server.
                    fclose ($tSocket);               
                    break;
                case 'android':
                   // Set POST variables
                   $url = 'https://fcm.googleapis.com/fcm/send';
                   $apiKey = 'AIzaSyDxNZTYQwJIhWA6jyr2jVaRRiZQDVd4oZM';

                   $priority = 'high';

                   $notification1 = array(       //// when application close then post field 'notification' parameter work
                    'body'  => $message,
                    'sound' => $sound,
                    );

                   $bestest_data['title'] = $message;
                   $fields = array(
                       'registration_ids' => array($deviceToken),
                       'data' => $bestest_data,
                      // 'notification' => $notification1,
                       'priority' => $priority
                   );
                    $headers = array(
                       'Authorization: key=' . $apiKey,
                       'Content-Type: application/json'
                   );  // Key For your App(find in your project which was created in <a href="https://console.developers.google.com/project" target="_blank">https://console.developers.google.com/project</a>
                   // Open connection
                   $ch = curl_init();
                   // Set the url, number of POST vars, POST data
                   curl_setopt($ch, CURLOPT_URL, $url);
                   curl_setopt($ch, CURLOPT_POST, true);
                   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                   // Disabling SSL Certificate support temporarly
                   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                   curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                   // Execute post
                   $result = curl_exec($ch);
                   //var_dump($result);
                   if ($result === FALSE) {
                    //   die('Curl failed: ' . curl_error($ch));
                   }
                   // Close connection
                   curl_close($ch);
                   return TRUE;
                    break;
            }
            return true;
        }

        public function pushAPNS_post()
       {
            $projectLocation = explode('index.php',$_SERVER['PHP_SELF']);
            $projectFolder = $_SERVER['DOCUMENT_ROOT'].$projectLocation[0];
            //$device_token = '2168ab604e183611cd8d0b4cd2a1f4b58c6824c152b250feb96b4f06df78c386';
            $device_token = '060178113f11d5f0c591a98297b4edb78c1d94d2ab7e05c5ec5beb945c3bda6c';
            // Provide the Host Information.
            $base_url = $this->baseurl;
            //$tHost = 'gateway.sandbox.push.apple.com';
            $tHost = 'gateway.push.apple.com';
            $tPort = 2195;
            // Provide the Certificate and Key Data.
            $tCert = $projectFolder.'uploads/pushcert.pem';
            // Provide the Private Key Passphrase (alternatively you can keep this secrete
            // and enter the key manually on the terminal -> remove relevant line from code).
            // Replace XXXXX with your Passphrase
            $tPassphrase = '';
            // Provide the Device Identifier (Ensure that the Identifier does not have spaces in it).
            // Replace this token with the token of the iOS device that is to receive the notification.
            $tToken = $device_token;
            // The message that is to appear on the dialog.
            $empresa = "bestest_test";
            $tAlert = $empresa . ' notification!';
            // The Badge Number for the Application Icon (integer >=0).
            $tBadge = 1;
            // Audible Notification Option.
            $tSound = 'default';
            // The content that is returned by the LiveCode "pushNotificationReceived" message.
            $tPayload = '{msg":"You have new notification"}';
            // Create the message content that is to be sent to the device.

            $tBody['aps'] = array (
                    'alert' => $tAlert,
                    'badge' => $tBadge,
                    'sound' => $tSound,
                   // 'type' => $type,
                   // 'book_id' => $book_id
                );
            $tBody ['payload'] = $tPayload;

            // Encode the body to JSON.
            $tBody = json_encode ($tBody);
            // Create the Socket Stream.
            $tContext = stream_context_create ();
            stream_context_set_option ($tContext, 'ssl', 'local_cert', $tCert);
            // Remove this line if you would like to enter the Private Key Passphrase manually.
            stream_context_set_option ($tContext, 'ssl', 'passphrase', $tPassphrase);
            // Open the Connection to the APNS Server.
            $tSocket = stream_socket_client ('ssl://'.$tHost.':'.$tPort, $error, $errstr, 30, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $tContext);
            // Check if we were able to open a socket.
            if (!$tSocket)
            exit ("APNS Connection Failed: $error $errstr" . PHP_EOL);
            // Build the Binary Notification.
            $tMsg = chr (0) . chr (0) . chr (32) . pack ('H*', $tToken) . pack ('n', strlen ($tBody)) . $tBody;
            // Send the Notification to the Server.
            $tResult = fwrite ($tSocket, $tMsg, strlen ($tMsg));
            //echo $tResult;die;
            if ($tResult != ''){
                /*$res = json_decode($tBody);
                //check
                $k = print_r( $res, true );

                $myFile = "/var/www/html/day_stay/testFile.txt";

                $fh = fopen($myFile, 'a') or die("can't open file");

                $stringData = $k.'<br/>------------------------<br/>';

                fwrite($fh, $stringData);

                print $stringData;*/

                //check

                //echo 'Delivered Message to APNS' . PHP_EOL;
            }else{
                echo 'Could not Deliver Message to APNS' . PHP_EOL;
            }    
            // Close the Connection to the Server.
            fclose ($tSocket);
    }

    /**
     * **********************************************************************************************
     * Function Name : create_image                                                                *
     * Functionality : add playbutton on image                                                     *    
     * @author       : pratibha sinha                                                              *
     * @param        : fisrt_img, secound_img,saving_path,name,position,padding,isFrame,top        *
     * revision 0    : author changes_made                                                         *
     * **********************************************************************************************
     * */
    public function create_image($fisrt_img, $secound_img, $saving_path, $name, $position = 'center', $padding = '0', $isFrame = FALSE, $top = '') {

        //getting file extension from first_img
        $path_parts = pathinfo($fisrt_img);

        $first_img_ext = $path_parts['extension'];
        $savetoloc = $saving_path . $name;

        if ($first_img_ext == 'png') {
            $im = imagecreatefrompng($fisrt_img);
        } else {
            $im = imagecreatefromjpeg($fisrt_img);
        }

        // Location of main first layer image with respect to code file
        imagealphablending($im, true);

        $stamp = imagecreatefrompng($secound_img);  // second layer image sorce with respect to code file
        imagesavealpha($stamp, true);

        $fisrt_img_width = imagesx($im);
        $fisrt_img_height = imagesy($im);

        $secound_img_width = imagesx($stamp);
        $secound_img_height = imagesy($stamp);

        switch ($position) {
            case 'center':
                $stamp_pos_x = ($fisrt_img_width - $secound_img_width) / 2;
                $stamp_pos_y = ($fisrt_img_height - $secound_img_height) / 2;
                break;
            case 'left-top':
                $stamp_pos_x = 0 + $padding;
                $stamp_pos_y = 0 + $padding + $top;
                break;
            case 'left-bottom':
                $stamp_pos_x = 0 + $padding;
                $stamp_pos_y = $fisrt_img_height - $secound_img_height - $padding;
                break;
            case 'right-top':
                $stamp_pos_x = $fisrt_img_width - $secound_img_width - $padding;
                $stamp_pos_y = 0 + $padding + $top;
                break;
            case 'right-bottom':
                $stamp_pos_x = $fisrt_img_width - $secound_img_width - $padding;
                $stamp_pos_y = $fisrt_img_height - $secound_img_height - $padding;
                break;

            default:
                # code...
                break;
        }
        // calculating height and width according to aspect ratio of orignal image
       
        //echo $fisrt_img_width;

        if ($isFrame == TRUE) {
            $thumb_img = imagecreatetruecolor($fisrt_img_width, $fisrt_img_height);
            imagefill($thumb_img, 0, 0, 0x7fff0000);
            imagealphablending($thumb_img, true);
            imagecopyresampled($thumb_img, $stamp, 0, 0, 0, 0, $fisrt_img_width, $fisrt_img_height, $secound_img_width, $secound_img_height);
            imagealphablending($thumb_img, true);

            imagecopy($im, $thumb_img, $stamp_pos_x, $stamp_pos_y, 00, 00, $fisrt_img_width, $fisrt_img_height);                   // adding size and location of secound layer image  
        } else {
            imagecopy($im, $stamp, $stamp_pos_x, $stamp_pos_y, 00, 00, $secound_img_width, $secound_img_height);                   // adding size and location of secound layer image
        }

        $thumb = imagecreatetruecolor($fisrt_img_width, $fisrt_img_height);
        imagecopyresized($thumb, $im, 0, 0, 0, 0, $fisrt_img_width, $fisrt_img_height, imagesx($im), imagesy($im));
        // Save the image to file and free memory
        ob_start();
        //header('Content-Type: image/jpeg');
        imagepng($thumb);
        //imagepng($im);
        $image_data = ob_get_contents();
        ob_end_clean();

        $source = imagecreatefromstring($image_data);       // creating image
        $imageSave = imagejpeg($source, $savetoloc, 100);              /// adding name and saving    
        imagedestroy($source);          // destroying object
    }

    public function image_resize($image_name, $width, $height, $folder) {
        $this->load->library('image_lib');
        $config['image_library'] = 'gd2';
        $config['source_image'] = '././uploads/' . $folder . '/' . $image_name;
        $config['create_thumb'] = False;
        $config['maintain_ratio'] = TRUE;
        $config['width'] = $width;
        $config['height'] = $height;
        $config['new_image'] = $width . '_' . $image_name;
        $this->load->library('image_lib', $config);
        $this->image_lib->clear();
        $this->image_lib->initialize($config);
        $this->image_lib->resize();
        if (!$this->image_lib->resize()) {
            echo $this->image_lib->display_errors();
        }
        $this->image_lib->clear();
        $image = explode('/', $config['new_image']);
        return $config['new_image'];
    }

    /* api made on 10 april 2015  >>>>>>>> Paramjit singh*/
    public function following_friend_list_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');
        
        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');

        $base_url = $this->baseurl;
        $user_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id), $multi_record = false, $order = '');
        $friend_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $friend_id), $multi_record = false, $order = '');
        if ($user_data && $friend_data) {
            $this->db->where('friend_id', $friend_id);
            $following = $this->db->get('ws_follow')->result_array();
            $DATA = array();
            if ($following) {
                foreach ($following as $list) {
                    $following_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['user_id']), $multi_record = false, $order = '');

                    //chk follow
                    $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $list['user_id']), $multi_record = false, $order = '');

                    $DATA[] = array(
                        'following_id' => $list['user_id'],
                        'following_name' => (!empty($following_data['fullname']) ? $following_data['fullname'] : ''),
                        'following_pic' => (!empty($following_data['profile_pic']) ? $base_url . $following_data['profile_pic'] : ''),
                        'unique_name' => (!empty($following_data['unique_name']) ? $following_data['unique_name'] : ''),
                        'phone' => (!empty($following_data['phone']) ? $following_data['phone'] : ''),
                        'email' => (!empty($following_data['email']) ? $following_data['email'] : ''),
                        'follow' => (!empty($chk_follow_data) ? '1' : '0'),
                    );
                }
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            } else {
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id or frind id does not exist'
            );
        }
        $this->response($data, 200);
    }
    
    
    public function follower_friend_list_post() {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');
        
        $friend_id = $this->input->post('friend_id');
        $this->check_empty($friend_id, 'Please add friend_id');
        
        $base_url = $this->baseurl;
        $user_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $user_id), $multi_record = false, $order = '');
        $friend_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $friend_id), $multi_record = false, $order = '');
        if ($user_data && $friend_data) {
            $this->db->where('user_id', $friend_id);
            $follower = $this->db->get('ws_follow')->result_array();
            $DATA = array();
            if ($follower) {
                foreach ($follower as $list) {
                    $follower_data = $this->common_model->findWhere($table = 'ws_users', array('id' => $list['friend_id']), $multi_record = false, $order = '');

                    //chk follow
                    $chk_follow_data = $this->common_model->findWhere($table = 'ws_follow', $where_data = array('user_id' => $user_id, 'friend_id' => $list['friend_id']), $multi_record = false, $order = '');

                    $DATA[] = array(
                        'follower_id' => $list['friend_id'],
                        'follower_name' => (!empty($follower_data['fullname']) ? $follower_data['fullname'] : ''),
                        'follower_pic' => (!empty($follower_data['profile_pic']) ? $base_url . $follower_data['profile_pic'] : ''),
                        'unique_name' => (!empty($follower_data['unique_name']) ? $follower_data['unique_name'] : ''),
                        'phone' => (!empty($follower_data['phone']) ? $follower_data['phone'] : ''),
                        'email' => (!empty($follower_data['email']) ? $follower_data['email'] : ''),
                        'follow' => (!empty($chk_follow_data) ? '1' : '0')
                    );
                }
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            } else {
                $data = array(
                    'status' => 1,
                    'data' => $DATA
                );
            }
        } else {
            $data = array(
                'status' => 0,
                'message' => 'User id or friend id does not exist'
            );
        }
        $this->response($data, 200);
    }

    public function edit_profile_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $unique_name = $this->input->post('unique_name');
        
        $full_name = $this->input->post('full_name');
        $this->check_empty($full_name, 'Please add full_name');

        $latitude = $this->input->post('latitude');
        
        $longitude = $this->input->post('longitude');
        
        $location = $this->input->post('location');
        
        $relationship_status = $this->input->post('relationship_status');
        //$this->check_empty($relationship_status, 'Please add relationship_status');

        $gender = $this->input->post('gender');
        //$this->check_empty($gender, 'Please add gender');

        $age = $this->input->post('age');
        
        $country_code = $this->input->post('country_code');
        
        $phone = $this->input->post('phone');
        
        $email_val = $this->input->post('email');
        $this->check_empty($email_val, 'Please add email');

        $occupation = $this->input->post('occupation');

        $interest = $this->input->post('interest');

        $industry = $this->input->post('industry');

        $usage = $this->input->post('usage');
        

        $this->load->helper(array('file'));
        $this->load->library('upload');

        $user_detail = $this->db->get_where('ws_users' , array('id' => $user_id))->row_array();
        if($user_detail)
        {
            if($user_detail['unique_name'] == '')
            {
                $alreadyexist_unique_name = $this->common_model->findWhere($table = 'ws_users', $where_data = array('unique_name' => $unique_name), $multi_record = false, $order = '');
                if (isset($alreadyexist_unique_name) && (!empty($alreadyexist_unique_name)))
                {
                        $data = array(
                            'status' => 0,
                            'message' => 'unique name already exists'
                        );
                        $this->response($data, 200);
                }
            }

            /* if the image file exists as input parameter */

            if (isset($_FILES['profile_pic']['name'])) {
                //provide config values
                $file_name = $_FILES['profile_pic']['name'];
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);

                $config['upload_path'] = './uploads/profile';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '500000';
                $config['max_width'] = '52400';
                $config['max_height'] = '57680';
                $config['file_name'] = 'profile' . rand() . '.' . $ext;

                $this->upload->initialize($config);

                //if the profile pic could not be uploaded
                if (!$this->upload->do_upload('profile_pic')) {
                    print_r($this->upload->display_errors());
                    $this->session->set_flashdata('errors', $this->upload->display_errors());
                } else {
                    $profile_path = "uploads/profile/" . $config['file_name'];
                }
            }

            /* check if email already exists in users table */
            if (!empty($email_val)) {
                $email = strtolower($email_val);

                if($email == $user_detail['email'])
                {

                }
                else
                {
                    $user_exist_data = $this->common_model->findWhere($table = 'ws_users', array('email' => $email, 'activated' => 1), $multi_record = false, $order = '');
                    if (isset($user_exist_data) && (!empty($user_exist_data))) {
                        if ($user_exist_data['email'] != '') {
                            /* user already exists */
                            $data = array(
                                'status' => 0,
                                'message' => 'Email id already exists'
                            );
                            $this->response($data, 200);
                        }
                    }
                }    
                
            } else {
                $email = '';
            }

            $post_data = array(
                'unique_name' => (!empty($unique_name) ? $unique_name : $user_detail['unique_name']),
                'fullname' => (!empty($full_name) ? $full_name : $user_detail['fullname']),
                'latitude' => (!empty($latitude) ? $latitude : $user_detail['latitude']),
                'longitude' => (!empty($longitude) ? $longitude : $user_detail['longitude']),
                'location' => (!empty($location) ? $location : $user_detail['location']),
                'relationship_status' => (!empty($relationship_status) ? $relationship_status : $user_detail['relationship_status']),
                'gender' => (!empty($gender) ? $gender : $user_detail['gender']),
                'age' => (!empty($age) ? $age : $user_detail['age']),
                'country_code' => (!empty($country_code) ? $country_code : $user_detail['country_code']),
                'phone' => (!empty($phone) ? $phone : $user_detail['phone']),
                'profile_pic' => (!empty($profile_path) ? $profile_path : $user_detail['profile_pic']),
                'email' => (!empty($email) ? $email : $user_detail['email']),
                'occupation' => (!empty($occupation) ? $occupation : $user_detail['occupation']),
                'interest' => (!empty($interest) ? $interest : $user_detail['interest']),
                'industry' => (!empty($industry) ? $industry : $user_detail['industry']),
                'bestest_usage' => (!empty($usage) ? $usage : $user_detail['bestest_usage'])
            );
            if($this->db->where('id', $user_id)->update('ws_users' ,$post_data))
            {
                $data = array(
                    'status' => 1,
                    'message' => 'success',
                    'profile_pic' => (!empty($updateduser_detail['profile_pic']) ? $this->baseurl.$updateduser_detail['profile_pic'] : ''),
                 );
            }
            else
            {
                $data = array(
                    'status' => 0,
                    'message' => 'failed'
                 );
            }    
        }
        else
        {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exist'
            );
        }    
        $this->response($data, 200);
    }

   public function get_updated_profile_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $user_detail = $this->db->get_where('ws_users' , array('id' => $user_id))->row_array();
        if($user_detail)
        {
            $interest = $this->db->get_where('ws_interest')->result_array();
            $industry = $this->db->get_where('ws_industry')->result_array();
            $usage = $this->db->get_where('ws_usage')->result_array();

            //post count
            $user_posts = $this->db->get_where('ws_posts' , array('user_id' => $user_id))->result_array();
            $count_post = count($user_posts);

            //count follower
            $followers = $this->db->get_where('ws_follow' , array('user_id' => $user_id))->result_array();
            $count_followers = count($followers);

            //count follower
            $following = $this->db->get_where('ws_follow' , array('friend_id' => $user_id))->result_array();
            $count_following = count($following);

            //last 3 months followers

            $recentfollowers = $this->db->query("select * from ws_follow where user_id = '$user_id' AND created >= now()-interval 3 month")->result_array();
            $count_recentfollowers = count($recentfollowers);

            //last 3 months voting

            $imagelikes = $this->db->query("select * from ws_likes where user_id = '$user_id' AND created >= now()-interval 3 month")->result_array();
            $count_imagelikes = count($imagelikes);
            
            $textlikes = $this->db->query("select * from ws_text_likes where user_id = '$user_id' AND created >= now()-interval 3 month")->result_array();
            $count_textlikes = count($textlikes);

            $videolikes = $this->db->query("select * from ws_video_likes where user_id = '$user_id' AND created >= now()-interval 3 month")->result_array();
            $count_videolikes = count($videolikes);

            $count_recentlikes = $count_imagelikes + $count_textlikes + $count_videolikes;

            //last 3 months posts

            $recentposts = $this->db->query("select * from ws_posts where user_id = '$user_id' AND added_at >= now()-interval 3 month")->result_array();
            $count_recentposts = count($recentposts);

            $Data = array(
                'unique_name' => (!empty($user_detail['unique_name']) ? $user_detail['unique_name'] : ''),
                'email' => (!empty($user_detail['email']) ? $user_detail['email'] : ''),
                'fullname' => (!empty($user_detail['fullname']) ? $user_detail['fullname'] : ''),
                'created' => (!empty($user_detail['created']) ? $user_detail['created'] : ''),
                'latitude' => (!empty($user_detail['latitude']) ? $user_detail['latitude'] : ''),
                'longitude' => (!empty($user_detail['longitude']) ? $user_detail['longitude'] : ''),
                'location' => (!empty($user_detail['location']) ? $user_detail['location'] : ''),
                'relationship_status' => (!empty($user_detail['relationship_status']) ? $user_detail['relationship_status'] : ''),
                'gender' => (!empty($user_detail['gender']) ? $user_detail['gender'] : ''),
                'age' => (!empty($user_detail['age']) ? $user_detail['age'] : ''),
                'country_code' => (!empty($user_detail['country_code']) ? $user_detail['country_code'] : ''),
                'country_label' => (!empty($user_detail['country_label']) ? $user_detail['country_label'] : ''),
                'phone' => (!empty($user_detail['phone']) ? $user_detail['phone'] : ''),
                'occupation' => (!empty($user_detail['occupation']) ? $user_detail['occupation'] : ''),
                'profile_pic' => (!empty($user_detail['profile_pic']) ? $this->baseurl.$user_detail['profile_pic'] : ''),
                'interest' => (!empty($user_detail['interest']) ? str_replace(',', ', ', $user_detail['interest']): ''),
                'industry' => (!empty($user_detail['industry']) ? str_replace(',', ', ', $user_detail['industry']) : ''),
                'usage' => (!empty($user_detail['bestest_usage']) ? str_replace(',', ', ', $user_detail['bestest_usage']) : ''),
                'post_count' => $count_post,
                'follower_count' => $count_followers,
                'following_count' => $count_following,
                'recent_followers' => $count_recentfollowers,
                'recent_likes' => $count_recentlikes,
                'recent_posts' => $count_recentposts
            );
            $data = array(
                'status' => 1,
                'data' => $Data
            );
        }
        else
        {
            $data = array(
                'status' => 0,
                'message' => 'User id does not exist'
            );
        }
        $this->response($data, 200); 
    }

    public function update_interest_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $interest = $this->input->post('interest');
        $this->check_empty($interest, 'Please add interest');

        $post_data = array(
            'interest' => $interest
            );
        if($this->db->where('id', $user_id)->update('ws_users' ,$post_data))
        {
            $data = array(
                'status' => 1,
                'message' => 'success'
             );
        }
        else
        {
            $data = array(
                'status' => 0,
                'message' => 'failed'
             );
        }  
        $this->response($data, 200); 
    }

    public function interest_details_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $allinterest = $this->db->get_where('ws_interest')->result_array();

        foreach($allinterest as &$interest)
        {
            $value = trim($interest['title']);
            $this->db->where('id' , $user_id);
            $this->db->where("FIND_IN_SET('$value',interest) !=", 0);
            $q = $this->db->get('ws_users');
            $data = $q->row_array();
            if($data)
            {
                $interest['selected'] = 'yes';
            }
            else
            {
                $interest['selected'] = 'no';    
            }    
        }
        $data = array(
                'status' => 1,
                'message' => 'success',
                'all_interest' => $allinterest
             );
        $this->response($data, 200); 
    }

    public function update_industry_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $industry = $this->input->post('industry');
        $this->check_empty($industry, 'Please add industry');

        $post_data = array(
            'industry' => $industry
            );
        if($this->db->where('id', $user_id)->update('ws_users' ,$post_data))
        {
            $data = array(
                'status' => 1,
                'message' => 'success'
             );
        }
        else
        {
            $data = array(
                'status' => 0,
                'message' => 'failed'
             );
        }  
        $this->response($data, 200); 
    }

    public function industry_details_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $allindustry = $this->db->get_where('ws_industry')->result_array();

        foreach($allindustry as &$industry)
        {
            $value = $industry['title'];
            $this->db->where('id' , $user_id);
            $this->db->where("FIND_IN_SET('$value',industry) !=", 0);
            $q = $this->db->get('ws_users');
            $data = $q->row_array();

            if($data)
            {
                $industry['selected'] = 'yes';
            }
            else
            {
                $industry['selected'] = 'no';    
            }    
        }
        $data = array(
                'status' => 1,
                'message' => 'success',
                'all_industry' => $allindustry
             );
        $this->response($data, 200); 
    }

    public function update_usage_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $usage = $this->input->post('usage');
        $this->check_empty($usage, 'Please add usage');

        $post_data = array(
            'bestest_usage' => $usage
            );
        if($this->db->where('id', $user_id)->update('ws_users' ,$post_data))
        {
            $data = array(
                'status' => 1,
                'message' => 'success'
             );
        }
        else
        {
            $data = array(
                'status' => 0,
                'message' => 'failed'
             );
        }  
        $this->response($data, 200); 
    }

    public function usage_details_post()
    {
        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $allusage = $this->db->get_where('ws_usage')->result_array();

        foreach($allusage as &$usage)
        {
            $value = $usage['title'];
            $this->db->where('id' , $user_id);
            $this->db->where("FIND_IN_SET('$value',bestest_usage) !=", 0);
            $q = $this->db->get('ws_users');
            $data = $q->row_array();

            if($data)
            {
                $usage['selected'] = 'yes';
            }
            else
            {
                $usage['selected'] = 'no';    
            }    
        }
        $data = array(
                'status' => 1,
                'message' => 'success',
                'all_usage' => $allusage
             );
        $this->response($data, 200); 
    }

    public function delete_user_post()
    {
        $relative_path = '/var/www/html/bestest_test/';

        $user_id = $this->input->post('user_id');
        $this->check_empty($user_id, 'Please add user_id');

        $posts = $this->db->get_where('ws_posts', array('user_id' => $user_id))->result_array();
        if($posts)
        {
            foreach($posts as $post)
            {
                $post_id = $post['post_id'];
                $this->common_model->delete($table = 'ws_images', $delete_data = array('post_id' => $post_id));
                
                $images_detail = $this->db->get_where('ws_images', array('post_id' => $post_id))->result_array();

               
                //delete image files from server,likes,dislikes
                foreach ($images_detail as $images) {
                    $image_path = $relative_path . 'uploads/post_images/' . $images['image_name'];
                    //delete image files
                    unlink($image_path);

                    //delete likes
                    $this->common_model->delete($table = 'ws_likes', $where_data = array('image_id' => $images['image_id']));

                    //delete unlikes
                    $this->common_model->delete($table = 'ws_unlikes', $where_data = array('image_id' => $images['image_id']));
                }
                
                $text_detail = $this->db->get_where('ws_text', array('post_id' => $post_id))->result_array();

                //delete image files from server,likes,dislikes
                foreach ($text_detail as $text) {
                    
                    //delete text likes
                    $this->common_model->delete($table = 'ws_text_likes', $where_data = array('text_id' => $text['id']));

                    //delete text unlikes
                    $this->common_model->delete($table = 'ws_text_unlikes', $where_data = array('text_id' => $text['id']));
                }
                
                //delete images
                $this->common_model->delete($table = 'ws_images', $where_data = array('post_id' => $post_id));

                //delete img tags user
                $this->common_model->delete($table = 'ws_img_tags_user', $where_data = array('post_id' => $post_id));

                //delete img tags word
                $this->common_model->delete($table = 'ws_img_tags_word', $where_data = array('post_id' => $post_id));

                //delete tags
                $this->common_model->delete($table = 'ws_tags', $where_data = array('post_id' => $post_id));

                //delete comments
                $this->common_model->delete($table = 'ws_comments', $where_data = array('post_id' => $post_id));

                //delete notifications
                $this->common_model->delete($table = 'ws_notifications', $where_data = array('post_id' => $post_id));
            
                //delete report abuse
                $this->common_model->delete($table = 'ws_report_abuse', $where_data = array('post_id' => $post_id));

                $this->common_model->delete($table = 'ws_text', $where_data = array('post_id' => $post_id));

                $this->common_model->delete($table = 'ws_words', $where_data = array('post_id' => $post_id));

                //delete posts
                $this->common_model->delete($table = 'ws_posts', $where_data = array('post_id' => $post_id));
            }
        }
        $this->common_model->delete($table = 'ws_notification_set', $delete_data = array('user_id' => $user_id));
        
        $this->common_model->delete($table = 'ws_comments', $delete_data = array('user_id' => $user_id));

        $wdata = $this->db->where("(sender_id='" . $user_id . "' OR receiver_id='" . $user_id . "') ", NULL, FALSE);
        $this->common_model->delete($table = 'ws_conversations', $wdata);

        $w2data = $this->db->where("(sender_id='" . $user_id . "' OR receiver_id='" . $user_id . "') ", NULL, FALSE);
        $this->common_model->delete($table = 'ws_messages', $w2data);
        

        $w3data = $this->db->where("(sender_id='" . $user_id . "' OR receiver_id='" . $user_id . "') ", NULL, FALSE);
        $this->common_model->delete($table = 'ws_notifications', $w3data);


        $w1data = $this->db->where("(user_id='" . $user_id . "' OR friend_id='" . $user_id . "') ", NULL, FALSE);
        $this->common_model->delete($table = 'ws_follow', $w1data);

        $this->common_model->delete($table = 'ws_groups', $delete_data = array('group_owner' => $user_id));
        $this->common_model->delete($table = 'ws_group_members', $delete_data = array('member_id' => $user_id));
        $this->common_model->delete($table = 'ws_group_messages', $delete_data = array('user_id' => $user_id));
        $this->common_model->delete($table = 'ws_group_read_status', $delete_data = array('user_id' => $user_id));
    
        $this->common_model->delete($table = 'ws_report_abuse', $where_data = array('user_id' => $user_id));

        $this->common_model->delete($table = 'ws_sound_notification', $where_data = array('user_id' => $user_id));
        
        $this->common_model->delete($table = 'ws_tags', $where_data = array('user_id' => $user_id));

        $this->common_model->delete($table = 'ws_likes', $where_data = array('user_id' => $user_id));

        $this->common_model->delete($table = 'ws_unlikes', $where_data = array('user_id' => $user_id));

        $this->common_model->delete($table = 'ws_text_likes', $where_data = array('user_id' => $user_id));

        $this->common_model->delete($table = 'ws_text_unlikes', $where_data = array('user_id' => $user_id));

        $this->common_model->delete($table = 'ws_users', $delete_data = array('id' => $user_id));
        
        $data = array(
                'status' => 0,
                'message' => 'successfully'
            );

        $this->response($data, 200);
    }

    public function testFB_post()
    {
        /*$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $get = curl_exec($ch);
        $res = json_decode($get);
        //echo 'aa'.$res;
        curl_close($ch);

        $access_token = str_replace('access_token=', '', $get);

        

        $users = $this->db->get_where('ws_users' , array('social_id !=' => '' , 'social_type' = 'facebook'))->result_array();
        foreach($users as $us)
        {
            $social_id = $us['social_id'];
            $ch = curl_init();
            //$postFields = array('access_token' => $access_token, 'message' => 'test message');
            curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/".$social_id."?fields=email,name&access_token=".$access_token);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $tt = curl_exec($ch);
            echo 'aa'.print_r($tt);
            curl_close($ch);


        }*/

        //$access_token = $this->getAccessToken();
        //$users = $this->db->get_where('ws_users' , array('social_id !=' => '' , 'social_type' = 'facebook'))->result_array();
        //echo '<pre>';print_r($users);
        /*foreach($users as $user)
        {
            $social_id = $us['social_id'];
            $url = "https://graph.facebook.com/".$social_id."?fields=email,name&access_token=".$access_token;
            $ch = curl_init();
            $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',

            );
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            //$body = '{}';

            //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
            //curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Timeout in seconds
            //curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);


            echo $result;
            //$res = json_decode($authToken);
            //$access_token = $res->access_token;
            //return $access_token;
        }*/
    }

    public function getAccessToken()
    {
        $url = 'https://graph.facebook.com/oauth/access_token?client_id=1740512662891357&client_secret=650c9db2802bba87bc0122de1804b8d3&grant_type=client_credentials';
        $ch = curl_init();
        $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',

        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //$body = '{}';

        //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        //curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Timeout in seconds
        //curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $authToken = curl_exec($ch);

        //echo $authToken;
        $res = json_decode($authToken);
        $access_token = $res->access_token;
        return $access_token;
    }
}

/* End of file welcome.php */
/* Location: ./application/modules/welcome/controllers/welcome.php */
