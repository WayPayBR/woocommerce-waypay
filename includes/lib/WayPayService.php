<?php

class WayPayService {

    const URL_SANDBOX = 'https://suaconta.sandbox.waypay.com.br';
    const URL_PRODUCTION = 'https://suaconta.waypay.com.br';

	private $url;

	/** @var WC_Logger */
	private $log;

	public function __construct($logger,$mode_test='no')
	{
		$this->log = $logger;
		$this->url = $mode_test === 'yes' ? self::URL_SANDBOX : self::URL_PRODUCTION;
	}

	public function pay($request_data)
	{
		$url = $this->url.'/api/v1/transaction';
        if ($this->log) {
            $this->log->add('waypay_api', '----- REQUEST -----');
            $this->log->add('waypay_api', json_encode($request_data,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        }
		$response = $this->request($url, 'POST', json_encode($request_data,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),array(
		    'Content-Type: application/json'
	    ));
        if ($this->log) {
            $this->log->add('waypay_api', '----- RESPONSE -----');
            $this->log->add('waypay_api', print_r($response,1));
        }
        return $response;
    }

    public function balance($request_data)
    {
        $url = $this->url.'/api/v1/balance';
        if ($this->log) {
            $this->log->add('waypay_api', '----- REQUEST -----');
            $this->log->add('waypay_api', json_encode($request_data,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        }
        $response = $this->request($url, 'POST', json_encode($request_data,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),array(
            'Content-Type: application/json'
        ));
        if ($this->log) {
            $this->log->add('waypay_api', '----- RESPONSE -----');
            $this->log->add('waypay_api', print_r($response,1));
        }
        return $response;
    }

	public function simulate($authorization, $paymentCode, $total, $minSplit)
	{

		$url = $this->url.'/api/v1/paymentInterestInstallments';

		$dataToSimulate = array(
			'authorization' => $authorization,
			'paymentInterestInstallments' => array(
				'paymentMethodCode' => $paymentCode,
				'value' => $total
			)
		);

		$response =  $this->request($url, 'POST', json_encode($dataToSimulate), array(
		    'Content-Type: application/json'
	    ));

	    if($response['status'] != 200) {
	    	return [];
	    }

	    $installments = json_decode($response['body'], true);
	    $installmentsResult = array();

	    foreach ($installments['paymentInterestInstallments']['InterestInstallments'] as $key => $value) {
	    	if($value['value'] / $value['installment'] >= $minSplit){
	    		$installmentsResult[$value['installment']] = $value['value'];
	    	}
	    }

	    return $installmentsResult;
	}

	public function login($cpfcnpj, $password)
	{
		$url = $this->url.'/api/v1/login';

		$dataToLogin = array(
			'login' =>  array(
				'account' => array(
					'cpfcnpj' => $cpfcnpj,
					'password' => $password
				)
			)
		);

        if ($this->log) {
            $this->log->add('waypay_api', '----- REQUEST -----');
            $this->log->add('waypay_api', json_encode($dataToLogin,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        }

        $response = $this->request($url, 'POST', json_encode($dataToLogin), array(
		    'Content-Type: application/json'
	    ));

        if ($this->log) {
            $this->log->add('waypay_api', '----- RESPONSE -----');
            $this->log->add('waypay_api', print_r($response,1));
        }

        if($response['status'] == 200) {
			$content = json_decode($response['body']);
			return $content->login->token;
		}

		return false;
	}


	/**
	 * Do requests in the WayPay API.
	 *
	 * @param  string $url      URL.
	 * @param  string $method   Request method.
	 * @param  string  $data     Request data.
	 * @param  array  $headers  Request headers.
	 *
	 * @return array            Request response.
	 */
	protected function request( $url, $method = 'POST', $data = null , $headers = array() ) {
		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if($method == 'POST'){
			curl_setopt($ch,CURLOPT_POST, true);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
		}

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);

		$statusCode = isset($info['http_code']) ? $info['http_code'] : null;
		curl_close($ch);

		return array('status' => $statusCode, 'body' => $result);
	}

}