<?php

namespace Relations\Models;

use Exception;
use Relations\Config;

class AbstractModel
{
    protected static $route;
    protected static $include;
    protected static $options = [];

    public $id;

    /**
     * @return array
     */
    public static function all()
    {
        $url = static::getRoute();
        $data = static::sendRequest($url);

        $result = [];
        if ($data) {
            foreach ($data as $item) {
                $result[] = static::hydrate($item);
            }
        }
        return $result;
    }

    /**
     * @return \Relations\Models\AbstractModel
     */
    public static function find($id)
    {
        $url = static::getRoute() . '/' . $id;
        $data = static::sendRequest($url);

        return static::hydrate($data);
    }

    /**
     * @return \Relations\Models\AbstractModel
     */
    public static function findByClientId($id)
    {
        $url = sprintf('%s?client_id=%s', static::getRoute(), $id);
        $data = static::sendRequest($url);

        $result = [];
        foreach ($data as $item) {
            $result[] = static::hydrate($item);
        }

        return $result;
    }

    /*
    public static function whereClientId($clientId)
    {
        static::$options['client_id'] = $clientId;
    }
    */

    /**
     * @return \Relations\Models\AbstractModel
     */
    public function save()
    {
        $url = static::getRoute();

        if ($this->id) {
            $method = 'PUT';
            $url .= '/' . $this->id;
        } else {
            $method = 'POST';
        }

        $data = static::sendRequest($url, $this, $method);

        return static::hydrate($data, $this);
    }

    public function archive()
    {
        return $this->sendAction('archive');
    }

    public function restore()
    {
        return $this->sendAction('restore');
    }

    public function delete()
    {
        return $this->sendAction('delete');
    }

    public static function subscribeCreate($target)
    {
        $url = Config::getUrl() . '/hooks';
        $data = [
            'event' => 'create_' . static::entityType(),
            'target_url' => $target
        ];

        static::sendRequest($url, $data, 'POST', true);
    }

    protected static function entityType()
    {
        return rtrim(static::$route, 's');
    }

    protected function sendAction($action)
    {
        $url = sprintf('%s/%s?action=%s', static::getRoute(), $this->id, $action);

        $data = static::sendRequest($url, $this, 'PUT');

        return static::hydrate($data, $this);
    }

    protected static function getRoute()
    {
        if (!static::$route) {
            throw new Exception('API route is not defined for ' . get_called_class());
        }

        return sprintf('%s/%s', Config::getUrl(), static::$route);
    }

    public static function hydrate($item, $entity = false)
    {
        if (!$entity) {
            $className = get_called_class();
            $entity = new $className();
        }

        foreach ($item as $key => $value) {
            $entity->$key = $value;
        }
        return $entity;
    }

    protected static function sendRequest($url, $data = false, $type = 'GET', $raw = false)
    {

        // debug
        //$raw = true;

        $data = json_encode($data);
        $curl = curl_init();

        $options = array_merge(static::$options, [
            'include' => static::$include,
            'per_page' => Config::getPerPage()
        ]);

        $parsedUrl = parse_url($url);
        $separator = (!isset($parsedUrl['query']) || $parsedUrl['query'] == NULL) ? '?' : '&';

        $url .= $separator . http_build_query($options);


        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_POST => $type === 'POST' ? 1 : 0,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_USERAGENT => 'Relations - PHP SDK',
            CURLOPT_HTTPHEADER  => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data),
                'X-Relations-Token: ' . Config::getToken(),
            ],
        ];

        curl_setopt_array($curl, $opts);

        $response = curl_exec($curl);

        if ($response) {
            if ($raw) {
                return $response;
            } else {
                $json = json_decode($response);
                if ($json) {
                    return $json;
                } else {
                    throw new Exception($response);
                }
            }
        } else {
            $response = 'Erreur Curl : ' . curl_error($curl);
            throw new Exception($response);
            return '';
        }
    }
}
