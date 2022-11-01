<?php
	if (!class_exists('WPHttp')) {
		class WPHttp {
			public $response;
			
			private $args			= ['method' => 'POST'];
			private $return			= ['headers' => [], 'body' => ''];
			
			public $success			= false;
			public $return_body		= '';
			public $return_headers	= [];
			public $return_code		= 0;
			
			public function __construct($url, $method = 'POST') {
				$this->url = $url;
				$this->args['method'] = $method;
			}
			
			public function SetMethod($method) {
				$this->Method($method);
			}
			
			public function Method($method = false) {
				if ($method !== false) {
					$this->args['method'] = $method;
				}
				return $this->args['method'];
			}
			
			public function SetHeader($header,$value) {
				$this->args['headers'][$header] = $value;
			}
			
			public function SetArg($arg,$value) {
				$this->args[$arg] = $value;
			}
			
			public function SetBody($body) {
				$this->args['body'] = $body;
			}
			
			public function Execute() {

				$this->response = wp_remote_request($this->url,$this->args);
				
				if (!is_wp_error($this->response) && ($this->response['response']['code'] == 200 || $this->response['response']['code'] == 201)) {
					
					$this->success = true;
					
					$this->return_body		= wp_remote_retrieve_body($this->response);
					$this->return_headers	= wp_remote_retrieve_headers($this->response);
					$this->return_code		= wp_remote_retrieve_response_code($this->response);
					
					return true;
				} else {
					$this->success = false;
					return false;
				}
				
			}
		}
	}
?>