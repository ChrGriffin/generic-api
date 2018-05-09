<?php

namespace ChrGriffin;

use GuzzleHttp;
use GuzzleHttp\Exception\ClientException;

class GenericApi
{
    /**
     * GuzzleHttp client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Bearer token for the API.
     *
     * @var string
     */
    public $token;

    /**
     * GenericApi constructor.
     *
     * @param string $baseUri The base URL of the api to access.
     * @return void
     */
    public function __construct(string $baseUri)
    {
        $this->client = new GuzzleHttp\Client([
    		'base_uri' => $baseUri,
    	]);
    }

    /**
     * Make a request to the API.
     *
     * @param string $method The request method (Get, post, etc.)
     * @param string $endpoint The endpoint to access.
     * @param array $params Any additional request parameters, such as form data.
     * @return mixed
     */
    public function request(string $method, string $endpoint, array $params = [])
    {
    	$params = $this->recursiveMerge(
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json'
                ]
            ],
            $params
        );

    	try {
    		$response = $this->client->request(
        		strtoupper($method),
        		$endpoint,
        		$params
        	);
    	}
    	catch(ClientException $e) {
    		if($e->getCode() === 401 && method_exists($this, 'authorize')) {
    			$this->authorize();
    			return $this->request($method, $endpoint, $params);
    		}

    		throw $e;
    	}

    	return json_decode($response
    		->getBody()
    		->getContents());
    }

    /**
     * Recursively merge multiple arrays into the first array.
     *
     * @param array $array
     * @param mixed $arrays,...
     * @return array
     */
    private function recursiveMerge(array $array, ...$arrays) : array
    {
        foreach($arrays as $insertArray) {

            if(!is_array($insertArray)) {
                continue;
            }

            foreach($insertArray as $key => $value) {
                if(array_key_exists($key, $array) && is_array($value)){
                    $array[$key] = $this->recursiveMerge($array[$key], $insertArray[$key]);
                }
                else {
                    $array[$key] = $value;
                }
            }
        }

        return $array;
    }
}