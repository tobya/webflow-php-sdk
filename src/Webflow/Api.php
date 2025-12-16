<?php

namespace Webflow;

use Webflow\WebflowException;
use Illuminate\Support\Facades\Log;

class Api
{
      // changed api endpoints
    // https://docs.developers.webflow.com/data/changelog/webflow-api-changed-endpoints
    // https://docs.developers.webflow.com/data/reference/
    const WEBFLOW_API_ENDPOINT = 'https://api.webflow.com/v2';
    const WEBFLOW_API_USERAGENT = 'Expertlead Webflow PHP SDK (https://github.com/expertlead/webflow-php-sdk)';

    private $client;
    private $token;

    private $requests;
    private $start;
    private $finish;

    private $cache = [];

    public function __construct(
        $token,
        $version = '2.0.0'
    ) {
        if (empty($token)) {
            throw new WebflowException('token');
        }

        $this->token = $token;
        $this->version = $version;

        $this->rateRemaining = 60;

        return $this;
    }

    private function request(string $path, string $method,   $data = [])
    {
        $curl = curl_init();
        $options = [
        CURLOPT_URL => self::WEBFLOW_API_ENDPOINT . $path,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERAGENT => self::WEBFLOW_API_USERAGENT,
        CURLOPT_HTTPHEADER => [
          "Authorization: Bearer {$this->token}",
          "accept-version: {$this->version}",
          "Accept: application/json",
          "Content-Type: application/json",
        ],
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        ];
        if (!empty($data)) {
            $json = json_encode($data);

            $options[CURLOPT_POSTFIELDS] = $json;
            $options[CURLOPT_HTTPHEADER][] = "Content-Length: " . strlen($json);
        }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);
       // Log::debug('curl response webflow', [$response, curl_error($curl)]);
        list($headers, $body) = explode("\r\n\r\n", $response, 2);

        return $this->parse($body);
    }
    private function get($path)
    {
        return $this->request($path, "GET");
    }

    private function post($path, $data)
    {
        return $this->request($path, "POST", $data);
    }

    private function put($path, $data)
    {
        return $this->request($path, "PUT", $data);
    }


    private function patch($path, $data)
    {
        return $this->request($path, "PATCH", $data);
    }

    private function delete($path)
    {
        return $this->request($path, "DELETE");
    }

    private function parse($response)
    {
        $json = json_decode($response);
        if (isset($json->code) && isset($json->message)) {
            $error = $json->message;
            if (isset($json->details)) {
                $error .= PHP_EOL . $json->code . ': ' . implode(PHP_EOL, $json->details) ;
            }
            throw new \Exception($error);
        }
        return $json;
    }

    // Meta
    public function info()
    {
        return $this->get('/info');
    }

    public function sites()
    {
        return $this->get('/sites');
    }

    public function site(string $siteId)
    {
        return $this->get("/sites/{$siteId}");
    }

    public function domains(string $siteId)
    {
        return $this->get("/sites/{$siteId}/custom_domains");
    }

    /**
     * Publish site must take an array list of customdomains to be published to
     * and a boolean indicating if your webflow.io subdomain should be published to
     * @param string $siteId
     * @param array $domains
     * @param $publishWebflowSubdomain
     * @return mixed
     */
    public function publishSite(string $siteId, array $domains, $publishWebflowSubdomain =  false)
    {
        // if  domains empty array then
        if  (!$domains){
            $data = [];
        } else {
            $data = ['customDomains' => $domains];
        }


        $data['publishToWebflowSubdomain'] = $publishWebflowSubdomain;

        return $this->post("/sites/${siteId}/publish", $data);
    }

  /**
   * Publish just the items specified, rather than the entire site
   * @param string $collection_id
   * @param array $itemIds
   * @return mixed
   */
    public function publishItem(string $collection_id, array $itemIds)
    {
        return $this->post("/collections/{$collection_id}/items/publish", [
            'itemIds' => $itemIds,
        ]);
    }

    // Collections
    public function collections(string $siteId)
    {
        return $this->get("/sites/{$siteId}/collections");
    }

    public function collection(string $collectionId)
    {
        return $this->get("/collections/{$collectionId}");
    }

    // Items
    public function items(string $collectionId, int $offset = 0, int $limit = 100)
    {
        $query = http_build_query([
        'offset' => $offset,
        'limit' => $limit,
        ]);
        return $this->get("/collections/{$collectionId}/items?{$query}");
    }

    public function itemsAll(string $collectionId): array
    {
        $response = $this->items($collectionId);
        $items = $response->items;
        $limit = $response->pagination->limit;
        $total = $response->pagination->total;
        $pages = ceil($total / $limit);
        for ($page = 1; $page < $pages; $page++) {
            $offset = $response->pagination->limit * $page;
            $items = array_merge($items, $this->items($collectionId, $offset, $limit)->items);
        }
        return $items;
    }

    public function item(string $collectionId, string $itemId)
    {
        return $this->get("/collections/{$collectionId}/items/{$itemId}");
    }

    public function createItem(string $collectionId,  $fields, bool $live = false)
    {
        $defaults = [
            "isArchived" => false,
            "isDraft" => false,
        ];
        $data =  (object) [
            'fieldData' => [],
          ];
        // must be an object property
        $data->fieldData = $fields;
        return $this->post("/collections/{$collectionId}/items" . ($live ? "?live=true" : ""),
          $data
        );

    }

  /**
   * Version 2 update item patches the item so you do not need to provide all details.
   * @param string $collectionId
   * @param string $itemId
   * @param array $fields
   * @param bool $live
   * @return mixed
   */
    public function updateItem(string $collectionId, string $itemId, array $fields, bool $live = false)
    {
        $data =  (object) [
          'fieldData' => [],
        ];
        // must be an object property
        $data->fieldData = $fields;
         return $this->patch("/collections/{$collectionId}/items/{$itemId}" . ($live ? "?live=true" : ""),  $data);
    }

    public function removeItem(string $collectionId, $itemId)
    {
        return $this->delete("/collections/{$collectionId}/items/{$itemId}");
    }

    public function findOrCreateItemByName(string $collectionId, array $fields)
    {
        if (!isset($fields['name'])) {
            throw new WebflowException('name');
        }
        $cacheKey = "collection-{$collectionId}-items";
        $instance = $this;
        $items = $this->cache($cacheKey, function () use ($instance, $collectionId) {
            return $instance->itemsAll($collectionId);
        });
        foreach ($items as $item) {
            if (strcasecmp($item->name, $fields['name']) === 0) {
                return $item;
            }
        }
        $newItem = $this->createItem($collectionId, $fields);
        $items[] = $newItem;
        $this->cacheSet($cacheKey, $items);
        return $newItem;
    }

    private function cache($key, callable $callback)
    {
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $callback();
        }
        return $this->cache[$key];
    }

    private function cacheSet($key, $value)
    {
        $this->cache[$key] = $value;
    }
}
