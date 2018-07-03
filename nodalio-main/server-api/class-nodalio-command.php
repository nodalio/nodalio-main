<?php

class Nodalio_API_Command {
    public $base = 'nodalio';
    public $shortcode;
    public $action;
    public $endpoint = 'sites';
    public $api_version = 'v1';
    public $url = 'localhost';
    public $http = 'http';
    public $port = '34560';
    public $http_method = 'POST';
    public $token = false;

    /**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  WP_Error if token is empty
	 */
	public function __construct($token, $shortcode, $action, $http_method = 'POST') {
		if ( ! empty($token) ) {
            $this->http_method = strtoupper($http_method);
            $this->token = $token;
            $this->shortcode = $shortcode;
            if ( ! empty($action) ) {
                $this->action = $action;
            }
        } else {
            return;
        }
    }
    
    public function toString($type) {
        if ( $type == "url" ) {
            return $url = apply_filters( 'nodalio_api_run_shortcode_request_url', $this->_formSiteURL() );
        } else if ( $type == "body" ) {
            return apply_filters( 'nodalio_api_run_shortcode_request_body', $this->_formRequestBody() );
        } else if ( $type == "headers" ) {
            apply_filters( 'nodalio_api_run_shortcode_request_headers', $this->_formRequestHeaders() );
        } else {
            return new WP_Error( 'site_api_to_string_no_type', __( "Error: Missing type from toString" ) );
        }
    }

    public function runCommand() {
        //error_log("run command: " . $token . $shortcode . $action . $http_method);
        $response = $this->_runCommand();
        return apply_filters( 'nodalio_api_run_shortcode_response', $response );
    }

    private function _runCommand() {
        if ( $this->token ) {

            // Filters the API url, contains url in string format OR WP_Error object with error message from url formatting
            $url = apply_filters( 'nodalio_api_run_shortcode_request_url', $this->_formSiteURL() );

            if ( is_wp_error( $url ) ) {
                return $url;
            }

            // Filters the request body, contains array with shortcode and action(if exists) OR WP_Error object with error message from body formatting
            $body_array = apply_filters( 'nodalio_api_run_shortcode_request_body', $this->_formRequestBody() );

            if ( is_wp_error( $body_array ) ) {
                return $body_array;
            } else if ( ! is_array( $body_array ) ) {
                return new WP_Error( 'site_api_request_body_error', __( "Error: request body is not an array" ) );
            }

            // Filters the request headers, contains array with Content-Type header and Authorization Bearer token header OR WP_Error object with error message from body formatting
            $headers_array = apply_filters( 'nodalio_api_run_shortcode_request_headers', $this->_formRequestHeaders() );

            if ( is_wp_error( $headers_array ) ) {
                return $headers_array;
            } else if ( ! is_array( $headers_array ) ) {
                return new WP_Error( 'site_api_request_body_error', __( "Error: headers request is not an array" ) );
            }

            
            if ( $this->http_method == "POST" ) {
                $response = wp_remote_post( $url, array(
                    'method' => 'POST',
                    'timeout' => 120,
                    'redirection' => 5,
                    'httpversion' => '1.1',
                    'blocking' => true,
                    'headers' => $headers_array,
                    'body' => $body_array,
                    'cookies' => array()
                    )
                );
                return $response;
            } else if ( $this->http_method == "GET" ) {
                $response = wp_remote_get( $url, array(
                    'method' => 'GET',
                    'timeout' => 120,
                    'redirection' => 5,
                    'httpversion' => '1.1',
                    'blocking' => true,
                    'headers' => $headers_array,
                    'body' => $body_array,
                    'cookies' => array()
                    )
                );
                return $response;
            } else {
                $response = new WP_Error( 'site_api_http_method_error', __( "Error: invalid HTTP method." ) );
            }

            return $response; 
        } else {
            return new WP_Error( 'site_api_token_missing', __( "Missing site security token." ) );
        }
    }

    private function _formSiteURL() {
        try {
            $api_url = esc_url_raw( $this->http . "://" . $this->url . ":" . trailingslashit( $this->port ) . trailingslashit( $this->api_version ) . trailingslashit( $this->endpoint ) . $this->_getSiteID() );
        } catch (Exception $e) {
            $api_url = new WP_Error( 'site_api_url', __( "Error creating your site's API url: " . $e->getMessage() ) );
        } finally {
            return $api_url;
        }
    }

    private function _formRequestBody() {
        $request_body = array();
        if ( $this->shortcode ) {
            $request_body['shortcode_name'] = $this->shortcode;
            if ( isset($this->action) && $this->action ) {
                $request_body['shortcode_action'] = $this->action;
            }
        } else {
            return new WP_Error( 'site_api_request_body', __( "Error: Missing shortcode from request body" ) );
        }
        return $request_body;
    }

    private function _formRequestHeaders() {
        $request_headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        );
        if ( isset($this->token) && $this->token ) {
            $request_headers['Authorization'] = 'Bearer ' . $this->token;
        } else {
            return new WP_Error( 'site_api_request_headers', __( "Error: Missing token from request headers" ) );
        }
        return $request_headers;
    }

    private function _getSiteID() {
        if ( defined('NODALIO_SITE_ID') && NODALIO_SITE_ID ) {
            return NODALIO_SITE_ID;
        } else {
            throw new Exception('Site ID missing from wp-config.php');
        }
    }
}

class Nodalio_API_Token_Validator extends Nodalio_API_Command {

    /**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  WP_Error if token is empty
	 */
	public function __construct($token) {
		if ( ! empty($token) ) {
            $this->token = $token;
        }
    }

    public function runCommand() {
        $response = $this->_runCommand();
        return apply_filters( 'nodalio_api_run_token_validation_response', $response );
    }
    
    private function _runCommand() {
        if ( $this->token ) {

            // Filters the API url, contains url in string format OR WP_Error object with error message from url formatting
            $url = apply_filters( 'nodalio_api_run_token_validation_request_url', $this->_formSiteURL() );

            if ( is_wp_error( $url ) ) {
                return $url;
            }

            // Filters the request headers, contains array with Content-Type header and Authorization Bearer token header OR WP_Error object with error message from body formatting
            $headers_array = apply_filters( 'nodalio_api_run_token_validation_request_headers', $this->_formRequestHeaders() );

            if ( is_wp_error( $headers_array ) ) {
                return $headers_array;
            } else if ( ! is_array( $headers_array ) ) {
                return new WP_Error( 'site_api_request_body_error', __( "Error: request body is not an array" ) );
            }

            $response = wp_remote_post( $url, array(
                'method' => 'POST',
                'timeout' => 120,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => $headers_array,
                'body' => array(),
                'cookies' => array()
                )
            );

            return $response; 
        }
    }
}

?>