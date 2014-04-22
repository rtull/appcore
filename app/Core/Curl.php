<?php
class Core_Curl {
    protected $_curl_method = 'GET';
    protected $_curl_headers = array('Accept: application/xml');

    public function sendRequest($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_curl_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->_curl_method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $output = curl_exec($ch);

        curl_close($ch);

        return $output;
    }
}
