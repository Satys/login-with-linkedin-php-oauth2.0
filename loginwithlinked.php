<?php

	public function send_req($url, $data, $method) {
		$options = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => $method,
		        'content' => http_build_query($data)
		    )
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if ($result === FALSE) { /* Handle error */ }

		if (gettype($result) == 'string') {
			$result = json_decode($result, true);
		} else if (gettype($result) == 'object') {
			$result = array($result);
		}
		return $result;

    }
	/*
	* Redirect URL get api, implemented in rest api, codeigniter
	*/
	public function linkedin_auth_get() {

		if ($this->input->get('error')) {
			header('Location: '.PROTOCOL.base_url());
		} else {
			if ($this->input->get('code')) {
				$code = $this->input->get('code');
			} else {
				header('HTTP 401');
				return;
			}
			if ($this->input->get('state')) {
				$state = $this->input->get('state');
				if ($state !== $this->session->userdata('state')) {
					header('HTTP 401');
					return;
				}
			} else {
				header('HTTP 401');
				return;
			}
			$url = 'https://www.linkedin.com/oauth/v2/accessToken';
			$data_access_token = array(
				'grant_type' => 'authorization_code',
				'code' => $code,
				'redirect_uri' => PROTOCOL.base_url().'api/login/linkedin_auth',
				'client_id' => 'client_id',
				'client_secret' => 'client_secret'
				);
			$access_token = $this->send_req($url, $data_access_token, 'POST')['access_token'];
			$linkedin_data = $this->fetch_linkedin_data($access_token);
			print_r($linkedin_data);
		}
	}

	public function fetch_linkedin_data($access_token) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.linkedin.com/v1/people/~:(id,first-name,last-name,picture-url,public-profile-url,email-address)?format=json",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 10,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache",
		    "connection: Keep-Alive"
		  ),
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
		} else {
			$response = json_decode($response, true);
		  	return $response;
		}
	}
 ?>